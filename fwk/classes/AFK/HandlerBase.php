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

	private $allowed = array();

	public function handle(AFK_Context $ctx) {
		$methods = $this->get_available_methods($ctx->view());
		$ctx->header('Allow: ' . implode(', ', $methods));
		$handler_method = $this->get_handler_method($ctx->method(), $ctx->view());
		if ($handler_method != '') {
			call_user_func(array($this, $handler_method), $ctx);
		} elseif (count($methods) == 1) {
			// Why one? Because the OPTIONS method is always available.
			$ctx->not_found();
		} else {
			$ctx->no_such_method($methods);
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
		if (count($this->allowed) == 0) {
			$methods = get_class_methods(get_class($this));
			foreach ($methods as $m) {
				$parts = explode('_', $m, 3);
				if ($parts[0] == 'on' && count($parts) > 1) {
					$m_view = array_key_exists(2, $parts) ? $parts[2] : '';
					if ($m_view == '' || $m_view == $view) {
						$this->allowed[] = strtoupper($parts[1]);
					}
				}
			}
			if (in_array('GET', $this->allowed)) {
				$this->allowed[] = 'HEAD';
			}
			$this->allowed = array_unique($this->allowed);
		}
		return $this->allowed;
	}

	protected function on_options(AFK_Context $ctx) {
		$ctx->allow_rendering(false);
		// Note: the Allow header is generated by ::handle().
	}
}
