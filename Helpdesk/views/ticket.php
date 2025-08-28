<?php
// This file is views/ticket.php - Individual ticket detail view

require 'connector.php';

// Display success/error messages
if (isset($_SESSION['success'])) {
    echo '<div class="success-message">' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}

// Get ticket ID from URL parameter
$ticket_id = intval($_GET['id'] ?? 0);

if (!$ticket_id) {
    echo '<div class="error-message">Invalid ticket ID.</div>';
    return;
}

// Get ticket details with user information
$sql = "SELECT t.*, 
               customer.full_name as customer_name, customer.email as customer_email,
               assigned.full_name as assigned_name
        FROM tickets t 
        LEFT JOIN users customer ON t.customer_id = customer.user_id
        LEFT JOIN users assigned ON t.assigned_to = assigned.user_id
        WHERE t.ticket_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="error-message">Ticket not found.</div>';
    return;
}

$ticket = $result->fetch_assoc();

// Check access permissions
$can_view = false;
if ($_SESSION['user_role'] === 'ADMIN') {
    $can_view = true; // Admins can view all tickets
} elseif ($_SESSION['user_role'] === 'CUSTOMER' && $ticket['customer_id'] == $_SESSION['user_id']) {
    $can_view = true; // Customers can only view their own tickets
}

if (!$can_view) {
    echo '<div class="error-message">You do not have permission to view this ticket.</div>';
    return;
}

// Get ticket replies with user information
$replies_sql = "SELECT tr.*, u.full_name, u.user_role
                FROM ticket_replies tr
                JOIN users u ON tr.author_id = u.user_id
                WHERE tr.ticket_id = ? 
                AND (tr.is_internal = FALSE OR ? = 'ADMIN')
                ORDER BY tr.created_at ASC";

$replies_stmt = $conn->prepare($replies_sql);
$replies_stmt->bind_param('is', $ticket_id, $_SESSION['user_role']);
$replies_stmt->execute();
$replies_result = $replies_stmt->get_result();

// Get attachments for the ticket
$attachments_sql = "SELECT * FROM attachments WHERE ticket_id = ? ORDER BY created_at ASC";
$attachments_stmt = $conn->prepare($attachments_sql);
$attachments_stmt->bind_param('i', $ticket_id);
$attachments_stmt->execute();
$attachments_result = $attachments_stmt->get_result();

?>

