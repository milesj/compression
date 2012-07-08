<?php
/**
 * Compression
 *
 * A basic class that loads CSS files, compresses them, and saves the resulting output into a cached file.
 * Also supports dynamic variables and functions within the CSS file.
 *
 * @version		2.1
 * @author		Miles Johnson - http://milesj.me
 * @copyright	Copyright 2006-2011, Miles Johnson, Inc.
 * @license		http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link		http://milesj.me/code/php/compression
 */

class Compression {

	/**
	 * Current version.
	 *
	 * @access public
	 * @var string
	 */
	public $version = '2.1';

	/**
	 * Is caching enabled?
	 *
	 * @access protected
	 * @var boolean
	 */
	protected $_cache = true;

	/**
	 * Path to the cached files, relative to the given CSS path.
	 *
	 * @access protected
	 * @var string
	 */
	protected $_cachePath;

	/**
	 * The paths of the CSS files.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_css = array();

	/**
	 * The path to the directory holding the CSS files.
	 *
	 * @access protected
	 * @var string
	 */
	protected $_cssPath;

	/**
	 * Should the stylesheet be parsed?
	 *
	 * @access protected
	 * @var boolean
	 */
	protected $_parse = true;

	/**
	 * Array of CSS variable references.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_variables = array();

	/**
	 * Loads the CSS file into the class.
	 *
	 * @access public
	 * @param string|array $stylesheets
	 */
	public function __construct($stylesheets = array()) {
		if (!empty($stylesheets)) {
			if (is_string($stylesheets)) {
				$stylesheets = explode(',', trim($stylesheets));
			}

			foreach ($stylesheets as $sheet) {
				if (strtolower(substr(strrchr($sheet, '.'), 1)) !== 'css') {
					$sheet = trim($sheet, '.') .'.css';
				}

				$this->_css[] = trim($sheet, '/');
				$this->_variables = array();
			}

			$this->setPath();
		} else {
			$this->_parse = false;
		}
	}

	/**
	 * Binds variables to the CSS stylesheets.
	 *
	 * @access public
	 * @param string|array $variable
	 * @param string $value
	 * @return Compression
	 * @chainable
	 */
	public function bind($variable, $value = null) {
		if (!$this->_parse) {
			return $this;
		}

		if (is_array($variable)) {
			foreach ($variable as $var => $value) {
				$this->bind($var, $value);
			}
		} else {
			$variable = preg_replace('/[^-_a-zA-Z0-9]/i', '', $variable);

			if (substr($variable, 0, 1) !== '@') {
				$variable = '@' . $variable;
			}

			$this->_variables[$variable] = trim(strip_tags($value));
		}

		return $this;
	}

	/**
	 * Forces the users browser not to cache the results of the current request.
	 *
	 * @access public
	 * @return void
	 */
	public function disableCache() {
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}

	/**
	 * Parses the stylesheet; does important logic for caching and compressing.
	 *
	 * @access public
	 * @return string
	 */
	public function parse() {
		$response = "";

		if (!$this->_parse || empty($this->_css)) {
			return $response;
		}

		foreach ($this->_css as $css) {
			$baseCss = $this->_cssPath . $css;
			$cachedCss = $this->_cachePath . $css;
			$cache = true;
			$output = "";

			// Use cache or regenerate
			if (is_file($cachedCss)) {
				$cssModified = filemtime($baseCss);

				if ($cssModified > filemtime($cachedCss)) {
					$output = $this->_compress($baseCss, $css);
				} else {
					$output = file_get_contents($cachedCss);
					$cache = false;
				}

			// Use base CSS
			} else if (is_file($baseCss)) {
				$output = $this->_compress($baseCss, $css);
				$cssModified = time();

			// No CSS
			} else {
				continue;
			}

			if ($this->_cache && $cache){
				$this->_cache($css, $output);
			}

			$response .= $output ."\n\n";
		}

		header("Date: " . date("D, j M Y G:i:s ", $cssModified) . " GMT");
		header("Content-Type: text/css");
		header("Expires: " . gmdate("D, j M Y H:i:s", time() + 86400) . " GMT");
		header("Cache-Control: max-age=86400, must-revalidate"); // HTTP/1.1
		header("Pragma: cache"); // HTTP/1.0

		return $response;
	}

	/**
	 * Enable or disable caching.
	 *
	 * @access public
	 * @param boolean $enable
	 * @return Compression
	 * @chainable
	 */
	public function setCaching($enable = true) {
		$this->_cache = (boolean) $enable;

		return $this;
	}

	/**
	 * Set the path to the location of the CSS files (and cached files).
	 *
	 * @access public
	 * @param string $path
	 * @param string $cachePath
	 * @return Compression
	 * @chainable
	 */
	public function setPath($path= null, $cachePath = '_cache') {
		if (empty($path)) {
			$path = dirname(__FILE__);
		}

		$path = trim(str_replace('\\', '/', $path), '/');

		if (substr($path, -1) !== '/') {
			$path .= '/';
		}

		$this->_cssPath = $path;
		$this->_cachePath = $path . $cachePath . '/';

		return $this;
	}

	/**
	 * Creates a cached file of the CSS.
	 *
	 * @access protected
	 * @param string $name
	 * @param string $content
	 * @return void
	 */
	protected function _cache($name, $content) {
		$path = $this->_cachePath . $name;
		$dir = dirname($path);

		if (!is_dir($dir)) {
			mkdir($dir, 0777);

		} else if (!is_writeable($dir)) {
			chmod($dir, 0777);
		}

		file_put_contents($path, $content);
	}

	/**
	 * Compress the CSS and bind the variables.
	 *
	 * @access protected
	 * @param string $path
	 * @param string $name
	 * @return string
	 */
	protected function _compress($path, $name) {
		$stylesheet = file_get_contents($path);

		// Parse the functions
		$stylesheet = preg_replace_callback('/(?:@([_\.a-zA-Z0-9]+)\((.*?)\))/i', array($this, '_functionize'), $stylesheet);

		// Parse the variables
		if (!empty($this->_variables)) {
			$stylesheet = str_replace(array_keys($this->_variables), $this->_variables, $stylesheet);
		}

		// Remove all whitespace
		$output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $stylesheet);
		$output = str_replace(array("\r\n", "\r", "\n", "\t", '/\s\s+/', '  ', '   '), '', $output);
		$output = str_replace(array(' {', '{ '), '{', $output);
		$output = str_replace(array(' }', '} '), '}', $output);
		$output = str_replace(': ', ':', $output);

		$ratio  = 100 - (round(strlen($output) / strlen($stylesheet), 3) * 100);
		$output = "/* $name ($ratio%) */\n". $output;

		return $output;
	}

	/**
	 * Parses the document and execute custom inline functions.
	 *
	 * @access protected
	 * @param string $matches
	 * @return string
	 * @throws Exception
	 */
	protected function _functionize($matches) {
		$function = str_replace('@', '', trim($matches[1]));
		$args = !empty($matches[2]) ? array_map('trim', explode(',', $matches[2])) : $matches[2];

		if (strpos($function, '.') !== false) {
			list($class, $method) = explode('.', $function);

			if (method_exists($class, $method)) {
				return call_user_func_array(array($class, $method), $args);
			}
		} else if (function_exists($function)) {
			return call_user_func_array($function, $args);
		}

		throw new Exception(sprintf('Function %s does not exist.', $function));
	}

}
