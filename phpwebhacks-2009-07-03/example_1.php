<?php
/**
 * This script will send email using GMail's web application
 * It's the basic of the more sophisticated free newsletter system
 *
 * See more examples at http://php-http.com/examples
 */

include 'phpWebHacks.php';

$h = new phpWebHacks;

/* open gmail.com */
$h->get('http://gmail.com');

/* extract the hidden fields of the login form */
$form = $h->parseForm('gaia_loginform', &$action);

/* username & password */
$form['Email']  = 'black_hawk_down';
$form['Passwd'] = 'mysecretpass';

/* login */
$h->post($action, $form);

/* go to the 'compose' page */
$h->get('?v=b&pv=tl&cs=b');

/* extract hidden fields of the compose form */
$form = $h->parseForm('f', &$action);

/* write message */
$form['to']          = 'dede_blackheart@hotmail.com';
$form['subject']     = 'phpWebHacks rocks!';
$form['body']        = 'Yeah it rocks!';
$form['nvp_bu_send'] = 'Send';

/* browse attachment */
$file = array(
	'file0' => '/home/nash/najwa.jpg'
);

/* click send button */
$h->post($action, $form, $file);
?>
