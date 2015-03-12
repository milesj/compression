# Changelog #

*These logs may be outdated or incomplete.*

## 3.0.0 ##

* Updated to PHP 5.3
* Fixed Composer issues

## 2.1 ##

* Added Composer support
* Replaced errors with exceptions
* Refactored to use strict equality

## 2.0 ##

* Added support for stylesheets within subfolders
* Added support for calling class methods within CSS: @ClassName.methodName()
* Changed CSS variables and functions to require an @ before the name
* Converted private members to protected
* Improved the caching mechanism

## 1.4 ##

* Added support for inline CSS functions, written as PHP functions
* Added the parseFunctions() method to handle the new CSS function mechanism
* Added a disableCache() method to be called to disable browser caching
* Added a setCachePath() method so you can define your own cached files location
* Rewrote __construct() with faster logic
* 

## 1.3 ##

* First initial release of Compression
