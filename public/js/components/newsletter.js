document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('newsletter-form');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const emailInput = this.querySelector('input[type=email]');
            const submitButton = this.querySelector('button[type=submit]');
            const originalButtonText = submitButton.innerHTML;
            
            try {
                // Disable form while submitting
                emailInput.disabled = true;
                submitButton.disabled = true;
                submitButton.innerHTML = 'Subscribing...';
                
                // Get the base URL from the global variable
                const baseUrl = window.baseUrl || '/car-project/public';
                const url = `${baseUrl}/api/newsletter/subscribe.php`;
                
                console.log('Submitting to:', url);
                console.log('Email:', emailInput.value);
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: emailInput.value.trim()
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Show success message
                    emailInput.value = '';
                    showToast('success', data.message);
                } else {
                    throw new Error(data.message || 'Failed to subscribe');
                }
            } catch (error) {
                console.error('Newsletter subscription error:', error);
                showToast('error', error.message || 'Failed to subscribe. Please try again.');
            } finally {
                // Re-enable form
                emailInput.disabled = false;
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }
});

function showToast(type, message) {
    // Check if we have a toast container, if not create one
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container fixed bottom-4 right-4 z-50';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast mb-2 ${type === 'success' ? 'alert-success' : 'alert-error'} alert shadow-lg`;
    
    // Add toast content
    toast.innerHTML = `
        <div class="flex items-center">
            <span>${message}</span>
            <button class="btn btn-circle btn-xs ml-2" onclick="this.parentElement.parentElement.remove()">✕</button>
        </div>
    `;
    
    // Add to container
    toastContainer.appendChild(toast);
    
    // Remove after 5 seconds
    setTimeout(() => {
        toast.remove();
    }, 5000);
} 