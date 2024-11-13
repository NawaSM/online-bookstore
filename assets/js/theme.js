document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    
    // Check for saved theme preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
        themeToggle.checked = savedTheme === 'dark';
    }
    
    // Handle theme toggle
    themeToggle.addEventListener('change', function() {
        const theme = this.checked ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        // Send theme preference to server
        updateThemePreference(theme);
    });
});

async function updateThemePreference(theme) {
    try {
        const response = await fetch('/onlinebookstore/assets/api/profile/update-theme.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ theme: theme })
        });
        const data = await response.json();
        if (!data.success) {
            console.error('Failed to save theme preference');
        }
    } catch (error) {
        console.error('Error saving theme preference:', error);
    }
}