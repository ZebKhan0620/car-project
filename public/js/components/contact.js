document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const purposeCards = document.querySelectorAll('[data-purpose]');
    const messageTextarea = document.getElementById('message');
    const messageLengthDisplay = document.getElementById('message-length');
    let submitting = false;
    
    // Initialize AOS animations for contact cards
    if (typeof AOS !== 'undefined') {
        AOS.refresh();
    }

    // Update character count
    function updateCharCount() {
        const length = messageTextarea.value.length;
        messageLengthDisplay.textContent = `${length}/3000`;
        
        if (length > 3000) {
            messageLengthDisplay.classList.add('text-error');
        } else {
            messageLengthDisplay.classList.remove('text-error');
        }
    }

    // Initialize character count
    if (messageTextarea && messageLengthDisplay) {
        messageTextarea.addEventListener('input', updateCharCount);
        updateCharCount();
    }

    // Handle purpose card selection
    window.selectPurpose = function(purpose, title) {
        const contactPurpose = document.getElementById('contact_purpose');
        const subject = document.getElementById('subject');
        
        if (contactPurpose && subject) {
            contactPurpose.value = purpose;
            subject.value = title;
            
            // Remove active state from all cards
            document.querySelectorAll('[data-purpose]').forEach(card => {
                card.classList.remove('ring', 'ring-primary', 'ring-2');
            });
            
            // Add active state to selected card
            const selectedCard = document.querySelector(`[data-purpose="${purpose}"]`);
            if (selectedCard) {
                selectedCard.classList.add('ring', 'ring-primary', 'ring-2');
            }
        }
    };
    
    if (contactForm) {
        // Form submission handler
        contactForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (submitting) return;
            submitting = true;

            // Client-side validation
            const isValid = validateForm();
            if (!isValid) {
                submitting = false;
                return;
            }

            // Get the submit button and show loading state
            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <span class="loading loading-spinner"></span>
                ${translations.form.buttons.sending}
            `;

            try {
                // Collect form data
                const formData = new FormData(contactForm);
                formData.append('contact_submit', '1');
                
                // Send the form data
                const response = await fetch(window.location.origin + '/car-project/public/Components/Contact/submit.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                let result;
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    result = await response.json();
                } else {
                    const text = await response.text();
                    throw new Error('Invalid response format. Server returned: ' + text);
                }

                if (!response.ok) {
                    if (response.status === 403) {
                        window.location.reload();
                        return;
                    } else if (response.status === 429) {
                        showNotification('error', translations.messages.rate_limit);
                        return;
                    } else if (response.status === 400) {
                        handleValidationErrors(result.errors);
                        return;
                    }
                    throw new Error(result.message || translations.messages.system_error);
                }

                if (result.success) {
                    showNotification('success', translations.messages.success);
                    contactForm.reset();
                    purposeCards.forEach(card => {
                        card.classList.remove('ring', 'ring-primary', 'ring-2');
                    });
                    updateCharCount();
                } else {
                    handleValidationErrors(result.errors);
                }
            } catch (error) {
                console.error('Submission Error:', error);
                showNotification('error', translations.messages.error);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                submitting = false;
            }
        });

        // Form validation function
        function validateForm() {
            let isValid = true;
            const name = contactForm.querySelector('[name="name"]');
            const email = contactForm.querySelector('[name="email"]');
            const phone = contactForm.querySelector('[name="phone"]');
            const subject = contactForm.querySelector('[name="subject"]');
            const message = contactForm.querySelector('[name="message"]');

            // Clear previous error styles and messages
            contactForm.querySelectorAll('.validation-error').forEach(el => el.remove());
            [name, email, phone, subject, message].forEach(field => {
                if (field) {
                    field.classList.remove('input-error', 'textarea-error');
                }
            });

            // Validate name
            if (!name.value.trim()) {
                name.classList.add('input-error');
                showFieldError(name, translations.validation.name.required);
                isValid = false;
            } else if (name.value.length > 100) {
                name.classList.add('input-error');
                showFieldError(name, translations.validation.name.maxLength);
                isValid = false;
            }

            // Validate email
            if (!email.value.trim()) {
                email.classList.add('input-error');
                showFieldError(email, translations.validation.email.required);
                isValid = false;
            } else if (!isValidEmail(email.value.trim())) {
                email.classList.add('input-error');
                showFieldError(email, translations.validation.email.invalid);
                isValid = false;
            }

            // Validate phone (optional)
            if (phone && phone.value.trim() && !isValidPhone(phone.value.trim())) {
                phone.classList.add('input-error');
                showFieldError(phone, translations.validation.phone.invalid);
                isValid = false;
            }

            // Validate subject
            if (!subject.value.trim()) {
                subject.classList.add('input-error');
                showFieldError(subject, translations.validation.subject.required);
                isValid = false;
            } else if (subject.value.length > 200) {
                subject.classList.add('input-error');
                showFieldError(subject, translations.validation.subject.maxLength);
                isValid = false;
            }

            // Validate message
            if (!message.value.trim()) {
                message.classList.add('textarea-error');
                showFieldError(message, translations.validation.message.required);
                isValid = false;
            } else if (message.value.length > 3000) {
                message.classList.add('textarea-error');
                showFieldError(message, translations.validation.message.maxLength);
                isValid = false;
            }

            if (!isValid) {
                const firstError = contactForm.querySelector('.input-error, .textarea-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }

            return isValid;
        }

        // Show field-specific error
        function showFieldError(field, message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'validation-error label-text-alt text-error mt-1';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
            field.setAttribute('aria-invalid', 'true');
        }

        // Handle server validation errors
        function handleValidationErrors(errors) {
            Object.keys(errors).forEach(field => {
                const element = contactForm.querySelector(`[name="${field}"]`);
                if (element) {
                    showFieldError(element, errors[field]);
                }
            });
        }

        // Email validation
        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        // Phone validation
        function isValidPhone(phone) {
            return /^[0-9\-\(\)\/\+\s]*$/.test(phone);
        }

        // Show notification
        function showNotification(type, message) {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} fixed bottom-4 right-4 z-50 max-w-md transform translate-y-full transition-transform duration-300 shadow-lg`;
            toast.innerHTML = `
                <span class="text-sm">${message}</span>
                <button class="btn btn-ghost btn-xs" onclick="this.parentElement.remove()">×</button>
            `;
            document.body.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.style.transform = 'translateY(0)';
            }, 100);

            // Auto dismiss
            setTimeout(() => {
                toast.style.transform = 'translateY(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // Real-time validation
        contactForm.querySelectorAll('input, textarea').forEach(field => {
            field.addEventListener('input', function() {
                this.classList.remove('input-error', 'textarea-error');
                const errorMessage = this.parentNode.querySelector('.validation-error');
                if (errorMessage) {
                    errorMessage.remove();
                }
            });
        });
    }

    // Update business hours status
    function updateBusinessStatus() {
        const statusBadge = document.querySelector('.business-status-badge');
        if (statusBadge) {
            const now = new Date();
            const hours = now.getHours();
            const minutes = now.getMinutes();
            const day = now.getDay();
            
            // Simple business hours check (Mon-Fri: 9-17, Sat: 10-15, Sun: closed)
            let isOpen = false;
            
            if (day >= 1 && day <= 5) { // Mon-Fri
                isOpen = (hours >= 9 && hours < 17) && !(hours === 12); // Closed for lunch at 12
            } else if (day === 6) { // Saturday
                isOpen = (hours >= 10 && hours < 15) && !(hours === 12); // Closed for lunch at 12
            }
            
            statusBadge.className = `badge ${isOpen ? 'badge-success' : 'badge-error'} ml-2 business-status-badge`;
            statusBadge.textContent = isOpen ? translations.businessHours.open : translations.businessHours.closed;
        }
    }

    // Update status immediately and then every minute
    updateBusinessStatus();
    setInterval(updateBusinessStatus, 60000);

    // Copy address function
    window.copyAddress = function() {
        const address = document.querySelector('address').textContent.trim();
        navigator.clipboard.writeText(address).then(() => {
            showNotification('success', translations.messages.addressCopied);
        }).catch(err => {
            console.error('Failed to copy address:', err);
        });
    };
}); 