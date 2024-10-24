<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    $to = 'p23014788@student.newinti.edu.my';
    $subject = 'User Feedback from ' . $name;
    $body = "Name: $name\nEmail: $email\nFeedback: $message";
    $headers = "From: $email";

    if (mail($to, $subject, $body, $headers)) {
        // Notify user via email
        $userSubject = "Feedback Received";
        $userBody = "Thank you for your feedback. All further communication will be on email.";
        mail($email, $userSubject, $userBody, "From: $to");

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
