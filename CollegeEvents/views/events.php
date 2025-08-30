<?php
// This file is views/events.php

echo '<div class="page-header">';
echo '<h1>Events</h1>';
if (isset($_SESSION['user_id'])) {
    echo '<a href="app.php?view=create_event" class="btn-create">New Event</a>';
}
echo '</div>';

require 'connector.php';

// --- Fetch Upcoming Events ---
$sql_upcoming = "SELECT e.id, e.title, e.date, e.location, u.username FROM events e JOIN users u ON e.user_id = u.id WHERE e.date >= NOW() ORDER BY e.date ASC";
$result_upcoming = $conn->query($sql_upcoming);

echo '<h2>Upcoming Events</h2>';
if ($result_upcoming->num_rows > 0) {
    echo '<div class="posts-list">';
    while($row = $result_upcoming->fetch_assoc()) {
        echo '<a href="app.php?view=event&id=' . $row['id'] . '" class="post-card-link">';
        echo '<div class="post-card compact">';
        echo '<div>';
        echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
        echo '<p class="post-meta">' . date('D, M j, Y, g:i a', strtotime($row['date'])) . ' &bull; ' . htmlspecialchars($row['location']) . '</p>';
        echo '</div>';
        // Edit/Delete buttons for author/admin
        if (isset($_SESSION['user_id']) && (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN')) {
            echo '<div class="post-actions">';
            echo '<a href="app.php?view=edit_event&id=' . $row['id'] . '" class="post-action-btn">Edit</a>';
            echo '<a href="actions/delete_event.php?id=' . $row['id'] . '" class="post-action-btn btn-delete" onclick="return confirm(\'Are you sure you want to delete this event?\');">Delete</a>';
            echo '</div>';
        }
        echo '</div>';
        echo '</a>';
    }
    echo '</div>';
} else {
    echo '<p>No upcoming events found.</p>';
}

// --- Fetch Past Events ---
$sql_past = "SELECT e.id, e.title, e.date, e.location, u.username FROM events e JOIN users u ON e.user_id = u.id WHERE e.date < NOW() ORDER BY e.date DESC";
$result_past = $conn->query($sql_past);

echo '<h2 style="margin-top: 40px;">Past Events</h2>';
if ($result_past->num_rows > 0) {
    echo '<div class="posts-list">';
    while($row = $result_past->fetch_assoc()) {
        echo '<a href="app.php?view=event&id=' . $row['id'] . '" class="post-card-link">';
        echo '<div class="post-card compact">';
        echo '<div>';
        echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
        echo '<p class="post-meta">' . date('D, M j, Y, g:i a', strtotime($row['date'])) . ' &bull; ' . htmlspecialchars($row['location']) . '</p>';
        echo '</div>';
        // Edit/Delete buttons for author/admin
        if (isset($_SESSION['user_id']) && (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN')) {
            echo '<div class="post-actions">';
            echo '<a href="app.php?view=edit_event&id=' . $row['id'] . '" class="post-action-btn">Edit</a>';
            echo '<a href="actions/delete_event.php?id=' . $row['id'] . '" class="post-action-btn btn-delete" onclick="return confirm(\'Are you sure you want to delete this event?\');">Delete</a>';
            echo '</div>';
        }
        echo '</div>';
        echo '</a>';
    }
    echo '</div>';
} else {
    echo '<p>No past events found.</p>';
}
