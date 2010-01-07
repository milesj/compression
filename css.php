<?php
/**
 * Example file to load CSS files through Compression
 * css.php?load=style.css
 */

// Get the path from the query
$stylesheet = (isset($_GET['load'])) ? $_GET['load'] : 'style.css';

// Define our custom function!
function colWidth() {
	$args = func_get_args(); 
	$width = $args[0] * 100;
	return $width .'px';
}

// Require class
require_once('compression.php');

// Initialize
$css = new Compression($stylesheet);

// Turn cache off for testing purposes
$css->setCaching(false);

// Bind the variables and parse
$css->bind(array(
	'img' 	=> '/images/',
	'font' 	=> '"Verdana", "Arial", sans-serif',
	'blue'	=> '#0000FF'
));

$css->parse();
