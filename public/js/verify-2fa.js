document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('verify2FAForm');
    const alertContainer = document.querySelector('.alert-error');
    const errorMessage = alertContainer?.querySelector('.error-message');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!form || !csrfToken) {
        console.error('Required elements not found');
        return;
    }

    function setLoading(isLoading) {
        const btn = form.querySelector('button[type="submit"]');
        const normalState = btn.querySelector('.normal-state');
        const loadingState = btn.querySelector('.loading-state');
        
        btn.disabled = isLoading;
        normalState.classList.toggle('hidden', isLoading);
        loadingState.classList.toggle('hidden', !isLoading);
    }

    function showError(message) {
        if (alertContainer && errorMessage) {
            errorMessage.textContent = message;
            alertContainer.classList.remove('hidden');
        }
    }

    function hideError() {
        if (alertContainer) {
            alertContainer.classList.add('hidden');
        }
    }

    function validateCode(code) {
        // Check if it's a backup code or regular 2FA code
        const isBackupCode = code.length === 20;
        const isAuthCode = /^\d{6}$/.test(code);
        
        return isBackupCode || isAuthCode;
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        hideError();
        
        const code = document.getElementById('code').value.trim();
        
        if (!validateCode(code)) {
            showError('Please enter a valid verification code');
            return;
        }

        setLoading(true);

        try {
            const response = await fetch('/car-project/public/api/2fa/verify.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ code })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Verification failed');
            }

            if (data.success) {
                showSuccess('Verification successful! Redirecting...');
                setTimeout(() => {
                    window.location.href = '/car-project/public/dashboard.php';
                }, 1500);
            } else {
                throw new Error(data.error || 'Invalid verification code');
            }

        } catch (error) {
            console.error('2FA Verification Error:', error);
            showError(error.message || 'Failed to verify code. Please try again.');
        } finally {
            setLoading(false);
        }
    });

    // Auto-focus the code input field
    const codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.focus();
        
        // Add input validation
        codeInput.addEventListener('input', function(e) {
            const code = e.target.value.trim();
            if (code.length > 0 && !validateCode(code)) {
                e.target.classList.add('input-error');
            } else {
                e.target.classList.remove('input-error');
            }
        });
    }
});
