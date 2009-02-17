<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

/**
 * A flexible yet compact template rendering system.
 */
class AFK_TemplateEngine {

	/* Envelope stack for the current rendering contexts. */
	private $envelopes = array();

	private $current_template = '';

	// Rendering {{{

	private $context = array();

	/** Renders the named template. */
	public function render($name, $values=array()) {
		$this->start_rendering_context($name);
		$this->internal_render($this->find($name), $values);
		$this->end_rendering_context($values);
	}

	/**
	 * Renders a bunch of rows, cycling through a list of named templates; if
	 * there are no rows and a default template is given, that's rendered
	 * instead.
	 */
	public function render_each($names, &$rows, $default=null) {
		if (!empty($rows)) {
			if (!is_array($names)) {
				$names = array($names);
			}
			if (count($names) == 0) {
				throw new AFK_TemplateException('You must specify at least one template for ::render_each()');
			}

			$paths = array_map(array($this, 'find'), $names);

			$this->start_rendering_context($names[0]);

			$row_count = count($rows);
			$current_row = 0;
			foreach ($rows as $r) {
				$r = array_merge($r, compact('row_count', 'current_row'));
				$this->internal_render($paths[$current_row % count($paths)], $r);
				$current_row++;
			}

			$values = compact('row_count');
			$this->end_rendering_context($values);
		} elseif (!is_null($default)) {
			$this->render($default);
		}
	}

	/* Creates a dedicated scope for rendering a PHP template. */
	private function internal_render($__path, &$__values) {
		array_unshift($this->context, $__values);
		try {
			foreach ($this->context as $__c) {
				extract($__c, EXTR_SKIP);
			}
			require($__path);
		} catch (Exception $__e) {
			// Prevent envelopes from inadvertantly wrapping the error.
			foreach ($this->envelopes as $env) {
				if (!is_null($env)) {
					ob_end_clean();
				}
			}
			$this->envelopes = array();
			throw $__e;
		}
		array_shift($this->context);
	}

	// }}}

	// Rendering Contexts {{{

	/* Prepares the current template rendering context. */
	private function start_rendering_context($name) {
		$this->current_template = $name;
		$this->envelopes[] = null;
	}

	/* Concludes the current template rendering context, possibly wrapping it
	   with an enveloping template if one's been specified. */
	private function end_rendering_context(&$values) {
		$envelope = array_pop($this->envelopes);
		if (!is_null($envelope)) {
			$values['generated_content'] = ob_get_contents();
			ob_end_clean();
			$this->render($envelope . '.envelope', $values);
		}
	}

	// }}}

	// Template Searching {{{

	/* Paths to use when searching for templates. */
	private static $paths = array();
	/* Cached template locations. */
	private static $locations = array();

	/**
	 * Adds a number of template search directory paths in the order they'll
	 * be searched.
	 */
	public static function add_paths() {
		$paths = func_get_args();
		$paths = array_reverse($paths);
		foreach ($paths as $path) {
			array_unshift(self::$paths, $path);
		}
	}

	/* Searches the template directories for a named template. */
	protected function find($name) {
		$location = $this->internal_find($name);
		if ($location === false) {
			throw new AFK_TemplateException(sprintf("Unknown template: %s", $name));
		}
		return $location;
	}

	private function internal_find($name) {
		if (array_key_exists($name, self::$locations)) {
			return self::$locations[$name];
		}
		if (count(self::$paths) == 0) {
			throw new AFK_TemplateException('No template search paths specified!');
		}
		foreach (self::$paths as $d) {
			$path = "$d/$name.php";
			if (file_exists($path)) {
				self::$locations[$name] = $path;
				return $path;
			}
		}
		return false;
	}

	// }}}

	// In-template Utilities {{{

	/**
	 * @return The rendering depth of the current template, i.e., 0 when not
	 *         in a template, 1 when rendering the root template, &c.
	 * @note   Envelopes will be at the name depth as the templates that
	 *         they envelope, as will any envelope enveloping an envelope.
	 *         This is because they're considered to be part of the same
	 *         rendering context.
	 */
	protected function depth() {
		// The envelopes instance variable has one entry per rendering context,
		// so the depth can be taken from the number of elements in it.
		return count($this->envelopes);
	}

	/**
	 * Specifies the enveloping template to use with the current rendering
	 * context. Only call this within a template! If one parameter is used,
	 * the envelope template with that name is used, otherwise the one named
	 * after the current template is used. If the named template can't be
	 * found, the default envelope template is used.
	 */
	protected function with_envelope() {
		$name = func_num_args() == 0 ? $this->current_template : func_get_arg(0);

		$end = count($this->envelopes) - 1;
		if ($end < 0) {
			throw new AFK_TemplateException(
				"Um, ::with_envelope() can't be called outside of a template rendering context.");
		}

		if (is_null($this->envelopes[$end])) {
			if ($this->internal_find($name . '.envelope') === false) {
				$name = 'default';
			}
			$this->envelopes[$end] = $name;
			ob_start();
			ob_implicit_flush(false);
		} elseif ($name != $this->envelopes[$end]) {
			throw new AFK_TemplateException(sprintf("Attempt to replace an envelope: %s", $name));
		}
	}

	// }}}
}
