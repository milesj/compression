<?php
/**
 * Example file to load CSS files through Compression
 * css.php?load=style.css
 */

// Get the path from the query
$stylesheet = isset($_GET['load']) ? $_GET['load'] : 'style.css';

// Define our custom function!
function colWidth($size, $base = 100) {
	$width = $size * $base;
	return $width .'px';
}

function debug($var) {
	echo '<pre>'. print_r($var, true) .'</pre>';
}

// Require class
include_once '../compression/Compression.php';

// Initialize
$css = new Compression($stylesheet);

// Set the location of the css files; trailing slash optional
$css->setPath(dirname(__FILE__) .'/css');

// Turn caching off for testing purposes
$css->setCaching(false);

// Bind the variables and parse
$css->bind(array(
	'font_family' => '"Verdana", "Arial", sans-serif',
	'blue' => '#0000FF',
	'img' => '/images'
));

// Output the compressed version
$css->parse();

debug($css);