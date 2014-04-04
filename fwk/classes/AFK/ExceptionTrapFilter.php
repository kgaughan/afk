<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Wraps a PHP error that's been converted to an exception.
 */
class AFK_TrappedErrorException extends AFK_Exception {

	protected $ctx;

	public function __construct($msg, $code, $file, $line, $ctx) {
		parent::__construct($msg, $code);
		$this->file = $file;
		$this->line = $line;
		$this->ctx  = $ctx;
	}
}

/**
 * Traps exceptions thrown within the application, converting them to
 * user-friendly screens. This class also traps and converts PHP errors to
 * exceptions.
 *
 * There are two classes of exception handled: subclasses of AFK_HttpException
 * (including AFK_HttpException itself), which represent HTTP status codes, and
 * all other exceptions, which are universally treated as triggering a 500
 * Internal Server Error status code.
 *
 * The filter assumes that a filter capable of rendering is the last one in
 * the pipeline. The templates it uses for rendering the exceptions have the
 * form error<STATUS>.php, so to the template rendered for a 404 Page Not Found
 * would be error404.php.
 */
class AFK_ExceptionTrapFilter implements AFK_Filter {

	public function convert_error($errno, $errstr, $errfile, $errline, $ctx) {
		// Only trigger the exception if the error hasn't been suppressed with '@'.
		if (error_reporting() != 0) {
			restore_error_handler();
			throw new AFK_TrappedErrorException($errstr, $errno, $errfile, $errline, $ctx);
		}
		return false;
	}

	public function execute(AFK_Pipeline $pipe, $ctx) {
		$errors = E_ALL;
		if (defined('E_DEPRECATED')) {
			$errors &= ~E_DEPRECATED;
		}
		try {
			set_error_handler(array($this, 'convert_error'), $errors);
			$pipe->do_next($ctx);
			restore_error_handler();
		} catch (AFK_HttpException $he) {
			restore_error_handler();
			$ctx->allow_rendering();
			foreach ($he->get_headers() as $h) {
				$ctx->header($h);
			}
			$ctx->message = $he->getMessage();
			$this->report_error($he->getCode(), $pipe, $ctx);
		} catch (Exception $e) {
			// The previous exception handler is restored previously to
			// this being thrown, so there's no need.
			if (get_class($e) != 'AFK_TrappedErrorException') {
				restore_error_handler();
			}

			$ctx->allow_rendering();
			$this->render_error500($ctx, $e);
			$this->report_error(500, $pipe, $ctx);

			AFK::load_helper('events');
			trigger_event('afk:internal_error', array('ctx' => $ctx, 'exception' => $e));
		}
	}

	private function report_error($code, AFK_Pipeline $pipe, AFK_Context $ctx) {
		// Assuming the last pipeline element is a rendering filter.
		// Not entirely happy with this.
		$ctx->change_view('error' . $code);
		$ctx->set_response_code($code);
		$pipe->to_end();
		$pipe->do_next($ctx);
	}

	// Diagnostics Page Rendering {{{

	private function render_error500(AFK_Context $ctx, Exception $ex) {
		$traceback = array();
		$last_file = $ex->getFile();
		$last_line = $ex->getLine();
		$trace = $ex->getTrace();
		foreach ($trace as $f) {
			if (!empty($last_file)) {
				 $traceback[] = $this->make_traceback_frame(
					$last_file, $last_line,
					$this->frame_to_name($f));
			}
			if (array_key_exists('file', $f)) {
				$last_file = $f['file'];
				$last_line = $f['line'];
			} else {
				$last_file = null;
				$last_line = null;
			}
		}
		$traceback[] = $this->make_traceback_frame($last_file, $last_line);

		AFK::load_helper('html');
		$ctx->page_title = sprintf('%s [%s]',
			get_class($ex), $this->truncate_filename($ex->getFile()));
		$ctx->message = $ex->getMessage();
		$ctx->details = $ex->__toString();
		$ctx->traceback = $traceback;
	}

	private function make_traceback_frame($file, $line, $function='') {
		return array(
			'file'    => $this->truncate_filename($file),
			'line'    => $line,
			'context' => $this->get_context_lines($file, $line),
			'method'  => $function === '' ? '' : "$function()");
	}

	/**
	 * Converts an exception trace frame to a function/method name.
	 */
	private function frame_to_name(array $frame) {
		$name = '';

		// Handle calls via call_user_func*().
		if (substr($frame['function'], 0, 14) == 'call_user_func') {
			$array_args = substr($frame['function'], 0, -6) == '_array';
			$args = $frame['args'];
			if (is_array($args[0])) {
				// Calling a static or instance method
				list($owner, $method) = $frame['args'][0];
				$frame['function'] = $method;
				if (is_object($owner)) {
					// Instance method.
					$frame['class'] = get_class($owner);
					$frame['type'] = '->';
				} else {
					// Class method.
					$frame['class'] = $owner;
					$frame['type'] = '::';
				}
			} else {
				// Function.
				$frame['function'] = $args[0];
			}
			if ($array_args) {
				// call_user_func_array()
				$frame['args'] = $args[1];
			} else {
				// call_user_func()
				array_shift($frame['args']);
			}
		}

		if (array_key_exists('class', $frame)) {
			$name .= $frame['class'] . $frame['type'];
		}
		$name .= $frame['function'];
		return $name;
	}

	/**
	 * Extracts the lines in and around where where an exception was thrown to
	 * give it context.
	 */
	private function get_context_lines($file, $line_no, $amount=3) {
		$context = array();
		// TODO: Quick dumb hack. Need to deal with cases of generated code
		// (such as in regexes) properly.
		if (is_file($file)) {
			$lines = file($file);
			for ($i = $line_no - $amount; $i <= $line_no + $amount; $i++) {
				if ($i >= 0 && array_key_exists($i - 1, $lines)) {
					$context[$i] = rtrim($lines[$i - 1]);
				}
			}
		}
		return $context;
	}

	/**
	 * Truncates an application/library filename.
	 */
	private function truncate_filename($filename) {
		if (substr($filename, 0, strlen(AFK_ROOT)) == AFK_ROOT) {
			return 'AFK:' . substr($filename, strlen(AFK_ROOT) + 1);
		}
		if (defined('APP_ROOT') && substr($filename, 0, strlen(APP_ROOT)) == APP_ROOT) {
			return substr($filename, strlen(APP_ROOT) + 1);
		}
		return $filename;
	}

	// }}}
}
