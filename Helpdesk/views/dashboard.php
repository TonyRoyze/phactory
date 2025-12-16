<?php
// Ensure only admins can access this page
if ($_SESSION['user_role'] !== 'ADMIN') {
    header("Location: app.php?view=my_tickets");
    exit();
}

// Get ticket statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_tickets,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tickets,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved_tickets,
        SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_tickets
    FROM tickets
";

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get category breakdown
$category_query = "
    SELECT 
        category,
        COUNT(*) as count,
        SUM(CASE WHEN status IN ('Open', 'In Progress') THEN 1 ELSE 0 END) as active_count
    FROM tickets 
    GROUP BY category
    ORDER BY count DESC
";

$category_result = mysqli_query($conn, $category_query);

// Get priority breakdown
$priority_query = "
    SELECT 
        priority,
        COUNT(*) as count,
        SUM(CASE WHEN status IN ('Open', 'In Progress') THEN 1 ELSE 0 END) as active_count
    FROM tickets 
    GROUP BY priority
    ORDER BY FIELD(priority, 'Urgent', 'High', 'Medium', 'Low')
";

$priority_result = mysqli_query($conn, $priority_query);

// Get recent ticket activity (last 10 activities)
$recent_activity_query = "
    SELECT 
        t.ticket_id,
        t.title,
        t.status,
        t.priority,
        t.last_activity,
        u.full_name as customer_name,
        a.full_name as assigned_name
    FROM tickets t
    LEFT JOIN users u ON t.customer_id = u.user_id
    LEFT JOIN users a ON t.assigned_to = a.user_id
    ORDER BY t.last_activity DESC
    LIMIT 10
";

$recent_activity_result = mysqli_query($conn, $recent_activity_query);

// Get tickets requiring attention (urgent/high priority open tickets)
$attention_query = "
    SELECT 
        t.ticket_id,
        t.title,
        t.priority,
        t.created_at,
        u.full_name as customer_name,
        TIMESTAMPDIFF(HOUR, t.created_at, NOW()) as hours_old
    FROM tickets t
    LEFT JOIN users u ON t.customer_id = u.user_id
    WHERE t.status IN ('Open', 'In Progress') 
    AND t.priority IN ('Urgent', 'High')
    ORDER BY FIELD(t.priority, 'Urgent', 'High'), t.created_at ASC
    LIMIT 5
";

$attention_result = mysqli_query($conn, $attention_query);

// Calculate average response time (time from ticket creation to first admin reply)
$avg_response_query = "
    SELECT 
        AVG(TIMESTAMPDIFF(HOUR, t.created_at, first_reply.created_at)) as avg_response_hours
    FROM tickets t
    INNER JOIN (
        SELECT 
            tr.ticket_id,
            MIN(tr.created_at) as created_at
        FROM ticket_replies tr
        INNER JOIN users u ON tr.author_id = u.user_id
        WHERE u.user_role = 'ADMIN'
        GROUP BY tr.ticket_id
    ) first_reply ON t.ticket_id = first_reply.ticket_id
";

$avg_response_result = mysqli_query($conn, $avg_response_query);
$avg_response = mysqli_fetch_assoc($avg_response_result);
$avg_response_hours = $avg_response['avg_response_hours'] ? round($avg_response['avg_response_hours'], 1) : 0;

?>

