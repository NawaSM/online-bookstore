<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT first_name, email FROM users WHERE id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<!-- Fix CSS paths -->
<link rel="stylesheet" href="/onlinebookstore/assets/css/feedback.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<main>
    <div class="feedback-container">
        <div class="feedback-content">
            <div class="section-header">
                <h2>Contact Us</h2>
                <p>Send us a message and we'll reply to your email: <?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <div class="feedback-form-container">
                <form id="feedbackForm" method="POST">
                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" name="message" required 
                                placeholder="Type your message here..."
                                rows="6"></textarea>
                    </div>
                    
                    <button type="submit" class="send-message-btn">
                        Send Message
                    </button>
                </form>
                
                <!-- Add a visible status div -->
                <div id="feedbackStatus" style="margin-top: 15px; display: none;"></div>
            </div>
        </div>
    </div>
</main>

<!-- Debug output -->
<div id="debugOutput" style="display:none;"></div>

<!-- Fix JavaScript path -->
<script>
document.getElementById('feedbackForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const debugOutput = document.getElementById('debugOutput');
    const statusDiv = document.getElementById('feedbackStatus');
    
    // Show loading state
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = 'Sending message...';
    statusDiv.className = '';
    
    fetch('/onlinebookstore/includes/send_feedback.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())  // First get text response for debugging
    .then(text => {
        debugOutput.textContent = text;  // Store raw response
        return JSON.parse(text);  // Then parse as JSON
    })
    .then(data => {
        statusDiv.style.display = 'block';
        
        if (data.success) {
            statusDiv.className = 'success';
            statusDiv.textContent = data.message;
            document.getElementById('feedbackForm').reset();
        } else {
            statusDiv.className = 'error';
            statusDiv.textContent = data.message || 'An error occurred. Please try again.';
        }
    })
    .catch(error => {
        statusDiv.style.display = 'block';
        statusDiv.className = 'error';
        statusDiv.textContent = 'An error occurred. Please check the console.';
        console.error('Error:', error);
        debugOutput.style.display = 'block';  // Show debug output on error
    });
});
</script>

<?php include '../includes/footer.php'; ?>