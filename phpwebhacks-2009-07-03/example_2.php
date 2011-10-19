<?php
/**
 * Uploading image to tinypic.com and retrieve the image's URL 
 */
include 'phpWebHacks.php';

$h = new phpWebHacks;

/* tinypic.com */
$h->get('http://tinypic.com');

/* get the hidden fields */
$form = $h->parseForm('uploadform', &$action);

/* filetype = image, resize = default */
$form['file_type'] = 'image';
$form['dimension'] = '1600';

/* 'browse' the image to upload */
$file = array('the_file' => '/home/nash/elvita.jpg');

/* submit */
$page = $h->post($action, $form, $file);

/* It will show a 'click here to view the image' page 
   and then redirects using javascript. 
   Since javascript is not supported, we need to manually 
   parse the URL */
preg_match('/<a\s+href\s*=\s*"(.+)".*>/iU', $page, $url);

/* get the result page */
$h->get($url[1]);

/* and here are the URLs */
$form = $h->parseForm('email_form');
echo "HTML Code   : " . $form['html-code'] . "\n";
echo "Forums      : " . $form['img-code'] . "\n";
echo "Email       : " . $form['email-url'] . "\n";
echo "Direct link : " . $form['direct-url'] . "\n";
?>
