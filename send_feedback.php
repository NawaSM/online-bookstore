<?php
header('Content-Type: application/json');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';

    if (!empty($name) && !empty($email) && !empty($message)) {
        $api_key = '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';
        $email_content = "Name: $name\nEmail: $email\n\nMessage:\n$message";

        $url = 'https://api.sendgrid.com/v3/mail/send';
        $data = [
            'personalizations' => [
                [
                    'to' => [['email' => 'p23014788@student.newinti.edu.my']]
                ]
            ],
            'from' => ['email' => 'd4rth02@gmail.com'],
            'subject' => 'New Feedback from NawLexKen Books',
            'content' => [
                [
                    'type' => 'text/plain',
                    'value' => $email_content
                ]
            ]
        ];

        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n" .
                             "Authorization: Bearer $api_key\r\n",
                'content' => json_encode($data)
            ]
        ];

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result !== false) {
            $response['success'] = true;
        } else {
            $response['error'] = 'Failed to send email';
        }
    } else {
        $response['error'] = 'Please fill in all fields.';
    }
}

echo json_encode($response);