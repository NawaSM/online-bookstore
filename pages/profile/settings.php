<?php
// pages/profile/settings.php
if (!isset($_SESSION['user_id'])) {
    return;
}

// Remove any whitespace or newlines before this comment
?>
<div class="settings-section">
    <div class="section-header">
        <h2>Account Settings</h2>
    </div>

    <div class="settings-group">
        <h3>Theme Preferences</h3>
        <div class="theme-toggle">
            <label class="switch">
                <input type="checkbox" id="themeToggle" <?php echo (getUserThemePreference($_SESSION['user_id']) === 'dark') ? 'checked' : ''; ?>>
                <span class="slider round"></span>
            </label>
            <span class="theme-label">Dark Mode</span>
        </div>
        <p class="setting-description">Switch between light and dark theme for better viewing experience.</p>
    </div>
</div>

<!-- Add CSS within the same file for now to debug -->
<style>
.settings-section {
    padding: 20px;
}

.settings-group {
    background: var(--bg-secondary);
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.section-header {
    margin-bottom: 20px;
}

.section-header h2 {
    color: var(--text-primary);
    margin: 0;
}

.theme-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
}

.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #4CAF50;
}

input:focus + .slider {
    box-shadow: 0 0 1px #4CAF50;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.theme-label {
    color: var(--text-primary);
    font-size: 16px;
}

.setting-description {
    color: var(--text-secondary);
    font-size: 14px;
    margin-top: 8px;
}
</style>

<!-- Add some debug JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Settings page loaded');
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        console.log('Theme toggle found');
        themeToggle.addEventListener('change', function() {
            console.log('Theme toggle changed:', this.checked);
        });
    } else {
        console.log('Theme toggle not found');
    }
});
</script>