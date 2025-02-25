document.addEventListener('DOMContentLoaded', function() {
    const settings = {
        init() {
            this.tabs = document.querySelectorAll('[data-tab]');
            this.forms = document.querySelectorAll('form');
            this.setupEventListeners();
            this.loadUserSettings();
        },

        setupEventListeners() {
            // Tab switching
            this.tabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.switchTab(e.currentTarget.dataset.tab);
                });
            });

            // Form submissions
            this.forms.forEach(form => {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.saveSettings(e.target);
                });
            });
        },

        switchTab(tabId) {
            // Hide all content sections
            document.querySelectorAll('.card').forEach(card => {
                card.classList.add('hidden');
            });
            
            // Show selected content
            document.getElementById(`${tabId}-settings`).classList.remove('hidden');
            
            // Update active tab
            this.tabs.forEach(tab => {
                tab.classList.remove('active');
                if (tab.dataset.tab === tabId) {
                    tab.classList.add('active');
                }
            });
        },

        async saveSettings(form) {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('/api/settings/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                if (response.ok) {
                    this.showToast('Settings saved successfully', 'success');
                } else {
                    throw new Error('Failed to save settings');
                }
            } catch (error) {
                this.showToast('Failed to save settings', 'error');
                console.error('Settings update error:', error);
            }
        },

        async loadUserSettings() {
            try {
                const response = await fetch('/api/settings/get');
                const settings = await response.json();
                this.populateSettings(settings);
            } catch (error) {
                console.error('Failed to load settings:', error);
            }
        },

        populateSettings(settings) {
            Object.entries(settings).forEach(([key, value]) => {
                const element = document.querySelector(`[name="${key}"]`);
                if (element) {
                    if (element.type === 'checkbox') {
                        element.checked = value;
                    } else {
                        element.value = value;
                    }
                }
            });
        },

        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} fixed bottom-4 right-4 z-50`;
            toast.innerHTML = `<span>${message}</span>`;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    };

    settings.init();
});
