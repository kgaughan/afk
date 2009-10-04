<?php
/*
 * AFK - A minimalist PHP web development library.
 * Copyright (c) Keith Gaughan, 2007. All Rights Reserved.
 *
 * For the full copyright and licence terms, please view the LICENCE file
 * that was distributed with this source code.
 */

class AFK_UploadedFile {

	private $original_filename;
	private $mime_type;
	private $size;
	private $temporary_name;
	private $error;

	public function __construct($original_filename, $mime_type, $size, $temporary_name, $error) {
		$this->original_filename = basename($original_filename);
		$this->mime_type = $mime_type;
		$this->size = $size;
		$this->temporary_name = $temporary_name;
		$this->error = $error;
	}

	public function get_original_filename() {
		return $this->original_filename;
	}

	public function get_mime_type() {
		return $this->mime_type;
	}

	public function get_size() {
		return $this->size;
	}

	public function get_error_code() {
		return $this->error;
	}

	public function move_to($dir, $filename=false) {
		if (!is_dir($dir)) {
			throw Exception(sprintf("'%s' is not a directory.", $dir));
		}
		$filename = $filename === false ? $this->original_filename : basename($filename);
		if (!$this->exists()) {
			throw Exception(sprintf("'%s' no longer exists.", $filename));
		}
		if (move_uploaded_file($this->temporary_name, "$dir/$filename")) {
			$this->temporary_filename = false;
			return true;
		}
		return false;
	}

	public function is_good() {
		return $this->error == UPLOAD_ERR_OK;
	}

	public function has_good_mime_type(array $valid_types) {
		return in_array($this->mime_type, $valid_types, true);
	}

	public function exists() {
		return
			$this->temporary_filename !== false &&
			is_uploaded_file($this->temporary_name) &&
			file_exists($this->temporary_name);
	}

	public function delete() {
		if ($this->exists()) {
			unlink($this->temporary_name);
			$this->temporary_name = false;
		}
	}

	public function open() {
		if ($this->exists()) {
			return fopen($this->temporary_name, 'r', false);
		}
		return false;
	}
}
