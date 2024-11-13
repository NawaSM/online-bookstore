document.getElementById('feedbackForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitButton = this.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    
    // Disable button and show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
    const formData = new FormData(this);
    
    fetch('/onlinebookstore/includes/send_feedback.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const statusDiv = document.getElementById('feedbackStatus');
        statusDiv.style.display = 'block';
        
        if (data.success) {
            statusDiv.className = 'success';
            statusDiv.textContent = 'Thank you for your feedback! We will get back to you soon.';
            this.reset();
        } else {
            statusDiv.className = 'error';
            statusDiv.textContent = data.message || 'An error occurred. Please try again.';
        }
        
        // Reset button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
        
        // Scroll status into view
        statusDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Hide status after 5 seconds
        setTimeout(() => {
            statusDiv.style.display = 'none';
        }, 5000);
    })
    .catch(error => {
        const statusDiv = document.getElementById('feedbackStatus');
        statusDiv.style.display = 'block';
        statusDiv.className = 'error';
        statusDiv.textContent = 'An error occurred. Please try again.';
        
        // Reset button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    });
});