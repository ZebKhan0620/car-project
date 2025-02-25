document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('scheduleForm');
    if (!form) return;

    const dateInput = form.querySelector('input[name="meeting_date"]');
    const timeSlotSelect = form.querySelector('select[name="time_slot"]');
    const meetingTypeSelect = form.querySelector('select[name="meeting_type"]');

    // Handle date selection
    dateInput.addEventListener('change', async function() {
        const date = this.value;
        const meetingType = meetingTypeSelect.value;
        
        if (!date || !meetingType) {
            timeSlotSelect.disabled = true;
            return;
        }

        try {
            const response = await fetch(`/car-project/public/Components/Cars/get-available-slots.php?date=${date}&type=${meetingType}`);
            const slots = await response.json();

            timeSlotSelect.innerHTML = '';
            timeSlotSelect.disabled = false;

            // Add default option
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select a time slot';
            timeSlotSelect.appendChild(defaultOption);

            // Add available slots
            slots.forEach(slot => {
                const option = document.createElement('option');
                option.value = slot.id;
                option.textContent = slot.time;
                timeSlotSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Error fetching time slots:', error);
            timeSlotSelect.innerHTML = '<option value="">Error loading time slots</option>';
        }
    });

    // Handle form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Scheduling...';

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Redirect to car view page with success message
                window.location.href = `/car-project/public/Components/Cars/view.php?id=${formData.get('car_id')}&scheduled=true&type=${formData.get('meeting_type')}`;
            } else {
                throw new Error(result.error || 'Failed to schedule meeting');
            }
        } catch (error) {
            console.error('Scheduling error:', error);
            alert('Failed to schedule meeting. Please try again.');
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    });
}); 