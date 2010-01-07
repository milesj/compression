<?php
/**
 * compression.php
 *
 * Allows the use of defined variables within the CSS file; also compresses the stylesheet and caches it
 * 
 * @author 		Miles Johnson - www.milesj.me
 * @copyright	Copyright 2006-2009, Miles Johnson, Inc.
 * @license 	http://www.opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @package     Compression - CSS Builder, Compressor and Cacher
 * @version     1.4
 * @link		www.milesj.me/resources/script/compression
 */

class Compression {

	/**
	 * Current version: www.milesj.me/files/logs/compression
	 *
	 * @access public
	 * @var int 
	 */
	public $version = '1.4';
	
	/**
	 * Is cacheing enabled?
	 *
	 * @access private
	 * @var boolean
	 */
	private $__cache = true;
	
	/**
	 * Path to the cached files, relative to the given css.
	 *
	 * @access private
	 * @var string
	 */
	private $__cachePath = '';
	
	/**
	 * How long to cache the file.
	 *
	 * @access private
	 * @var string
	 */
	private $__cacheDuration = '+7 days';

	/**
	 * The path to the css file.
	 *
	 * @access private
	 * @var string
	 */
	private $__css;
	
	/**
	 * Should the stylesheet be parsed?
	 *
	 * @access private
	 * @var boolean
	 */
	private $__parse = true;
	
	/**
	 * Array of css variable references.
	 *
	 * @access private
	 * @var array
	 */ 
	private $__variables;
	
	/**
	 * The prefix delimiter of your variable.
	 *
	 * @access private
	 * @var string
	 */
	private $__varPre = '[';
	
	/**
	 * The suffix delimiter of your variable.
	 *
	 * @access private
	 * @var string
	 */
	private $__varSuf = ']';
	
	/**
	 * Loads the css file into the class.
	 *
	 * @access public
	 * @param string $file
	 * @return void 
	 */
	public function __construct($file) { 
		if (file_exists($file) && mb_strtolower(substr(strrchr($file, '.'), 1)) == 'css') {
			$path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
			
			$this->__css = $path . basename($file);
			$this->__cachePath = $path .'cache'. DIRECTORY_SEPARATOR;
			$this->__variables = array();
		} else {
			trigger_error('Compression::parse(): Stylesheet "'. basename($file) .'" does not exist', E_USER_WARNING);
			$this->__parse = false;
		}
	}
	
	/**
	 * Binds variables to the CSS stylesheet.
	 *
	 * @access public
	 * @param string $variable
	 * @param string $value
	 * @return object
	 */
	public function bind($variable, $value = null) {
		if ($this->__parse === false) {
			return;
		}
		
		if (is_array($variable)) {
			foreach ($variable as $var => $value) {
				$this->bind($var, $value);
			}
		} else {
			if (!empty($variable) && !empty($value)) {
				$variable = preg_replace('/[^-_a-zA-Z0-9]/i', '', $variable);
				
				if (substr($variable, 0, 1) != $this->__varPre) {
					$variable = $this->__varPre . $variable;
				}
				if (substr($variable, -1) != $this->__varSuf) {
					$variable = $variable . $this->__varSuf;
				}
				
				$this->__variables[$variable] = trim(htmlentities(strip_tags($value), ENT_NOQUOTES, 'UTF-8'));
			}
		}
		
		return $this;
	}
	
	/**
	 * Forces the user's browser not to cache the results of the current request.
	 *
	 * @access public
	 * @return void
	 */
	public function disableCache() {
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: ". gmdate("D, d M Y H:i:s") ." GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}
	
	/**
	 * Parses the stylesheet; does required logic for cacheing and compressing.
	 *
	 * @access public
	 * @param boolean $return
	 * @return string
	 */
	public function parse($return = false) {
		if ($this->__parse === false) {
			return;
		}
		
		$cachedCss = $this->__cachePath . basename($this->css);
		$cache = true;
		
		if (file_exists($cachedCss)) {
			$cssModified = filemtime($this->__css);
			$cacheModified = filemtime($cachedCss);
			
			if ($cssModified > $cacheModified) {
				$output = $this->__compress();
			} else {
				$output = file_get_contents($cachedCss);
				$cache = false;
			}
		} else {
			$output = $this->__compress();
			$cssModified = time();
		}
		
		if ($this->__cache === true && $cache === true){
			$this->__cache($output);
		}
		
		if ($return === true) {
			return $output;
		} else {	
			header("Date: ". date("D, j M Y G:i:s ", $cssModified) ."GMT");
			header("Content-Type: text/css");
			header("Expires: ". gmdate("D, j M Y H:i:s", time() + 86400) ." GMT");
			header("Cache-Control: max-age=86400, must-revalidate"); // HTTP/1.1
			header("Pragma: cache"); // HTTP/1.0
			echo $output;
		}
	}
	
