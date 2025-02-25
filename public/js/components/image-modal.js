document.addEventListener('DOMContentLoaded', function() {
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const imageSizeDisplay = document.getElementById('imageSize');
    let isFullscreen = false;
    let rotation = 0;
    
    // Make functions globally available
    window.openImageModal = function(img) {
        modalImage.src = img.src;
        // Reset states
        isFullscreen = false;
        rotation = 0;
        modalImage.style.transform = '';
        modalImage.classList.remove('!max-h-none', '!max-w-none');
        
        // Show image dimensions when loaded
        modalImage.onload = function() {
            imageSizeDisplay.textContent = `${this.naturalWidth} × ${this.naturalHeight}px`;
            
            // Adjust modal sizing based on image orientation
            const aspectRatio = this.naturalWidth / this.naturalHeight;
            if (aspectRatio < 1) {
                // Portrait image
                modalImage.classList.add('portrait');
                modalImage.style.maxHeight = '85vh';
                modalImage.style.maxWidth = 'min(85vh * ' + aspectRatio + ', 100%)';
            } else {
                // Landscape image
                modalImage.classList.remove('portrait');
                modalImage.style.maxHeight = 'min(85vh, 100%)';
                modalImage.style.maxWidth = '100%';
            }
        };
        
        imageModal.showModal();
    }
    
    window.closeImageModal = function() {
        imageModal.close();
        // Reset states when closing
        rotation = 0;
        isFullscreen = false;
        modalImage.style.transform = '';
        modalImage.classList.remove('!max-h-none', '!max-w-none');
        modalImage.classList.remove('object-cover');
        modalImage.classList.add('object-contain');
    }
    
    window.rotateImage = function() {
        rotation = (rotation + 90) % 360;
        const scale = rotation % 180 === 90 ? 
            modalImage.classList.contains('portrait') ? 0.7 : 0.85 : 
            1;
        
        modalImage.style.transform = `rotate(${rotation}deg) scale(${scale})`;
    }
    
    window.toggleFullscreen = function() {
        isFullscreen = !isFullscreen;
        if (isFullscreen) {
            modalImage.classList.add('!max-h-none', '!max-w-none');
            modalImage.classList.remove('object-contain');
            modalImage.classList.add('object-cover');
            modalImage.style.maxHeight = 'none';
            modalImage.style.maxWidth = 'none';
        } else {
            modalImage.classList.remove('!max-h-none', '!max-w-none');
            modalImage.classList.remove('object-cover');
            modalImage.classList.add('object-contain');
            // Reset to original sizing
            modalImage.onload();
        }
    }
    
    // Add zoom on mouse move when in fullscreen
    modalImage.addEventListener('mousemove', function(e) {
        if (!isFullscreen) return;
        
        const rect = this.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width;
        const y = (e.clientY - rect.top) / rect.height;
        
        this.style.transformOrigin = `${x * 100}% ${y * 100}%`;
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (!imageModal.open) return;
        
        switch(e.key) {
            case 'Escape':
                closeImageModal();
                break;
            case 'f':
                toggleFullscreen();
                break;
            case 'r':
                rotateImage();
                break;
        }
    });
    
    // Close modal on backdrop click
    imageModal.addEventListener('click', (e) => {
        const dialogDimensions = imageModal.getBoundingClientRect();
        if (
            e.clientX < dialogDimensions.left ||
            e.clientX > dialogDimensions.right ||
            e.clientY < dialogDimensions.top ||
            e.clientY > dialogDimensions.bottom
        ) {
            closeImageModal();
        }
    });
}); 