<div class="dashboard-container">
    <h1>Admin Dashboard</h1>
    
    <!-- Quick Search Widget -->
    <div class="dashboard-search-section">
        <h3>Quick Search</h3>
        <?php
        $widget_id = 'dashboard-search';
        $placeholder = 'Search all tickets...';
        $compact = false;
        include 'views/components/search_widget.php';
        ?>
    </div>
    
    <!-- Key Metrics Cards -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-number"><?= $stats['total_tickets'] ?></div>
            <div class="metric-label">Total Tickets</div>
        </div>
        
        <div class="metric-card open">
            <div class="metric-number"><?= $stats['open_tickets'] ?></div>
            <div class="metric-label">Open Tickets</div>
        </div>
        
        <div class="metric-card in-progress">
            <div class="metric-number"><?= $stats['in_progress_tickets'] ?></div>
            <div class="metric-label">In Progress</div>
        </div>
        
        <div class="metric-card resolved">
            <div class="metric-number"><?= $stats['resolved_tickets'] ?></div>
            <div class="metric-label">Resolved Tickets</div>
        </div>
        
        <div class="metric-card response-time">
            <div class="metric-number"><?= $avg_response_hours ?>h</div>
            <div class="metric-label">Avg Response Time</div>
        </div>
    </div>

    <div class="dashboard-content">
        <!-- Left Column -->
        <div class="dashboard-left">
            <!-- Category Breakdown -->
            <div class="dashboard-section">
                <h3>Tickets by Category</h3>
                <div class="breakdown-list">
                    <?php while ($category = mysqli_fetch_assoc($category_result)): ?>
                        <div class="breakdown-item">
                            <div class="breakdown-info">
                                <span class="breakdown-label"><?= htmlspecialchars($category['category']) ?></span>
                                <span class="breakdown-count"><?= $category['count'] ?> total</span>
                            </div>
                            <div class="breakdown-active">
                                <?= $category['active_count'] ?> active
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Priority Breakdown -->
            <div class="dashboard-section">
                <h3>Tickets by Priority</h3>
                <div class="breakdown-list">
                    <?php while ($priority = mysqli_fetch_assoc($priority_result)): ?>
                        <div class="breakdown-item">
                            <div class="breakdown-info">
                                <span class="breakdown-label priority-<?= strtolower($priority['priority']) ?>">
                                    <?= htmlspecialchars($priority['priority']) ?>
                                </span>
                                <span class="breakdown-count"><?= $priority['count'] ?> total</span>
                            </div>
                            <div class="breakdown-active">
                                <?= $priority['active_count'] ?> active
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="dashboard-right">
            <!-- Tickets Requiring Attention -->
            <div class="dashboard-section">
                <h3>Tickets Requiring Attention</h3>
                <?php if (mysqli_num_rows($attention_result) > 0): ?>
                    <div class="attention-list">
                        <?php while ($ticket = mysqli_fetch_assoc($attention_result)): ?>
                            <div class="attention-item">
                                <div class="attention-header">
                                    <a href="app.php?view=ticket&id=<?= $ticket['ticket_id'] ?>" class="ticket-link">
                                        #<?= $ticket['ticket_id'] ?> - <?= htmlspecialchars($ticket['title']) ?>
                                    </a>
                                    <span class="priority-badge priority-<?= strtolower($ticket['priority']) ?>">
                                        <?= $ticket['priority'] ?>
                                    </span>
                                </div>
                                <div class="attention-meta">
                                    <span class="customer-name"><?= htmlspecialchars($ticket['customer_name']) ?></span>
                                    <span class="ticket-age"><?= $ticket['hours_old'] ?> hours old</span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data">No urgent tickets requiring immediate attention.</p>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <div class="dashboard-section">
                <h3>Recent Ticket Activity</h3>
                <div class="activity-list">
                    <?php while ($activity = mysqli_fetch_assoc($recent_activity_result)): ?>
                        <div class="activity-item">
                            <div class="activity-header">
                                <a href="app.php?view=ticket&id=<?= $activity['ticket_id'] ?>" class="ticket-link">
                                    #<?= $activity['ticket_id'] ?> - <?= htmlspecialchars($activity['title']) ?>
                                </a>
                                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $activity['status'])) ?>">
                                    <?= $activity['status'] ?>
                                </span>
                            </div>
                            <div class="activity-meta">
                                <span class="customer-name"><?= htmlspecialchars($activity['customer_name']) ?></span>
                                <?php if ($activity['assigned_name']): ?>
                                    <span class="assigned-to">Assigned to: <?= htmlspecialchars($activity['assigned_name']) ?></span>
                                <?php endif; ?>
                                <span class="activity-time"><?= date('M j, Y g:i A', strtotime($activity['last_activity'])) ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>