	/**
	 * Is cacheing enabled or disabled?
	 *
	 * @access public
	 * @param boolean $enable
	 * @return object
	 */
	public function setCaching($enable = true) {
		if (is_bool($enable)) {
			$this->__cache = $enable;
		}
		return $this;
	}
	
	/**
	 * Set the path to store the cached files.
	 *
	 * @access public
	 * @param string $path
	 * @return object
	 */
	public function setCachePath($path) {
		if (empty($path)) {
			return false;
		}
		
		$path = trim($path, DIRECTORY_SEPARATOR);
		$path = DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR;
		$this->__cachePath = $path;
		
		return $this;
	}
	
	/**
	 * Set the delimiters to use for the inline variables.
	 *
	 * @access public
	 * @param string $prefix
	 * @param string $suffix
	 * @return object
	 */
	public function setDelimiters($prefix = '[', $suffix = ']') {
		if (empty($prefix) && empty($suffix)) {
			return false;
		}
		
		$prefix = preg_replace('/[^-_=+;:<>{}\[\]|]/i', '', $prefix);
		if ($prefix != '') {
			$this->__varPre = $prefix;
		}
		
		$suffix = preg_replace('/[^-_=+;:<>{}\[\]|]/i', '', $suffix);
		if ($suffix != '') {
			$this->__varSuf = $suffix;
		}
		
		return $this;
	}
	
	/**
	 * Creates a cached file of the CSS.
	 *
	 * @access private
	 * @param string $input
	 * @return string
	 */
	private function __cache($input) {
		if (!is_dir($this->__cachePath)) {
			mkdir($this->__cachePath, 0777);
		}
		
		$handle = fopen($this->__cachePath . basename($this->__css), 'w');
		fwrite($handle, $input);
		fclose($handle);
		
		return true;
	}
	
	/**
	 * Compress the CSS and bind the variables.
	 *
	 * @access private
	 * @return string
	 */
	private function __compress() {
		$stylesheet = file_get_contents($this->__css);
		
		// Parse the variables
		if (!empty($this->__variables)) {
			$stylesheet = str_replace(array_keys($this->__variables), array_values($this->__variables), $stylesheet);
		}
		
		// Parse the functions
		$stylesheet = preg_replace_callback('/(?:([_a-zA-Z0-9]+)\((.*?)\))/i', array($this, '__parseFunctions'), $stylesheet);
		
		// Remove all whitespace
		$output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $stylesheet);
		$output = str_replace(array("\r\n", "\r", "\n", "\t", '/\s\s+/', '  ', '   '), '', $output);
		$output = str_replace(array(' {', '{ '), '{', $output);
		$output = str_replace(array(' }', '} '), '}', $output);
		$output = str_replace(': ', ':', $output);
		
		$ratio  = 100 - (round(mb_strlen($output) / mb_strlen($stylesheet), 3) * 100);
		$output = "/* file: ". basename($this->__css) .", ratio: $ratio% */ ". $output;
		
		return $output;
	}

	/**
	 * Parses the document and runs custom inline functions.
	 * 
	 * @access private
	 * @param string $matches
	 * @return string
	 */
	private function __parseFunctions($matches) {
		$function = $matches[1];
		$args = (!empty($matches[2])) ? array_map('trim', explode(',', $matches[2])) : $matches[2];
		
		// Dont mess with existent css functions
		if (in_array($function, array('url', 'attr', 'rect', 'rgb', 'alpha', 'lang'))) {
			return $matches[0];
		} else {
			if (function_exists($function)) {
				return call_user_func_array($function, $args);
			} else {
				trigger_error('Compression::__parseFunctions(): Custom function "'. $function .'" does not exist', E_USER_WARNING);
			}
		}
		
		return null;
	}
	
}
