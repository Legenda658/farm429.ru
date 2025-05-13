document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const icon = themeToggle.querySelector('i');
    function setTheme(theme) {
        html.setAttribute('data-theme', theme);
        document.cookie = `theme=${theme}; path=/; max-age=31536000`;
        icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }
    const currentTheme = document.cookie.split('; ').find(row => row.startsWith('theme='))?.split('=')[1] || 'light';
    setTheme(currentTheme);
    themeToggle.addEventListener('click', function() {
        const newTheme = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
        setTheme(newTheme);
    });
}); 