class meeting_actions {
    static async cancel_meeting(meeting_id) {
        if (!meeting_id) {
            console.error('Missing meeting ID');
            alert('Invalid meeting ID');
            return;
        }

        if (!confirm('Are you sure you want to cancel this meeting?')) {
            return;
        }

        try {
            const response = await fetch('/car-project/public/api/meetings/cancel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ meeting_id: meeting_id }),
                credentials: 'same-origin'
            });

            // First get the raw text
            const text = await response.text();
            let data;
            
            try {
                // Try to parse as JSON
                data = JSON.parse(text);
            } catch (e) {
                console.error('Server response:', text);
                throw new Error('Server returned invalid JSON');
            }

            if (data.success) {
                alert('Meeting cancelled successfully');
                setTimeout(() => window.location.reload(), 500);
            } else {
                throw new Error(data.message || 'Failed to cancel meeting');
            }
        } catch (error) {
            console.error('Cancel meeting error:', error);
            alert('Failed to cancel meeting: ' + error.message);
        }
    }

    static async view_meeting_details(meeting_id) {
        try {
            const response = await fetch(`/car-project/public/api/meetings/details.php?id=${meeting_id}`);
            const result = await response.json();
            
            if (result.success) {
                let modal = document.getElementById('meeting_details_modal');
                if (!modal) {
                    modal = document.createElement('div');
                    modal.id = 'meeting_details_modal';
                    modal.className = 'modal modal-open';
                    document.body.appendChild(modal);
                }

                modal.innerHTML = meeting_actions.render_meeting_details(result.data);
                modal.classList.add('modal-open');
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            alert('Failed to load meeting details: ' + error.message);
        }
    }

    static render_meeting_details(meeting) {
        return `
            <div class="modal-box relative">
                <h3 class="font-bold text-lg mb-4">Meeting Details</h3>
                <div class="py-4">
                    <p class="mb-2"><strong>Date:</strong> ${new Date(meeting.created_at).toLocaleDateString()}</p>
                    <p class="mb-2"><strong>Time:</strong> ${new Date(meeting.created_at).toLocaleTimeString()}</p>
                    <p class="mb-2"><strong>Type:</strong> ${meeting.type}</p>
                    <p class="mb-2"><strong>Status:</strong> ${meeting.status}</p>
                    <p class="mb-2"><strong>Name:</strong> ${meeting.name}</p>
                    <p class="mb-2"><strong>Email:</strong> ${meeting.email}</p>
                    <p class="mb-2"><strong>Phone:</strong> ${meeting.phone}</p>
                    ${meeting.notes ? `<p class="mb-2"><strong>Notes:</strong> ${meeting.notes}</p>` : ''}
                </div>
                <div class="modal-action">
                    <button onclick="document.getElementById('meeting_details_modal').classList.remove('modal-open')" 
                            class="btn">Close</button>
                </div>
            </div>
        `;
    }

    static async reschedule_meeting(meeting_id) {
        try {
            let modal = document.getElementById('reschedule_modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'reschedule_modal';
                modal.className = 'modal';
                modal.innerHTML = `
                    <div class="modal-box">
                        <h3 class="font-bold text-lg">Reschedule Meeting</h3>
                        <div class="py-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Select New Date</span>
                                </label>
                                <input type="date" id="newDate" class="input input-bordered" min="${new Date().toISOString().split('T')[0]}"
                                       onchange="meeting_actions.load_time_slots(this.value)">
                            </div>
                            <div class="form-control mt-4" id="time_slots">
                                <!-- Time slots will be loaded here -->
                            </div>
                        </div>
                        <div class="modal-action">
                            <button onclick="document.getElementById('reschedule_modal').classList.remove('modal-open')" 
                                    class="btn">Cancel</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            modal.classList.add('modal-open');
            modal.dataset.meeting_id = meeting_id;

            await this.load_time_slots(new Date().toISOString().split('T')[0]);
        } catch (error) {
            alert('Failed to open reschedule dialog: ' + error.message);
        }
    }

    static async load_time_slots(date) {
        try {
            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const response = await fetch(`/car-project/public/api/calendar/slots.php?date=${date}&timezone=${timezone}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('Received slots:', result);
            
            if (result.success) {
                if (result.data && Array.isArray(result.data)) {
                    meeting_actions.render_time_slots(result.data);
                } else {
                    throw new Error('Invalid slots data format');
                }
            } else {
                throw new Error(result.error || 'Failed to load time slots');
            }
        } catch (error) {
            console.error('Failed to load time slots:', error);
            alert('Error loading time slots: ' + error.message);
        }
    }

    static render_time_slots(slots) {
        const container = document.getElementById('time_slots');
        if (!container) return;

        const meeting_id = document.getElementById('reschedule_modal').dataset.meeting_id;
        
        container.innerHTML = slots.map(slot => `
            <div class="form-control">
                <label class="label cursor-pointer">
                    <span class="label-text">${slot.time}</span>
                    <input type="radio" name="timeSlot" value="${slot.id}" 
                           class="radio" 
                           onchange="meeting_actions.handle_slot_selection('${meeting_id}', '${slot.id}')"
                           ${!slot.available ? 'disabled' : ''}>
                </label>
            </div>
        `).join('');
    }

    static async handle_slot_selection(meeting_id, slot_id) {
        if (!confirm('Are you sure you want to reschedule this meeting?')) {
            return;
        }

        try {
            const response = await fetch('/car-project/public/api/meetings/reschedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    meeting_id,
                    new_slot_id: slot_id
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success) {
                alert('Meeting rescheduled successfully');
                
                // Close any open modals
                const rescheduleModal = document.getElementById('reschedule_modal');
                if (rescheduleModal) {
                    rescheduleModal.classList.remove('modal-open');
                }

                // Reload the page to show updated meeting data
                window.location.reload();
            } else {
                throw new Error(result.error || 'Failed to reschedule meeting');
            }
        } catch (error) {
            console.error('Reschedule error:', error);
            alert('Failed to reschedule meeting: ' + error.message);
        }
    }

    static async schedule_meeting(form_data) {
        try {
            const meeting_data = {
                type: form_data.type,
                slot_id: form_data.slot_id,
                car_id: form_data.car_id,
                name: form_data.name,
                email: form_data.email,
                phone: form_data.phone,
                timezone: form_data.timezone || 'UTC',
                notes: form_data.notes || '',
                date: form_data.date,
                duration: form_data.duration || 30
            };

            if (!meeting_data.car_id) {
                throw new Error('Car ID is required');
            }

            const response = await fetch('/car-project/public/api/meetings/schedule.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json' 
                },
                body: JSON.stringify(meeting_data)
            });

            const result = await response.json();
            if (result.success) {
                alert('Meeting scheduled successfully');
                window.location.reload();
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Meeting scheduling error:', error);
            alert('Failed to schedule meeting: ' + error.message);
        }
    }
}

// Global functions for onclick handlers
window.cancel_meeting = meeting_actions.cancel_meeting.bind(meeting_actions);
window.reschedule_meeting = meeting_actions.reschedule_meeting.bind(meeting_actions);
window.view_meeting_details = meeting_actions.view_meeting_details.bind(meeting_actions);