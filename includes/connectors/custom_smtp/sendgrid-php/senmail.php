<?php
//require 'vendor/autoload.php'; // If you're using Composer (recommended)
// Comment out the above line if not using Composer
 require("sendgrid-php.php");//phpcs:disable
// If not using Composer, uncomment the above line and
// download sendgrid-php.zip from the latest release here,
// replacing <PATH TO> with the path to the sendgrid-php.php file,
// which is included in the download:
// https://github.com/sendgrid/sendgrid-php/releases

$email = new \SendGrid\Mail\Mail();
$from_name = isset( $atts['fromname'] ) ? $atts['fromname'] : '';
$email->setFrom( $sendfrom, $from_name );
$email->setSubject( $atts['subject'] );
$email->addTo( $atts['to'] );
$email->addContent("text/plain", $atts['message'] );
$email->addContent(
    "text/html", $atts['message']
);
$sendgrid = new \SendGrid( $api );
try {
    $response = $sendgrid->send($email);
    //print $response->statusCode() . "\n";
    //print_r($response->headers());
    //print $response->body() . "\n";
	if ( $response->statusCode() == 202 ) {
		print 'Email sent';
	}
} catch (Exception $e) {
    echo 'Caught exception: '. $e->getMessage() ."\n";
}