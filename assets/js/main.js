/**
 * College Complaint Management System
 * Vanilla JavaScript Utilities
 * 
 * Concept: Lightweight, pure JavaScript to enhance user experience without
 * heavy external libraries.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Image Preview Handler for Complaint Submission & Edit
    const imageInput = document.getElementById('complaint_image_input');
    const imagePreviewContainer = document.getElementById('image_preview_container');
    const imagePreview = document.getElementById('image_preview');

    if (imageInput && imagePreview && imagePreviewContainer) {
        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                // Check if file is an image
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file (PNG, JPG, JPEG, WEBP).');
                    this.value = '';
                    imagePreviewContainer.classList.add('d-none');
                    return;
                }

                // Check file size (2MB = 2097152 bytes)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Image file size must be less than 2 MB.');
                    this.value = '';
                    imagePreviewContainer.classList.add('d-none');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                imagePreviewContainer.classList.add('d-none');
            }
        });
    }

    // 2. Character Counter for Description Textarea
    const descTextarea = document.getElementById('complaint_description');
    const charCounter = document.getElementById('char_counter');
    if (descTextarea && charCounter) {
        const updateCounter = () => {
            const currentLen = descTextarea.value.length;
            charCounter.textContent = `${currentLen} characters entered`;
        };
        descTextarea.addEventListener('input', updateCounter);
        updateCounter();
    }

    // 3. Auto dismiss flash alerts after 6 seconds
    const flashAlerts = document.querySelectorAll('.alert-dismissible');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });
});

/**
 * Standard confirmation helper for destructive actions (Delete, Reject)
 * 
 * @param {string} message 
 * @returns {boolean}
 */
function confirmAction(message) {
    return confirm(message || 'Are you sure you want to proceed with this action?');
}
