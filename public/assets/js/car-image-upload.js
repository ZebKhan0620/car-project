/**
 * Car Image Upload Configuration
 * Handles image upload, preview, and deletion using Dropzone.js
 */
Dropzone.autoDiscover = false;

document.addEventListener('DOMContentLoaded', function() {
    const uploadedImages = [];
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    
    const myDropzone = new Dropzone("#imageUpload", {
        url: "/car-project/public/Components/Cars/upload-images.php",
        maxFiles: 10,
        acceptedFiles: "image/*",
        addRemoveLinks: false,
        paramName: "file",
        thumbnailWidth: 500,
        thumbnailHeight: 500,
        thumbnailMethod: 'contain',
        resizeWidth: null,
        resizeHeight: null,
        resizeQuality: 1,
        createImageThumbnails: true,
        timeout: 180000, // Increased timeout for large files
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        previewTemplate: `
            <div class="dz-preview relative m-1 inline-block">
                <div class="relative rounded-lg overflow-hidden hover:shadow-lg transition-all duration-300">
                    <!-- Image Preview -->
                    <div class="relative w-40 h-28 bg-base-200">
                        <img data-dz-thumbnail class="w-full h-full object-cover rounded-lg" 
                             style="-webkit-transform: translateZ(0); transform: translateZ(0); backface-visibility: hidden;" />
                        
                        <!-- Upload Progress -->
                        <div class="dz-progress absolute bottom-0 left-0 right-0">
                            <div class="h-0.5 bg-base-100">
                                <div class="dz-upload h-full bg-primary transition-all" 
                                     data-dz-uploadprogress style="width: 0%">
                                </div>
                            </div>
                        </div>

                        <!-- Remove Button -->
                        <button type="button" 
                                class="btn btn-circle btn-xs absolute top-1 right-1 bg-base-100/50 hover:bg-base-100 border-none" 
                                data-dz-remove>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- File Info -->
                    <div class="p-0.5 text-center text-xs">
                        <p class="truncate font-medium text-xs" data-dz-name></p>
                        <p class="opacity-70 text-[10px]" data-dz-size></p>
                        <div class="dz-error-message hidden">
                            <span class="text-[10px] text-error" data-dz-errormessage></span>
                        </div>
                    </div>
                </div>
            </div>
        `,
        dictDefaultMessage: `
            <div class="flex flex-col items-center justify-center p-6">
                <div class="mb-3 p-3 rounded-full bg-base-200">
                    <svg class="w-8 h-8 text-base-content/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-base font-medium">${window.translations?.cars?.vehicle_images?.dropzone?.message || 'Drop files here or click to upload'}</h3>
                <p class="text-xs opacity-70 mt-1">Upload up to 10 images</p>
            </div>
        `,
        accept: function(file, done) {
            if (!ALLOWED_TYPES.includes(file.type)) {
                done(window.translations?.cars?.vehicle_images?.dropzone?.invalid_type || 'Invalid file type. Only JPG, PNG and WebP are allowed.');
                return;
            }
            if (file.size > MAX_FILE_SIZE) {
                done(window.translations?.cars?.vehicle_images?.dropzone?.file_too_big || 'File too large. Maximum size is 5MB.');
                return;
            }
            done();
        },
        init: function() {
            this.on("sending", function(file) {
                return new Promise((resolve) => {
                    console.log("Uploading:", file.name);
                    resolve();
                });
            });
            
            this.on("success", function(file, response) {
                try {
                    const jsonResponse = typeof response === 'string' ? JSON.parse(response) : response;
                    if (jsonResponse.success) {
                        uploadedImages.push(jsonResponse.filename);
                        document.getElementById('uploaded_images').value = uploadedImages.join(',');
                        console.log("Upload successful:", jsonResponse.filename);
                    } else {
                        this.removeFile(file);
                        console.error("Upload failed:", jsonResponse.message);
                        alert(jsonResponse.error);
                    }
                } catch (e) {
                    this.removeFile(file);
                    console.error("Error processing upload response:", e);
                    showToast('error', 'Upload failed: ' + e.message);
                }
            });

            this.on("error", function(file, errorMessage) {
                console.error("Upload error:", errorMessage);
                file.previewElement.classList.add('dz-error');
                
                const errorDisplay = file.previewElement.querySelector('.dz-error-message');
                if (errorDisplay) {
                    errorDisplay.textContent = errorMessage;
                    errorDisplay.classList.remove('hidden');
                }
            });
        },
        removedfile: function(file) {
            return new Promise((resolve) => {
                if (file.xhr) {
                    try {
                        const response = JSON.parse(file.xhr.response);
                        const index = uploadedImages.indexOf(response.filename);
                        if (index > -1) {
                            uploadedImages.splice(index, 1);
                            document.getElementById('uploaded_images').value = uploadedImages.join(',');
                        }
                    } catch (e) {
                        console.error("Remove error:", e);
                    }
                }
                file.previewElement.remove();
                resolve();
            });
        }
    });

    function showToast(type, message) {
        // Add toast notification implementation
        console.log(`${type}: ${message}`);
    }
});