document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('verify2FAForm');
    const useBackupButton = document.getElementById('useBackupCode');
    const csrf_token = document.querySelector('meta[name="csrf-token"]').content;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const code = document.getElementById('code').value;
        
        try {
            const response = await fetch('/api/2fa/verify.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf_token
                },
                body: JSON.stringify({ code })
            });

            const data = await response.json();
            if (data.success) {
                window.location.href = data.redirect || '/dashboard.php';
            } else {
                showAlert(data.error);
            }
        } catch (error) {
            showAlert('Failed to verify code');
        }
    });

    useBackupButton.addEventListener('click', function() {
        const input = document.getElementById('code');
        const label = document.querySelector('.label-text');
        
        if (this.dataset.mode === 'backup') {
            // Switch to regular code mode
            this.textContent = 'Use Backup Code';
            this.dataset.mode = 'regular';
            label.textContent = 'Authentication Code';
            input.pattern = '[0-9]*';
        } else {
            // Switch to backup code mode
            this.textContent = 'Use Authentication Code';
            this.dataset.mode = 'backup';
            label.textContent = 'Backup Code';
            input.pattern = '[a-zA-Z0-9]*';
        }
        
        input.value = '';
        input.focus();
    });

    function showAlert(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-error mb-4';
        alertDiv.textContent = message;
        form.insertBefore(alertDiv, form.firstChild);
        setTimeout(() => alertDiv.remove(), 5000);
    }
}); 