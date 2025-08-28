/**
 * File Upload Utility Functions
 * Provides AJAX file upload functionality for the helpdesk system
 */

class FileUploadHandler {
    constructor() {
        this.maxFileSize = 5 * 1024 * 1024; // 5MB
        this.allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];
        this.allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
    }

    /**
     * Validate a file before upload
     */
    validateFile(file) {
        const errors = [];

        // Check file size
        if (file.size > this.maxFileSize) {
            errors.push(`File "${file.name}" is too large. Maximum size is 5MB.`);
        }

        // Check file type
        if (!this.allowedTypes.includes(file.type)) {
            errors.push(`File "${file.name}" has an unsupported format.`);
        }

        // Check file extension as additional security
        const extension = file.name.split('.').pop().toLowerCase();
        if (!this.allowedExtensions.includes(extension)) {
            errors.push(`File "${file.name}" has an unsupported extension.`);
        }

        return {
            valid: errors.length === 0,
            errors: errors
        };
    }

    /**
     * Upload file via AJAX
     */
    async uploadFile(file, ticketId, replyId = null) {
        const validation = this.validateFile(file);
        if (!validation.valid) {
            throw new Error(validation.errors.join('\n'));
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('ticket_id', ticketId);
        if (replyId) {
            formData.append('reply_id', replyId);
        }

        try {
            const response = await fetch('actions/upload_file.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Upload failed');
            }

            return result;
        } catch (error) {
            throw new Error(`Upload failed: ${error.message}`);
        }
    }

    /**
     * Format file size for display
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Create file preview element
     */
    createFilePreview(file) {
        const previewDiv = document.createElement('div');
        previewDiv.className = 'file-preview-item';

        const fileName = document.createElement('span');
        fileName.className = 'file-name';
        fileName.textContent = file.name;

        const fileSize = document.createElement('span');
        fileSize.className = 'file-size';
        fileSize.textContent = ` (${this.formatFileSize(file.size)})`;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-file-btn';
        removeBtn.textContent = '×';
        removeBtn.title = 'Remove file';

        previewDiv.appendChild(fileName);
        previewDiv.appendChild(fileSize);
        previewDiv.appendChild(removeBtn);

        return previewDiv;
    }

    /**
     * Show upload progress
     */
    showUploadProgress(container, fileName) {
        const progressDiv = document.createElement('div');
        progressDiv.className = 'upload-progress';
        progressDiv.innerHTML = `
            <div class="upload-progress-bar">
                <div class="upload-progress-fill"></div>
            </div>
            <span class="upload-progress-text">Uploading ${fileName}...</span>
        `;
        container.appendChild(progressDiv);
        return progressDiv;
    }

    /**
     * Hide upload progress
     */
    hideUploadProgress(progressElement) {
        if (progressElement && progressElement.parentNode) {
            progressElement.parentNode.removeChild(progressElement);
        }
    }

    /**
     * Show upload success message
     */
    showUploadSuccess(container, fileName) {
        const successDiv = document.createElement('div');
        successDiv.className = 'upload-success';
        successDiv.innerHTML = `
            <span class="upload-success-icon">✓</span>
            <span class="upload-success-text">${fileName} uploaded successfully</span>
        `;
        container.appendChild(successDiv);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.parentNode.removeChild(successDiv);
            }
        }, 3000);
    }

    /**
     * Show upload error message
     */
    showUploadError(container, message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'upload-error';
        errorDiv.innerHTML = `
            <span class="upload-error-icon">✗</span>
            <span class="upload-error-text">${message}</span>
        `;
        container.appendChild(errorDiv);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 5000);
    }
}

// Initialize global file upload handler
window.fileUploadHandler = new FileUploadHandler();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FileUploadHandler;
}