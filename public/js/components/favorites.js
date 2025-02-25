document.addEventListener('DOMContentLoaded', function() {
    window.toggleFavorite = async function(listingId) {
        try {
            const response = await fetch('/car-project/public/Components/Cars/toggle-favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `listing_id=${listingId}`
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            
            if (data.success) {
                const btn = document.querySelector(`.favorite-btn[data-listing-id="${listingId}"]`);
                if (!btn) return;

                const icon = btn.querySelector('svg');
                
                if (data.action === 'added') {
                    icon.classList.add('text-red-500', 'fill-current');
                } else {
                    icon.classList.remove('text-red-500', 'fill-current');
                    
                    // If we're on the favorites page, remove the card
                    if (window.location.pathname.includes('favorites.php')) {
                        const card = btn.closest('.card');
                        if (card) {
                            card.style.opacity = '0';
                            setTimeout(() => {
                                card.remove();
                                // Check if there are any cards left
                                const remainingCards = document.querySelectorAll('.card').length;
                                if (remainingCards === 0) {
                                    location.reload();
                                }
                            }, 300);
                        }
                    }
                }

                // Show toast message
                showToast(data.message, data.success ? 'success' : 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('An error occurred', 'error');
        }
    };

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = 'toast toast-end';
        toast.innerHTML = `
            <div class="alert alert-${type}">
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // Initialize favorite buttons with active state
    document.querySelectorAll('.favorite-btn').forEach(btn => {
        const listingId = btn.dataset.listingId;
        const icon = btn.querySelector('svg');
        
        // Check if this listing is in favorites
        if (window.favorites && window.favorites.includes(listingId)) {
            icon.classList.add('text-red-500', 'fill-current');
            btn.classList.add('active');
        }
    });
});