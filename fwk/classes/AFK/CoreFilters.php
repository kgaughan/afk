<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Core filter callbacks.
 *
 * @author Keith Gaughan
 */
class AFK_CoreFilters {

	/**
	 * Populates the request context with AFK_UploadedFile instances from the
	 * $_FILES superglobal.
	 */
	public static function populate_uploaded_files(AFK_Pipeline $pipe, $ctx) {
		foreach ($_FILES as $field_name => $info) {
			$contents = self::convert_uploaded_files(
				$info['name'], $info['type'], $info['size'], $info['tmp_name'], $info['error']);
			if ($contents !== false) {
				$ctx->$field_name = $contents;
			}
		}
		$pipe->do_next($ctx);
	}

	private static function convert_uploaded_files($name, $type, $size, $tmp_name, $error) {
		if (is_array($name)) {
			$files = array();
			for ($i = 0; $i < count($name); $i++) {
				$contents = self::convert_uploaded_files(
					$name[$i], $type[$i], $size[$i], $tmp_name[$i], $error[$i]);
				if ($contents !== false) {
					$files[] = $contents;
				}
			}
			if (count($files) > 0) {
				return $files;
			}
		} elseif ($size > 0) {
			return new AFK_UploadedFile($name, $type, $size, $tmp_name, $error);
		}
		return false;
	}

	/**
	 * Dispatches the request to the appropriate request handler class, if
	 * possible.
	 */
	public static function dispatch(AFK_Pipeline $pipe, $ctx) {
		if (is_null($ctx->_handler)) {
			throw new AFK_Exception('No handler specified.');
		}
		$handler_class = $ctx->_handler . 'Handler';
		try {
			// The next line will trigger an AFK_ClassLoadException if no such
			// handler exists. It also ensures that the autoloader will be
			// called. Simply attempting to instantiate the class does not.
			class_exists($handler_class);
		} catch (AFK_ClassLoadException $clex) {
			throw new AFK_ClassLoadException(sprintf("No such handler: %s (%s)", $handler_class, $clex->getMessage()));
		}
		$handler = new $handler_class();
		$handler->handle($ctx);
		unset($hander_class, $handler);
		$pipe->do_next($ctx);
	}

	/** Does the final stage of request processing: rendering. */
	public static function render(AFK_Pipeline $pipe, $ctx) {
		if ($ctx->rendering_is_allowed()) {
			$engine = AFK_Registry::_('template_engine');
			$engine->add_path(APP_TEMPLATE_ROOT . '/' . strtolower($ctx->_handler));
			$ctx->defaults(array('page_title' => ''));
			$env = array_merge($ctx->as_array(), compact('ctx'));
			try {
				ob_start();
				ob_implicit_flush(false);
				$engine->render($ctx->view('default'), $env);
				$pipe->do_next($ctx);
				ob_end_flush();
			} catch (Exception $e) {
				ob_end_clean();
				throw $e;
			}
		}
	}

	/**
	 * Forces the current AFK_Users implementation to authenticate the user.
	 */
	public static function require_auth(AFK_Pipeline $pipe, $ctx) {
		AFK_Users::force_auth();
		$pipe->do_next($ctx);
	}
}
