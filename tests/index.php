<?php
/**
 * Compression
 *
 * A basic class that loads CSS files, compress them, and saves the resulting output into a cached file.
 * Also supports dynamic variables and functions within the CSS file.
 *
 * @author		Miles Johnson - http://milesj.me
 * @copyright	Copyright 2006-2011, Miles Johnson, Inc.
 * @license		http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link		http://milesj.me/code/php/compression
 */

// Define our dynamic function
// Can be used in the CSS file as @colWidth()
function colWidth($size, $base = 100) {
	return ($size * $base) .'px';
}

// Get the path from the query: index.php?load=style.css,sub/style.css
// Separate multiple stylesheets with a comma
$stylesheet = isset($_GET['load']) ? $_GET['load'] : 'style.css';

// Include class and instantiate
include_once '../compression/Compression.php';

// Accepts a comma separated string, or an array
$css = new Compression($stylesheet);

// Set the location of the CSS files; trailing slash optional
$css->setPath(dirname(__FILE__) .'/css');

// Turn caching off for testing purposes
$css->setCaching(false);

// Bind the variables and parse
// Variables can be used as @variableName
$css->bind(array(
	'font_family' => '"Verdana", "Arial", sans-serif',
	'blue' => '#0000FF',
	'img' => '/images'
));

// Output the compressed version
echo $css->parse();