<div class="ticket-detail-container">
    <!-- Ticket Header -->
    <div class="ticket-detail-header">
        <div class="ticket-title-section">
            <h1>#<?= $ticket['ticket_id'] ?> - <?= htmlspecialchars($ticket['title']) ?></h1>
            <div class="ticket-badges">
                <span class="badge priority-<?= strtolower($ticket['priority']) ?>"><?= htmlspecialchars($ticket['priority']) ?></span>
                <span class="badge status-<?= strtolower(str_replace(' ', '-', $ticket['status'])) ?>"><?= htmlspecialchars($ticket['status']) ?></span>
                <span class="badge category-<?= strtolower($ticket['category']) ?>"><?= htmlspecialchars($ticket['category']) ?></span>
            </div>
        </div>
        
        <div class="ticket-actions">
            <?php if ($_SESSION['user_role'] === 'CUSTOMER'): ?>
                <a href="app.php?view=my_tickets" class="btn-back">← Back to My Tickets</a>
            <?php else: ?>
                <a href="app.php?view=tickets" class="btn-back">← Back to All Tickets</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ticket Meta Information -->
    <div class="ticket-meta-section">
        <div class="meta-item">
            <strong>Customer:</strong> <?= htmlspecialchars($ticket['customer_name']) ?> (<?= htmlspecialchars($ticket['customer_email']) ?>)
        </div>
        <div class="meta-item">
            <strong>Created:</strong> <?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?>
        </div>
        <div class="meta-item">
            <strong>Last Activity:</strong> <?= date('M j, Y g:i A', strtotime($ticket['last_activity'])) ?>
        </div>
        <div class="meta-item">
            <strong>Assigned to:</strong> 
            <?php if ($ticket['assigned_name']): ?>
                <?php if ($_SESSION['user_role'] === 'ADMIN' && $ticket['assigned_to'] == $_SESSION['user_id']): ?>
                    <span class="assigned-to-me"><?= htmlspecialchars($ticket['assigned_name']) ?> (You)</span>
                <?php else: ?>
                    <?= htmlspecialchars($ticket['assigned_name']) ?>
                <?php endif; ?>
            <?php else: ?>
                <span class="unassigned">Unassigned</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Admin Controls -->
    <?php if ($_SESSION['user_role'] === 'ADMIN'): ?>
    <div class="admin-controls-section">
        <h3>Admin Controls</h3>
        <div class="admin-controls-grid">
            <!-- Assignment Control -->
            <div class="control-group">
                <h4>Assignment</h4>
                <form action="actions/assign_ticket.php" method="POST" class="inline-form">
                    <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
                    <div class="form-row">
                        <select name="assigned_to" class="form-select">
                            <option value="0">Unassigned</option>
                            <?php
                            // Get all admin users for assignment dropdown
                            $admin_sql = "SELECT user_id, full_name FROM users WHERE user_role = 'ADMIN' ORDER BY full_name";
                            $admin_result = $conn->query($admin_sql);
                            while ($admin = $admin_result->fetch_assoc()):
                            ?>
                                <option value="<?= $admin['user_id'] ?>" <?= ($ticket['assigned_to'] == $admin['user_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($admin['full_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" class="btn-assign">Assign</button>
                    </div>
                </form>
            </div>

            <!-- Status Control -->
            <div class="control-group">
                <h4>Status</h4>
                <form action="actions/update_status.php" method="POST" class="inline-form">
                    <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
                    <div class="form-row">
                        <select name="status" class="form-select">
                            <option value="Open" <?= ($ticket['status'] === 'Open') ? 'selected' : '' ?>>Open</option>
                            <option value="In Progress" <?= ($ticket['status'] === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                            <option value="Resolved" <?= ($ticket['status'] === 'Resolved') ? 'selected' : '' ?>>Resolved</option>
                            <option value="Closed" <?= ($ticket['status'] === 'Closed') ? 'selected' : '' ?>>Closed</option>
                        </select>
                        <button type="submit" class="btn-status">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Original Ticket Content -->
    <div class="ticket-content-section">
        <h3>Original Request</h3>
        <div class="ticket-content">
            <?= nl2br(htmlspecialchars($ticket['description'])) ?>
        </div>
        
        <!-- Original Ticket Attachments -->
        <?php if ($attachments_result->num_rows > 0): ?>
        <div class="attachments-section">
            <h4>Attachments</h4>
            <div class="attachments-list">
                <?php while ($attachment = $attachments_result->fetch_assoc()): ?>
                    <div class="attachment-item">
                        <a href="actions/download_file.php?id=<?= $attachment['attachment_id'] ?>" class="attachment-link">
                            📎 <?= htmlspecialchars($attachment['original_filename']) ?>
                        </a>
                        <span class="attachment-size">(<?= number_format($attachment['file_size'] / 1024, 1) ?> KB)</span>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Replies Section -->
    <?php if ($replies_result->num_rows > 0): ?>
    <div class="replies-section">
        <h3>Conversation History</h3>
        <div class="replies-list">
            <?php while ($reply = $replies_result->fetch_assoc()): ?>
            <div class="reply-item <?= $reply['is_internal'] ? 'internal-note' : 'public-reply' ?>">
                <div class="reply-header">
                    <div class="reply-author">
                        <strong><?= htmlspecialchars($reply['full_name']) ?></strong>
                        <?php if ($reply['user_role'] === 'ADMIN'): ?>
                            <span class="role-badge admin">Support Agent</span>
                        <?php else: ?>
                            <span class="role-badge customer">Customer</span>
                        <?php endif; ?>
                        <?php if ($reply['is_internal']): ?>
                            <span class="internal-badge">Internal Note</span>
                        <?php endif; ?>
                    </div>
                    <div class="reply-date">
                        <?= date('M j, Y g:i A', strtotime($reply['created_at'])) ?>
                    </div>
                </div>
                <div class="reply-content">
                    <?= nl2br(htmlspecialchars($reply['content'])) ?>
                </div>
                
                <!-- Reply Attachments -->
                <?php
                $reply_attachments_sql = "SELECT * FROM attachments WHERE reply_id = ? ORDER BY created_at ASC";
                $reply_attachments_stmt = $conn->prepare($reply_attachments_sql);
                $reply_attachments_stmt->bind_param('i', $reply['reply_id']);
                $reply_attachments_stmt->execute();
                $reply_attachments_result = $reply_attachments_stmt->get_result();
                
                if ($reply_attachments_result->num_rows > 0): ?>
                <div class="reply-attachments">
                    <?php while ($reply_attachment = $reply_attachments_result->fetch_assoc()): ?>
                        <div class="attachment-item">
                            <a href="actions/download_file.php?id=<?= $reply_attachment['attachment_id'] ?>" class="attachment-link">
                                📎 <?= htmlspecialchars($reply_attachment['original_filename']) ?>
                            </a>
                            <span class="attachment-size">(<?= number_format($reply_attachment['file_size'] / 1024, 1) ?> KB)</span>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reply Form -->
    <?php if ($ticket['status'] !== 'Closed'): ?>
    <div class="reply-form-section">
        <h3>Add Reply</h3>
        <form action="actions/add_reply.php" method="POST" enctype="multipart/form-data" class="reply-form">
            <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
            
            <div class="form-group">
                <label for="content">Your Reply <span class="required">*</span></label>
                <textarea name="content" id="content" required placeholder="Enter your reply..."></textarea>
            </div>
            
            <?php if ($_SESSION['user_role'] === 'ADMIN'): ?>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_internal" value="1">
                    Internal Note (only visible to support agents)
                </label>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="attachment">Attach File (optional)</label>
                <input type="file" name="attachment" id="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
                <small class="file-help">Maximum file size: 5MB. Allowed types: images, PDF, Word documents, text files.</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-submit">Add Reply</button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="ticket-closed-notice">
        <p><strong>This ticket is closed.</strong> No further replies can be added.</p>
    </div>
    <?php endif; ?>
</div>

<script>
// Auto-resize textarea
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('content');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    }
});
</script>