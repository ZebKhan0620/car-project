// Business Hours Status
function updateBusinessStatus() {
    const now = new Date();
    const timezone = 'Asia/Tokyo'; // Set your business timezone
    
    // Convert to business timezone
    const options = { timeZone: timezone, hour: '2-digit', minute: '2-digit', hour12: false };
    const businessTime = now.toLocaleTimeString('en-US', options);
    const currentDay = now.toLocaleDateString('en-US', { timeZone: timezone, weekday: 'lowercase' });
    
    // Business hours configuration
    const businessHours = {
        weekdays: {
            monday: ['09:00-12:00', '13:00-17:00'],
            tuesday: ['09:00-12:00', '13:00-17:00'],
            wednesday: ['09:00-12:00', '13:00-17:00'],
            thursday: ['09:00-12:00', '13:00-17:00'],
            friday: ['09:00-12:00', '13:00-17:00']
        },
        weekend: {
            saturday: ['10:00-12:00', '13:00-15:00'],
            sunday: [] // Closed
        }
    };

    // Holidays
    const holidays = [
        '2024-12-25', // Christmas
        '2024-12-31', // New Year's Eve
        '2025-01-01'  // New Year's Day
    ];

    // Check if today is a holiday
    const currentDate = now.toLocaleDateString('en-US', { timeZone: timezone, year: 'numeric', month: '2-digit', day: '2-digit' })
        .split('/').reverse().join('-');
    const isHoliday = holidays.includes(currentDate);

    function isCurrentlyOpen(currentDay, currentTime) {
        if (isHoliday) return false;

        // Get today's hours
        let todayHours = [];
        if (Object.keys(businessHours.weekdays).includes(currentDay)) {
            todayHours = businessHours.weekdays[currentDay];
        } else {
            todayHours = businessHours.weekend[currentDay];
        }

        // Check each time range
        return todayHours.some(range => {
            const [start, end] = range.split('-');
            const isInRange = currentTime >= start && currentTime < end;
            const isLunchBreak = currentTime >= '12:00' && currentTime < '13:00';
            return isInRange && !isLunchBreak;
        });
    }

    // Update status badge
    const statusBadge = document.querySelector('.business-status-badge');
    if (statusBadge) {
        const isOpen = isCurrentlyOpen(currentDay, businessTime);
        statusBadge.className = `badge ${isOpen ? 'badge-success' : 'badge-error'} ml-2 business-status-badge`;
        statusBadge.textContent = isOpen ? translations.businessHours.open : translations.businessHours.closed;
    }
}

// Update status immediately and then every minute
updateBusinessStatus();
setInterval(updateBusinessStatus, 60000);

// Form Validation
const form = document.getElementById('contactForm');
const messageInput = document.getElementById('message');
const messageLength = document.getElementById('message-length');

// Update message length counter
if (messageInput && messageLength) {
    messageInput.addEventListener('input', () => {
        const length = messageInput.value.length;
        messageLength.textContent = `${length}/3000`;
    });
}

// Form submission handler
if (form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = translations.form.buttons.sending;

        try {
            const formData = new FormData(form);
            const response = await fetch('/contact/submit', {
                method: 'POST',
                body: formData
            });

            if (response.status === 429) {
                showToast(translations.messages.rateLimited, 'error');
                return;
            }

            const result = await response.json();
            
            if (result.success) {
                showToast(translations.messages.success, 'success');
                form.reset();
                updateMessageLength();
            } else {
                showToast(result.message || translations.messages.error, 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            showToast(translations.messages.systemError, 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    });
}

// Form validation
function validateForm() {
    let isValid = true;
    const name = form.querySelector('#name');
    const email = form.querySelector('#email');
    const phone = form.querySelector('#phone');
    const subject = form.querySelector('#subject');
    const message = form.querySelector('#message');
    const contactPurpose = form.querySelector('#contact_purpose');

    // Reset previous errors
    clearErrors();

    // Name validation
    if (!name.value.trim()) {
        showError(name, translations.validation.name.required);
        isValid = false;
    } else if (name.value.length > 100) {
        showError(name, translations.validation.name.maxLength);
        isValid = false;
    }

    // Email validation
    if (!email.value.trim()) {
        showError(email, translations.validation.email.required);
        isValid = false;
    } else if (!isValidEmail(email.value)) {
        showError(email, translations.validation.email.invalid);
        isValid = false;
    }

    // Phone validation (optional)
    if (phone.value.trim() && !isValidPhone(phone.value)) {
        showError(phone, translations.validation.phone.invalid);
        isValid = false;
    }

    // Subject validation
    if (!subject.value.trim()) {
        showError(subject, translations.validation.subject.required);
        isValid = false;
    } else if (subject.value.length > 200) {
        showError(subject, translations.validation.subject.maxLength);
        isValid = false;
    }

    // Message validation
    if (!message.value.trim()) {
        showError(message, translations.validation.message.required);
        isValid = false;
    } else if (message.value.length > 3000) {
        showError(message, translations.validation.message.maxLength);
        isValid = false;
    }

    // Contact purpose validation
    if (!contactPurpose.value || !['general', 'sales', 'support', 'partnership'].includes(contactPurpose.value)) {
        showError(contactPurpose, translations.validation.contactPurpose.invalid);
        isValid = false;
    }

    return isValid;
}

// Helper functions
function showError(element, message) {
    const formControl = element.closest('.form-control');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'label';
    errorDiv.innerHTML = `<span class="label-text-alt text-error" role="alert">${message}</span>`;
    formControl.appendChild(errorDiv);
    element.classList.add(element.type === 'textarea' ? 'textarea-error' : 'input-error');
    element.setAttribute('aria-invalid', 'true');
}

function clearErrors() {
    const errorMessages = form.querySelectorAll('.label-text-alt.text-error');
    errorMessages.forEach(error => error.closest('.label')?.remove());
    
    const errorInputs = form.querySelectorAll('.input-error, .textarea-error');
    errorInputs.forEach(input => {
        input.classList.remove('input-error', 'textarea-error');
        input.setAttribute('aria-invalid', 'false');
    });
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    // Allow various phone formats including international
    const phoneRegex = /^[+]?[(]?[0-9]{1,4}[)]?[-\s.]?[0-9]{1,4}[-\s.]?[0-9]{1,9}$/;
    return phoneRegex.test(phone);
}

function updateMessageLength() {
    if (messageInput && messageLength) {
        messageLength.textContent = `${messageInput.value.length}/3000`;
    }
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 bg-${type} text-white px-6 py-3 rounded-lg shadow-lg transform transition-transform duration-300 ease-in-out z-50`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.style.transform = 'translateY(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Contact purpose selection
function selectPurpose(purpose, title) {
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
}

// Copy address functionality
function copyAddress() {
    const address = document.querySelector('address')?.textContent?.trim();
    if (address) {
        navigator.clipboard.writeText(address).then(() => {
            showToast(translations.messages.addressCopied);
        }).catch(err => {
            console.error('Failed to copy address:', err);
            showToast(translations.messages.error, 'error');
        });
    }
} 