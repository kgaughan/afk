<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

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
		throw new AFK_TrappedErrorException($errstr, $errno, $errfile, $errline, $ctx);
	}

	public function execute(AFK_Pipeline $pipe, $ctx) {
		set_error_handler(array($this, 'convert_error'), E_ALL);

		try {
			$pipe->do_next($ctx);
		} catch (AFK_HttpException $he) {
			$ctx->allow_rendering();
			foreach ($he->get_headers() as $h) {
				header($h);
			}
			$ctx->message = $he->getMessage();
			$this->report_error($he->getCode(), $pipe, $ctx);
		} catch (Exception $e) {
			$ctx->allow_rendering();
			$this->render_error500($ctx, $e);
			$this->report_error(500, $pipe, $ctx);
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
		$traceback[] = $this->make_traceback_frame($ex->getFile(), $ex->getLine());
		foreach ($ex->getTrace() as $f) {
			if (!empty($f['file']) && !$this->should_ignore($f)) {
				 $traceback[] = $this->make_traceback_frame(
					$f['file'], $f['line'],
					$this->frame_to_name($f));
			}
		}

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
			'method'  => "$function()");
	}

	/**
	 * Converts an exception trace frame to a function/method name.
	 */
	private function frame_to_name(array $frame) {
		$name = '';
		if (isset($frame['class'])) {
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
				if ($i >= 0 && isset($lines[$i - 1])) {
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

	/**
	 *
	 */
	private function should_ignore(array $f) {
		static $methods_to_ignore = array(
			'AFK_Pipeline'       => array('start', 'do_next'),
		//	'AFK_Filter'         => array('execute'),
			'AFK_TemplateEngine' => array('internal_render'));
		static $functions_to_ignore = array('require');
		if (isset($f['class'])) {
			foreach ($methods_to_ignore as $class => $methods) {
				if ($class == $f['class'] || is_subclass_of($class, $f['class'])) {
					return array_search($f['function'], $methods) !== false;
				}
			}
			return false;
		}
		return array_search($f['function'], $functions_to_ignore) !== false;
	}

	// }}}
}

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
