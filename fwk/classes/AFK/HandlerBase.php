<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * Basic functionality common to all handlers.
 */
class AFK_HandlerBase implements AFK_Handler {

	public function handle(AFK_Context $ctx) {
		$method = $this->get_handler_method($ctx->method(), $ctx->view());
		if ($method != '') {
			call_user_func(array($this, $method), $ctx);
		} else {
			$methods = $this->get_available_methods($ctx->view());
			// Why one? Because the OPTIONS method is always available.
			if (count($methods) == 1) {
				$ctx->not_found();
			} else {
				$ctx->no_such_method($methods);
			}
		}
	}

	private function get_handler_method($method, $view) {
		$suffix = empty($view) ? '' : "_$view";
		if (method_exists($this, "on_$method$suffix")) {
			return "on_$method$suffix";
		}
		if ($method === 'head') {
			return $this->get_handler_method('get', $view);
		}
		if ($view != '') {
			return $this->get_handler_method($method, '');
		}
		return '';
	}

	/**
	 * Figures out which HTTP methods this handler will accept for the given
	 * view.
	 */
	private function get_available_methods($view) {
		static $allowed = array();
		if (count($allowed) == 0) {
			$methods = get_class_methods(get_class($this));
			$allowed = array();
			foreach ($methods as $m) {
				$parts = explode('_', $m, 3);
				if ($parts[0] == 'on' && count($parts) > 1) {
					$m_view = isset($parts[2]) ? $parts[2] : '';
					if ($m_view == '' || $m_view == $view) {
						$allowed[] = strtoupper($parts[1]);
					}
				}
			}
			$allowed = array_unique($allowed);
		}
		return $allowed;
	}

	protected function on_options(AFK_Context $ctx) {
		header('Allow: ' . implode(', ', $this->get_available_methods($ctx->view())));
		$ctx->allow_rendering(false);
	}
}
