# Compression #

*Documentation may be outdated or incomplete as some URLs may no longer exist.*

*Warning! This codebase is deprecated and will no longer receive support; excluding critical issues.*

Compression is a light weight class that will load a CSS stylesheet, bind and translate given variables, compress and remove white space and cache the output for future use. Upon each request will determine if the cached file should be loaded if the original has had no modifications.

Compression is a play on words for: CSS Compression and **C**ompre**SS**ion.

* Can define variables within your stylesheet instead of typing the same value repeatedly
* Can call PHP functions within the CSS to output dynamic stylesheets
* Edit what delimiters are used for your variables
* Stylesheets are compressed and stripped of whitespace
* Logs a ratio of how much space was saved during compression
* Enable or disable caching
* Cached files and folders are created relative to the parent folder

## Installation ##

Install by manually downloading the library or defining a [Composer dependency](http://getcomposer.org/).

```javascript
{
    "require": {
        "mjohnson/compression": "3.0.0"
    }
}
```

I will assume you have a setup like the following, if not you can easily make it work to your needs.

```php
css/
    index.php // The parser
js/
```

The first thing you need to do is create a file called `index.php` within the `css/` folder. This file is where you would load the `Compression` class, locate the stylesheet and parse it. It is required that you place the parser within the actual css directory or problems will arise. A quick example below.

```php
// Get the path from the query
$stylesheet = isset($_GET['load']) ? $_GET['load'] : 'style.css';

$css = new mjohnson\compression\Compression($stylesheet);
$css->bind('img', '/images');

// Output
echo $css->parse();
```

The concept is that you would pass this file and the load query within the HTML link element. You may also pass a comma separated list of stylesheets and it will compress them into 1 single file. It's pretty much that easy.

```markup
<link href="/css/index.php?load=style.css,other/style.css" rel="stylesheet" type="text/css">
```

### Caching ###

Additionally you can disable or enable caching by using the `setCaching()` method. The method accepts either boolean true or false. Caching is enabled by default. The destination of the cached files will be relative to the folder of the stylesheet given (`<css>/cache/<file>.css`).

```php
$css->setCaching(true);
```

## Using Variables ##

The most basic concept for this class is the stylesheet variables. The concept is that you can define a variable once using bind(), then just place that variable in your stylesheet so that you have consistent data and do not need to write certain strings multiple times. For example you can define the primary image path and font family, all by using the bind() method.

```php
$css->bind(array(
    'img'     => '/images',
    'font'     => '"Verdana", "Arial", sans-serif'
));
```

The bind() method can either be an array of variables and values or can be a single variable as demonstrated in the codeblock above. Once you have binded your variables, simply place them in your stylesheet and they will be parsed automatically.

```css
body {
    font: normal 12px @font;
    color: #000000;
    background: #ffffff url("@img/bg.jpg") 0 0 repeat-x; }
```

Everything should be working correctly. If caching is enabled, you can find the cached file at /css/cache/style.css.

## Using Functions ##

A very popular feature that a majority of developers wish was part of CSS itself, would be inline functions. These would work like regular PHP or programming functions where you can define your functions and pass arguments to determine results (for example math for sizing and structure). Compression has the ability to define functions in PHP and have them process within the CSS!

For example, say we have many CSS classes, all for different column sizes. We can define a base function in PHP to do the math and output a size in pixels. This also works with classes and methods. You must use `func_get_args()` to get the arguments passed from the CSS to the function.

```php
function colWidth() {
    $args = func_get_args(); 
    $width = $args[0] * 100;

    return $width . 'px';
}

// OOP variation
class CSS {
    public function colWidth() {
        // Code
    }
}
```

Once we have defined our function, we can write the function in the CSS and pass it some arguments. In the next example you can see the before and after.

```css
.col1 { width: @colWidth(5); }
.col2 { width: @colWidth(3); }
.col3 { width: @CSS.colWidth(1); }

/* After being parsed */
.col1 { width: 500px; }
.col2 { width: 300px; }
.col3 { width: 100px; }
```

Now you have the power of procedural and functional programming within CSS and can do just about anything!
