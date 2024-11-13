function setupPasswordToggles() {
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent form submission
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Form elements
    const editButton = document.getElementById('editButton');
    const cancelButton = document.getElementById('cancelButton');
    const saveButton = document.getElementById('saveButton');
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const formInputs = profileForm.querySelectorAll('input.view-mode');
    
    // Modal elements
    const modal = document.getElementById('passwordModal');
    const closeModal = document.querySelector('.close');
    const modalClose = document.querySelector('.modal-close');
    const confirmPassword = document.getElementById('confirmPassword');
    const passwordError = document.querySelector('.password-error');
    
    let originalValues = {};
    let currentAction = ''; // 'edit' or 'password'

    // Store original values
    formInputs.forEach(input => {
        originalValues[input.id] = input.value;
        input.disabled = true;
    });

    // Edit button click handler
    editButton.addEventListener('click', function() {
        currentAction = 'edit';
        modal.style.display = 'block';
    });

    // Cancel button click handler
    cancelButton.addEventListener('click', function() {
        formInputs.forEach(input => {
            input.value = originalValues[input.id];
            input.disabled = true;
        });
        toggleEditMode(false);
    });

    // Profile form submission
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        saveChanges();
    });

    // Password form submission
    passwordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmNewPassword').value;
        
        if (newPassword !== confirmPassword) {
            document.getElementById('passwordMismatch').style.display = 'block';
            return;
        }
        
        document.getElementById('passwordMismatch').style.display = 'none';
        currentAction = 'password';
        modal.style.display = 'block';
    });

    // Modal close handlers
    [closeModal, modalClose].forEach(elem => {
        elem.addEventListener('click', closeModalHandler);
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModalHandler();
        }
    });

    // Password confirmation handler
    confirmPassword.addEventListener('click', async function() {
        const password = document.getElementById('currentPassword').value;
        
        if (currentAction === 'edit') {
            // Verify password for edit mode
            try {
                const response = await verifyPassword(password);
                if (response.success) {
                    modal.style.display = 'none';
                    enableEditMode();
                } else {
                    showPasswordError(response.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showPasswordError('An error occurred');
            }
        } else if (currentAction === 'password') {
            // Handle password change
            const newPassword = document.getElementById('newPassword').value;
            try {
                const response = await changePassword(password, newPassword);
                if (response.success) {
                    modal.style.display = 'none';
                    showMessage('Password updated successfully!', 'success');
                    passwordForm.reset();
                } else {
                    showPasswordError(response.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showPasswordError('An error occurred');
            }
        }
    });
    
    setupPasswordToggles();
    
    function resetPasswordFields() {
        document.querySelectorAll('input[type="text"]').forEach(input => {
            if (input.id.toLowerCase().includes('password')) {
                input.type = 'password';
                const toggleButton = document.querySelector(`[data-target="${input.id}"]`);
                if (toggleButton) {
                    const icon = toggleButton.querySelector('i');
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        });
    }
    
    
    // Helper functions
    function enableEditMode() {
        formInputs.forEach(input => {
            input.disabled = false;
            input.classList.remove('view-mode');
            input.classList.add('edit-mode');
        });
        toggleEditMode(true);
    }

    function toggleEditMode(editing) {
        editButton.style.display = editing ? 'none' : 'inline-flex';
        cancelButton.style.display = editing ? 'inline-flex' : 'none';
        saveButton.style.display = editing ? 'inline-flex' : 'none';

        if (!editing) {
            formInputs.forEach(input => {
                input.disabled = true;
                input.classList.remove('edit-mode');
                input.classList.add('view-mode');
            });
        }
    }

    async function verifyPassword(password) {
        const response = await fetch('/onlinebookstore/assets/api/profile/verify-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ password: password })
        });
        return await response.json();
    }

    async function saveChanges() {
        const formData = new FormData(profileForm);
        try {
            const response = await fetch('/onlinebookstore/assets/api/profile/update-details.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                formInputs.forEach(input => {
                    originalValues[input.id] = input.value;
                    input.disabled = true;
                });
                toggleEditMode(false);
                showMessage('Profile updated successfully!', 'success');
            } else {
                showMessage(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('Error updating profile', 'error');
        }
    }

    async function changePassword(currentPassword, newPassword) {
        const response = await fetch('/onlinebookstore/assets/api/profile/update-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                currentPassword: currentPassword,
                newPassword: newPassword
            })
        });
        return await response.json();
    }

    function closeModalHandler() {
        modal.style.display = 'none';
        passwordError.style.display = 'none';
        document.getElementById('currentPassword').value = '';
        if (currentAction === 'edit') {
            toggleEditMode(false);
        }
    }

    function showPasswordError(message) {
        passwordError.textContent = message;
        passwordError.style.display = 'block';
    }

    function showMessage(message, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `${type}-message`;
        messageDiv.textContent = message;
        
        const container = document.querySelector('.details-section');
        container.insertBefore(messageDiv, container.firstChild);
        
        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }
    // Add password toggle functionality
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    [closeModal, modalClose].forEach(elem => {
        elem.addEventListener('click', function() {
            closeModalHandler();
            resetPasswordFields();
        });
    });
});