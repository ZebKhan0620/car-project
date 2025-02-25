document.addEventListener('DOMContentLoaded', function() {
    window.copyLink = function() {
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Link copied to clipboard!');
        });
    }
}); 