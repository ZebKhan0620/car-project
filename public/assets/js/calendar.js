class CarMeetingManager {
    constructor() {
        console.log("CarMeetingManager initializing...");
        this.selectedSlot = null;
        this.slotIdInput = null;
        this.meetingForm = document.getElementById('scheduler-form'); // Move this up
        if (!this.meetingForm) {
            console.error('Meeting form not found');
            return;
        }
        this.errorBoundary = {
            retryCount: 0,
            maxRetries: 3,
            errors: []
        };
        this.init();
    }

    init() {
        this.initializeElements();
        this.setupEventListeners();
        this.loadTimeSlots();
    }

    initializeElements() {
        console.log("Initializing elements...");
        
        this.meetingType = document.getElementById('meetingType');
        this.datePicker = document.getElementById('datePicker');
        this.timeSlotsContainer = document.getElementById('timeSlots');
        
        // Create hidden slot_id input if it doesn't exist
        this.slotIdInput = this.meetingForm.querySelector('[name="slot_id"]');
        if (!this.slotIdInput) {
            this.slotIdInput = document.createElement('input');
            this.slotIdInput.type = 'hidden';
            this.slotIdInput.name = 'slot_id';
            this.meetingForm.appendChild(this.slotIdInput);
        }
        
        const urlParams = new URLSearchParams(window.location.search);
        const carIdFromUrl = urlParams.get('carId');
        console.log("Car ID from URL:", carIdFromUrl);
        
        this.selectedCar = document.getElementById('selectedCar');
        console.log("Selected Car Element:", this.selectedCar);
        
        if (carIdFromUrl && this.selectedCar) {
            this.selectedCar.value = carIdFromUrl;
            this.selectedCar.dataset.carId = carIdFromUrl;
            console.log("Updated car ID from URL parameters");
        }

        this.datePicker.value = new Date().toISOString().split('T')[0];
        this.datePicker.min = new Date().toISOString().split('T')[0];

        this.timezoneSelect = document.getElementById('timezone');
        
        // Define limited timezone options
        const limitedTimezones = [
            { value: 'Asia/Tokyo', label: 'Japan (JST)' },
            { value: 'Asia/Dubai', label: 'Dubai (GST)' },
            { value: 'Asia/Manila', label: 'Philippines (PST)' }
        ];

        // Clear existing options
        this.timezoneSelect.innerHTML = '';
        
        // Add new limited options
        limitedTimezones.forEach(tz => {
            const option = document.createElement('option');
            option.value = tz.value;
            option.textContent = tz.label;
            this.timezoneSelect.appendChild(option);
        });

        // Set default timezone (you can change this to any of the three)
        this.timezoneSelect.value = 'Asia/Tokyo';
    }

    setupEventListeners() {
        this.datePicker.addEventListener('change', () => this.loadTimeSlots());
        
        this.meetingForm.addEventListener('submit', (e) => this.handleMeetingSubmit(e));
        
        this.meetingType.addEventListener('change', () => {
            this.selectedSlot = null;
            this.loadTimeSlots();
        });
    }

    async loadTimeSlots() {
        const date = this.datePicker.value;
        
        try {
            if (this.errorBoundary.retryCount >= this.errorBoundary.maxRetries) {
                throw new Error('Maximum retry attempts reached');
            }

            // Add timezone handling
            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const response = await this.fetchWithTimeout(`../../api/calendar/slots.php?date=${date}&timezone=${timezone}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            this.validateSlotData(result);
            this.renderTimeSlots(result.data);
            
            // Reset error count on success
            this.errorBoundary.retryCount = 0;

        } catch (error) {
            this.errorBoundary.retryCount++;
            this.errorBoundary.errors.push(error);
            this.handleError(error);
        }
    }

    validateSlotData(result) {
        if (!result.success || !Array.isArray(result.data)) {
            throw new Error('Invalid slot data received');
        }
    }

    async fetchWithTimeout(url, options = {}) {
        const timeout = 5000;
        const controller = new AbortController();
        const id = setTimeout(() => controller.abort(), timeout);

        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal
            });
            clearTimeout(id);
            return response;
        } catch (error) {
            clearTimeout(id);
            throw new Error(error.name === 'AbortError' ? 'Request timeout' : error.message);
        }
    }

    handleError(error) {
        console.error('Calendar Error:', error);
        this.showError(error.message);
        
        // Log errors if they persist
        if (this.errorBoundary.errors.length >= 3) {
            // Send error log to server
            this.logErrors();
        }
    }

    renderTimeSlots(slots) {
        this.timeSlotsContainer.innerHTML = '<p class="text-sm text-base-content mb-2">* Please select a time slot</p>';
        
        if (!slots.length) {
            this.timeSlotsContainer.innerHTML += '<p class="text-center text-gray-500">No available slots</p>';
            return;
        }

        slots.forEach(slot => {
            const button = document.createElement('button');
            button.className = 'btn btn-outline btn-sm w-full mb-2';
            button.textContent = slot.time;
            button.type = 'button'; // Add this to prevent form submission
            button.dataset.slotId = slot.id;

            if (!slot.available) {
                button.disabled = true;
                button.classList.add('btn-disabled');
            } else {
                button.addEventListener('click', (e) => {
                    e.preventDefault(); // Prevent any form submission
                    this.selectTimeSlot(slot);
                });
            }

            this.timeSlotsContainer.appendChild(button);
        });
    }

    selectTimeSlot(slot) {
        // Add validation check
        if (!slot.available) {
            this.showError('This time slot is not available');
            return false;
        }

        // Clear previous selection
        const buttons = this.timeSlotsContainer.querySelectorAll('button');
        buttons.forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline');
        });

        // Update button state
        const selectedButton = this.timeSlotsContainer.querySelector(`[data-slot-id="${slot.id}"]`);
        if (selectedButton) {
            selectedButton.classList.remove('btn-outline');
            selectedButton.classList.add('btn-primary');
        }

        // Update state and form
        this.selectedSlot = slot;
        this.slotIdInput.value = slot.id;

        return true;
    }

    async handleMeetingSubmit(event) {
        event.preventDefault();
        
        try {
            if (!this.selectedSlot) {
                throw new Error('Please select a time slot');
            }

            if (!this.selectedSlot.available) {
                throw new Error('Selected time slot is not available');
            }

            const formData = this.getFormData();
            console.log("Form data being sent:", formData);

            // Validate required fields
            const requiredFields = ['type', 'date', 'slot_id', 'name', 'email', 'car_id'];
            const missingFields = requiredFields.filter(field => !formData[field]);
            
            if (missingFields.length > 0) {
                throw new Error(`Missing required fields: ${missingFields.join(', ')}`);
            }

            const response = await fetch('/car-project/public/api/meetings/schedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Failed to schedule meeting');
            }

            // Success - redirect
            const carId = this.selectedCar?.dataset?.carId;
            window.location.href = `/car-project/public/Components/Cars/view.php?id=${carId}&scheduled=true&type=${formData.type}`;

        } catch (error) {
            console.error('Error scheduling meeting:', error);
            this.showError(error.message);
        }
    }

    async cancelMeeting(meetingId) {
        try {
            const response = await fetch('../../api/meetings/cancel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ meetingId })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Meeting cancelled successfully');
                this.loadTimeSlots();
            } else {
                throw new Error(result.error || 'Failed to cancel meeting');
            }
        } catch (error) {
            console.error('Error cancelling meeting:', error);
            this.showError(error.message || 'Failed to cancel meeting');
        }
    }

    async rescheduleMeeting(meetingId) {
        try {
            const response = await fetch('../../api/meetings/reschedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    meetingId,
                    slotId: this.selectedSlot.id
                })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Meeting rescheduled successfully');
                this.loadTimeSlots();
            } else {
                throw new Error(result.error || 'Failed to reschedule meeting');
            }
        } catch (error) {
            console.error('Error rescheduling meeting:', error);
            this.showError(error.message || 'Failed to reschedule meeting');
        }
    }

    showError(message) {
        alert(message);
    }

    showSuccess(message) {
        alert(message);
    }

    convertToLocalTime(dateTimeStr) {
        const date = new Date(dateTimeStr);
        return date.toLocaleString();
    }

    getFormData() {
        if (!this.selectedSlot || !this.selectedSlot.id) {
            throw new Error('Please select a time slot');
        }

        const formData = new FormData(this.meetingForm);
        const data = {
            type: this.meetingType.value,
            date: this.datePicker.value,
            slot_id: this.selectedSlot.id,
            timezone: this.timezoneSelect.value,
            name: formData.get('name'),
            email: formData.get('email'),
            phone: formData.get('phone') || '',
            notes: formData.get('notes') || '',
            car_id: this.selectedCar?.value || new URLSearchParams(window.location.search).get('carId') // Fixed this line
        };

        console.log("Form data being sent:", data);
        return data;
    }
}

// Initialize only when DOM is ready and form exists
document.addEventListener('DOMContentLoaded', () => {
    const schedulerForm = document.getElementById('scheduler-form');
    if (schedulerForm) {
        console.log("Creating CarMeetingManager instance...");
        carMeetingManagerInstance = new CarMeetingManager();
        window.carMeetingManager = carMeetingManagerInstance;
    } else {
        console.error('Scheduler form not found');
    }
});