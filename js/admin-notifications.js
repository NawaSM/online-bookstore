// js/admin-notifications.js

function showNotification(message, type = 'success') {
    if (!message) return; // Don't show empty notifications
    
    // Remove any existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notif => {
        if (document.body.contains(notif)) {
            document.body.removeChild(notif);
        }
    });

    // Create the notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    // Create message span
    const messageSpan = document.createElement('span');
    messageSpan.className = 'notification-message';
    messageSpan.textContent = message;
    notification.appendChild(messageSpan);

    // Create close button
    const closeButton = document.createElement('span');
    closeButton.className = 'notification-close';
    closeButton.innerHTML = '&times;';
    closeButton.onclick = function() {
        if (document.body.contains(notification)) {
            document.body.removeChild(notification);
        }
    };

    notification.appendChild(closeButton);

    // Add the notification to the page
    document.body.appendChild(notification);

    // Log for debugging
    console.log('Showing notification:', message, type);

    // Automatically remove the notification after 5 seconds
    setTimeout(() => {
        if (document.body.contains(notification)) {
            document.body.removeChild(notification);
        }
    }, 5000);
}