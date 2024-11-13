<?php
session_start();
include '../config/database.php';
include '../config/sendgrid.php';
require '../vendor/autoload.php';  // Using Composer's autoloader now

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please login to send feedback');
    }

    if (empty($_POST['message'])) {
        throw new Exception('Message is required');
    }

    $message = htmlspecialchars(trim($_POST['message']));

    $stmt = $conn->prepare("SELECT email, first_name, last_name FROM users WHERE id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    $conn->beginTransaction();

    $stmt = $conn->prepare("INSERT INTO feedback (user_id, subject, message) VALUES (:user_id, :subject, :message)");
    $result = $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'subject' => "Feedback from " . $user['first_name'] . " " . $user['last_name'],
        'message' => $message
    ]);
    
    if (!$result) {
        throw new Exception('Failed to save feedback');
    }

    $email = new \SendGrid\Mail\Mail();
    $email->setFrom("d4rth02@gmail.com", $user['first_name'] . " " . $user['last_name']);
    $email->setSubject("Website Feedback from " . $user['first_name'] . " " . $user['last_name']);
    $email->addTo("p23014788@student.newinti.edu.my", "Admin");
    $email->addContent("text/html", "
        <div style='font-family: Arial, sans-serif;'>
            <p><strong>From:</strong> {$user['first_name']} {$user['last_name']}</p>
            <p><strong>Email:</strong> {$user['email']}</p>
            <div style='margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px;'>
                " . nl2br($message) . "
            </div>
            <p style='color: #666; font-size: 12px;'>This message was sent via the website contact form.</p>
        </div>"
    );

    $sendgrid = new \SendGrid(SENDGRID_API_KEY);
    $response = $sendgrid->send($email);

    if ($response->statusCode() !== 202) {
        error_log("SendGrid Error: " . json_encode($response));
        throw new Exception('Failed to send email');
    }

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Thank you! Your message has been sent successfully.'
    ]);

} catch (Exception $e) {
    if ($conn) {
        $conn->rollBack();
    }
    error_log("Feedback Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

$conn = null;
?>