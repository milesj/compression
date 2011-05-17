<?php
/**
 * Compression - CSS Builder, Compressor and Cacher
 *
 * Allows the use of defined variables within the CSS file; also compresses the stylesheet and caches it.
 *
 * @author       Miles Johnson - http://milesj.me
 * @copyright    Copyright 2006-2011, Miles Johnson, Inc.
 * @license      http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link         http://milesj.me/code/php/compression
 */

class Compression {

    /**
     * Current version.
     *
     * @access public
     * @var int
     */
    public $version = '1.6';

    /**
     * Is cacheing enabled?
     *
     * @access protected
     * @var boolean
     */
    protected $_cache = true;

    /**
     * Path to the cached files, relative to the given css.
     *
     * @access protected
     * @var string
     */
    protected $_cachePath;

    /**
     * The path to the css file.
     *
     * @access protected
     * @var array
     */
    protected $_css = array();

    /**
     * The path to the directory holding the css files.
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
     * Array of css variable references.
     *
     * @access protected
     * @var array
     */
    protected $_variables;

    /**
     * Loads the css file into the class.
     *
     * @access public
     * @param string|array $stylesheets
     * @return void
     */
    public function __construct($stylesheets = array()) {
        if (!is_array($stylesheets)) {
            $stylesheets = explode(',', trim($stylesheets));
        }

        if (!empty($stylesheets)) {
            foreach ($stylesheets as $sheet) {
                if (strtolower(substr(strrchr($sheet, '.'), 1)) != 'css') {
                    $sheet = trim($sheet, '.') .'.css';
                }

                $this->_css[] = trim($sheet, '/');
                $this->_variables = array();
            }
        } else {
            $this->_parse = false;
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
        if (!$this->_parse) {
            return;
        }

        if (is_array($variable)) {
            foreach ($variable as $var => $value) {
                $this->bind($var, $value);
            }
        } else {
            if (!empty($variable) && !empty($value)) {
                $variable = preg_replace('/[^-_a-zA-Z0-9]/i', '', $variable);

                if (substr($variable, 0, 1) != '@') {
                    $variable = '@'. $variable;
                }

                $this->_variables[$variable] = trim(strip_tags($value));
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
        if (!$this->_parse) {
            return;
        }

        $response = "";

        foreach ($this->_css as $css) {
            $baseCss = $this->_cssPath . $css;
            $cachedCss = $this->_cachePath . $css;
            $cache = true;
            $output = "";

			// Use cache or regenerate
            if (file_exists($cachedCss)) {
                $cssModified = filemtime($baseCss);
                $cacheModified = filemtime($cachedCss);

                if ($cssModified > $cacheModified) {
                    $output = $this->_compress($baseCss, $css);
                } else {
                    $output = file_get_contents($cachedCss);
                    $cache = false;
                }

			// Use base CSS
            } else if (file_exists($baseCss)) {
                $output = $this->_compress($baseCss, $css);
                $cssModified = time();

			// No css
            } else {
                continue;
            }

            if ($this->_cache && $cache){
                $this->_cache($css, $output);
            }

            $response .= $output ."\n\n";
            unset($cache, $output, $baseCss, $cachedCss);
        }

        if ($return) {
            return $response;
        }

		header("Date: ". date("D, j M Y G:i:s ", $cssModified) ." GMT");
		header("Content-Type: text/css");
		header("Expires: ". gmdate("D, j M Y H:i:s", time() + 86400) ." GMT");
		header("Cache-Control: max-age=86400, must-revalidate"); // HTTP/1.1
		header("Pragma: cache"); // HTTP/1.0
		
		echo $response;
    }

    /**
     * Is caching enabled or disabled?
     *
     * @access public
     * @param boolean $enable
     * @return object
     */
    public function setCaching($enable = true) {
        $this->_cache = (boolean) $enable;

        return $this;
    }

    /**
     * Set the path to the location of the css files (and cached files).
     *
     * @access public
     * @param string $path
     * @param string $cacheDir
     * @return object
     */
    public function setPath($path, $cacheDir = '_cache') {
        if (empty($path)) {
            $path = dirname(__FILE__);
        }

        $path = str_replace('\\', '/', $path);

        if (substr($path, -1) != '/') {
            $path .= '/';
        }

        $this->_cssPath = $path;
        $this->_cachePath = $path . $cacheDir . '/';

        return $this;
    }

    /**
     * Creates a cached file of the CSS.
     *
     * @access protected
     * @param string $css
     * @param string $input
     * @return string
     */
    protected function _cache($css, $input) {
        if (!is_dir($this->_cachePath)) {
            mkdir($this->_cachePath, 0777);
        }

        $handle = fopen($this->_cachePath . basename($css), 'w');
        fwrite($handle, $input);
        fclose($handle);

        return true;
    }

    /**
     * Compress the CSS and bind the variables.
     *
     * @access protected
     * @param string $css
	 * @param string $name
     * @return string
     */
    protected function _compress($css, $name) {
        $stylesheet = file_get_contents($css);

        // Parse the variables
        if (!empty($this->_variables)) {
            $stylesheet = str_replace(array_keys($this->_variables), $this->_variables, $stylesheet);
        }

        // Parse the functions
        $stylesheet = preg_replace_callback('/(?:@([_a-zA-Z0-9]+)\((.*?)\))/i', array($this, '_functionize'), $stylesheet);

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
     * Parses the document and runs custom inline functions.
     *
     * @access protected
     * @param string $matches
     * @return string
     */
    protected function _functionize($matches) {
        $function = str_replace('@', '', $matches[1]);
        $args = !empty($matches[2]) ? array_map('trim', explode(',', $matches[2])) : $matches[2];

        // Dont mess with existent css functions
		if (function_exists($function)) {
			return call_user_func_array($function, $args);
		} else {
			trigger_error(sprintf('%s(): Custom function %s does not exist', __METHOD__, $function), E_USER_WARNING);
		}
    }

}
