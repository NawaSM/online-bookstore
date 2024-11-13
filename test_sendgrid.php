<?php
require 'vendor/autoload.php';
require 'config/sendgrid.php';

try {
    $email = new \SendGrid\Mail\Mail();
    $email->setFrom("d4rth02@gmail.com", "TEST");
    $email->setSubject("SendGrid Test");
    $email->addTo("p23014788@student.newinti.edu.my", "ADMIN");
    $email->addContent("text/plain", "Hello, this is a test email from SendGrid!");

    $sendgrid = new \SendGrid(SENDGRID_API_KEY);
    $response = $sendgrid->send($email);

    echo "Response status: " . $response->statusCode() . "\n";
    echo "Response headers: " . print_r($response->headers(), true) . "\n";
    echo "Response body: " . $response->body() . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>