<?php
// Ensure user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'CUSTOMER') {
    header("Location: login.php");
    exit();
}

// Handle form submission messages
$success_message = '';
$error_message = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = 'Ticket created successfully! You can view it in your ticket history.';
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'validation':
            $error_message = 'Please fill in all required fields correctly.';
            break;
        case 'upload':
            $error_message = 'File upload failed. Please check file size and type.';
            break;
        case 'database':
            $error_message = 'Database error occurred. Please try again.';
            break;
        default:
            $error_message = 'An error occurred. Please try again.';
    }
}
?>

<div class="page-header">
    <h1>Create New Support Ticket</h1>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success_message) ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($error_message) ?>
    </div>
<?php endif; ?>

<form id="ticketForm" class="ticket-form" action="actions/create_ticket.php" method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label for="title">Ticket Title <span class="required">*</span></label>
        <input type="text" id="title" name="title" required maxlength="255" 
               placeholder="Brief description of your issue">
        <div class="error-message" id="title-error"></div>
    </div>

    <div class="form-group">
        <label for="category">Category <span class="required">*</span></label>
        <select id="category" name="category" required>
            <option value="">Select a category</option>
            <option value="Technical">Technical Support</option>
            <option value="Billing">Billing & Payment</option>
            <option value="General">General Inquiry</option>
        </select>
        <div class="error-message" id="category-error"></div>
    </div>

    <div class="form-group">
        <label for="priority">Priority <span class="required">*</span></label>
        <select id="priority" name="priority" required>
            <option value="">Select priority level</option>
            <option value="Low">Low - General question or minor issue</option>
            <option value="Medium" selected>Medium - Standard support request</option>
            <option value="High">High - Important issue affecting work</option>
            <option value="Urgent">Urgent - Critical issue requiring immediate attention</option>
        </select>
        <div class="error-message" id="priority-error"></div>
    </div>

    <div class="form-group">
        <label for="description">Description <span class="required">*</span></label>
        <textarea id="description" name="description" required rows="6" 
                  placeholder="Please provide detailed information about your issue, including any error messages, steps to reproduce, and what you expected to happen."></textarea>
        <div class="error-message" id="description-error"></div>
        <div class="form-help">Minimum 10 characters required</div>
    </div>

    <div class="form-group">
        <label for="attachments">File Attachments (Optional)</label>
        <input type="file" id="attachments" name="attachments[]" multiple 
               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
        <div class="form-help">
            Supported formats: JPG, PNG, GIF, PDF, DOC, DOCX, TXT<br>
            Maximum file size: 5MB per file<br>
            Maximum 5 files per ticket
        </div>
        <div class="error-message" id="attachments-error"></div>
        <div id="file-preview"></div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary">Create Ticket</button>
        <a href="app.php?view=my_tickets" class="btn-secondary">Cancel</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('ticketForm');
    const titleInput = document.getElementById('title');
    const categorySelect = document.getElementById('category');
    const prioritySelect = document.getElementById('priority');
    const descriptionTextarea = document.getElementById('description');
    const attachmentsInput = document.getElementById('attachments');
    const filePreview = document.getElementById('file-preview');

    // Real-time validation
    titleInput.addEventListener('blur', validateTitle);
    categorySelect.addEventListener('change', validateCategory);
    prioritySelect.addEventListener('change', validatePriority);
    descriptionTextarea.addEventListener('blur', validateDescription);
    attachmentsInput.addEventListener('change', validateAttachments);

    // Form submission validation
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        if (!validateTitle()) isValid = false;
        if (!validateCategory()) isValid = false;
        if (!validatePriority()) isValid = false;
        if (!validateDescription()) isValid = false;
        if (!validateAttachments()) isValid = false;

        if (!isValid) {
            e.preventDefault();
            showError('Please correct the errors above before submitting.');
        }
    });

    function validateTitle() {
        const value = titleInput.value.trim();
        const errorElement = document.getElementById('title-error');
        
        if (value.length === 0) {
            showFieldError(errorElement, 'Title is required');
            return false;
        } else if (value.length < 5) {
            showFieldError(errorElement, 'Title must be at least 5 characters long');
            return false;
        } else if (value.length > 255) {
            showFieldError(errorElement, 'Title must be less than 255 characters');
            return false;
        }
        
        hideFieldError(errorElement);
        return true;
    }

    function validateCategory() {
        const value = categorySelect.value;
        const errorElement = document.getElementById('category-error');
        
        if (!value) {
            showFieldError(errorElement, 'Please select a category');
            return false;
        }
        
        hideFieldError(errorElement);
        return true;
    }

    function validatePriority() {
        const value = prioritySelect.value;
        const errorElement = document.getElementById('priority-error');
        
        if (!value) {
            showFieldError(errorElement, 'Please select a priority level');
            return false;
        }
        
        hideFieldError(errorElement);
        return true;
    }

    function validateDescription() {
        const value = descriptionTextarea.value.trim();
        const errorElement = document.getElementById('description-error');
        
        if (value.length === 0) {
            showFieldError(errorElement, 'Description is required');
            return false;
        } else if (value.length < 10) {
            showFieldError(errorElement, 'Description must be at least 10 characters long');
            return false;
        }
        
        hideFieldError(errorElement);
        return true;
    }

    function validateAttachments() {
        const files = attachmentsInput.files;
        const errorElement = document.getElementById('attachments-error');
        const maxFileSize = 5 * 1024 * 1024; // 5MB
        const maxFiles = 5;
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
                             'application/pdf', 'application/msword', 
                             'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                             'text/plain'];

        if (files.length > maxFiles) {
            showFieldError(errorElement, `Maximum ${maxFiles} files allowed`);
            return false;
        }

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            if (file.size > maxFileSize) {
                showFieldError(errorElement, `File "${file.name}" is too large. Maximum size is 5MB`);
                return false;
            }
            
            if (!allowedTypes.includes(file.type)) {
                showFieldError(errorElement, `File "${file.name}" has an unsupported format`);
                return false;
            }
        }

        hideFieldError(errorElement);
        updateFilePreview(files);
        return true;
    }

    function updateFilePreview(files) {
        filePreview.innerHTML = '';
        
        if (files.length === 0) return;

        const previewContainer = document.createElement('div');
        previewContainer.className = 'file-preview-container';
        
        const title = document.createElement('h4');
        title.textContent = 'Selected Files:';
        previewContainer.appendChild(title);

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileItem = document.createElement('div');
            fileItem.className = 'file-preview-item';
            
            const fileName = document.createElement('span');
            fileName.textContent = file.name;
            
            const fileSize = document.createElement('span');
            fileSize.className = 'file-size';
            fileSize.textContent = ` (${formatFileSize(file.size)})`;
            
            fileItem.appendChild(fileName);
            fileItem.appendChild(fileSize);
            previewContainer.appendChild(fileItem);
        }
        
        filePreview.appendChild(previewContainer);
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function showFieldError(element, message) {
        element.textContent = message;
        element.style.display = 'block';
        element.parentElement.classList.add('has-error');
    }

    function hideFieldError(element) {
        element.textContent = '';
        element.style.display = 'none';
        element.parentElement.classList.remove('has-error');
    }

    function showError(message) {
        // Create or update error alert
        let alertElement = document.querySelector('.alert-error');
        if (!alertElement) {
            alertElement = document.createElement('div');
            alertElement.className = 'alert alert-error';
            form.parentNode.insertBefore(alertElement, form);
        }
        alertElement.textContent = message;
        alertElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>