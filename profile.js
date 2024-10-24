document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('header');
    const footer = document.querySelector('footer');
    const body = document.body;
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const inputs = document.querySelectorAll('#profileForm input:not([readonly])');
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const navButtons = document.querySelectorAll('.nav-btn');
    const logoutBtn = document.getElementById('logoutBtn');
    
    const adjustBodyPadding = () => {
        const headerHeight = header.offsetHeight;
        const footerHeight = footer.offsetHeight;
        body.style.paddingTop = `${headerHeight}px`;
        body.style.paddingBottom = `${footerHeight}px`;
    };

    // Adjust padding on load and when window is resized
    adjustBodyPadding();
    window.addEventListener('resize', adjustBodyPadding);

    editBtn.addEventListener('click', () => {
        inputs.forEach(input => input.disabled = false);
        saveBtn.style.display = 'inline-block';
        editBtn.style.display = 'none';
    });

    saveBtn.addEventListener('click', () => {
        // Here you would typically send the form data to the server
        // For this example, we'll just disable the inputs and switch the buttons
        inputs.forEach(input => input.disabled = true);
        saveBtn.style.display = 'none';
        editBtn.style.display = 'inline-block';
        alert('Profile updated successfully!');
    });

    togglePassword.addEventListener('click', () => {
        passwordField.type = passwordField.type === 'password' ? 'text' : 'password';
    });

    navButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (button.id !== 'logoutBtn') {
                navButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
            }
        });
    });

    logoutBtn.addEventListener('click', () => {
        if (confirm('Are you sure? Log Out?')) {
            logout();
        }
    });

    function logout() {
        // Send a request to the server to destroy the session
        fetch('logout.php', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to the login page or home page after successful logout
                window.location.href = 'login.php';
            } else {
                alert('Logout failed. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during logout. Please try again.');
        });
    }
});