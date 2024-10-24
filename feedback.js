document.getElementById('feedbackForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const formData = new FormData(this);

    fetch('send_feedback.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const messageElement = document.getElementById('responseMessage');
        if (data.success) {
            messageElement.textContent = 'Email sent successfully';
            messageElement.className = 'success';
        } else {
            messageElement.textContent = 'Email cannot be sent';
            messageElement.className = 'error';
        }
        messageElement.style.display = 'block';

        // Hide the message after 5 seconds
        setTimeout(() => {
            messageElement.style.display = 'none';
        }, 5000);
    })
    .catch(error => {
        console.error('Error:', error);
        const messageElement = document.getElementById('responseMessage');
        messageElement.textContent = 'An error occurred. Please try again later.';
        messageElement.className = 'error';
        messageElement.style.display = 'block';
    });
});

function checkLogin(event, destination) {
    event.preventDefault();
    fetch('check_login.php')
        .then(response => response.json())
        .then(data => {
            if (data.loggedIn) {
                window.location.href = destination;
            } else {
                window.location.href = 'login.php';
            }
        });
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
}