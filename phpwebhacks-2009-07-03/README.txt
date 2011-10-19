phpWebHacks 1.5
===============
This is an advanced HTTP client written in PHP. It simulates a real web browser,
only that you use it with lines of code rather than mouse and keyboard. Using
pure PHP, no Curl or other fancy dependencies needed. The functionality makes it
a perfect tool for HTTP scripting.

Author:  Nashruddin Amin - me@nashruddin.com
License: GPL
Website: http://php-http.com

Features
========
* Support HTTP/1.1
* HEAD, GET, and POST
* HTTPS
* Cookies
* Redirects
* HTTP authentication
* Proxy
* Gzip encoding
* log HTTP streams for full debugging
* Parsing HTML forms
* Custom User-Agent

Documentation
=============
* head($url) 
  Make HTTP HEAD request to $url. Returns the response header in associative
  array.

* get($url)
  Make HTTP GET request to $url. It will handle all redirects and returns the last
  HTML body.

* post($url, $form=array(), $files=array())
  Make HTTP POST request to $url. Returns the last HTML body.

* parseForm($name_or_id, &$action, $html_string)
  Parse HTML forms and convert it to associative array.

* setProxy($host, $port, $user='', $pass='')
  Set the proxy server to be used.

* setDebug($is_debug)
  If is_debug is true, all HTTP streams will be logged. It's useful for debugging.

* setInterval($second)
  Set the delay between requests.

* setUserAgent($name)
  Assign a name to this HTTP client. It will appear in the 'User-Agent' field of the
  HTTP header.

See http://php-http.com/documentation for more details.

Example
=======
This is a simple example to fetch a web page:

<?php
include 'phpWebHacks.php';

$h = new phpWebHacks;
$page = $h->get('http://google.com/search?q=php+http');
?>

See more examples at http://php-http.com/examples.

Contacts
========
Send comments, suggestions, and bug reports to me@nashruddin.com.

