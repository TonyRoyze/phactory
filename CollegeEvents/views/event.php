<?php
// This file is views/event.php

require 'connector.php';
$event_id = $_GET['id'] ?? 0;

// Fetch the event
$sql_event = "SELECT e.title, e.description, e.location, e.date, u.username FROM events e JOIN users u ON e.user_id = u.id WHERE e.id = ?";
$stmt_event = $conn->prepare($sql_event);
$stmt_event->bind_param("i", $event_id);
$stmt_event->execute();
$result_event = $stmt_event->get_result();

if ($result_event->num_rows == 1) {
    $event = $result_event->fetch_assoc();
    echo '<a href="app.php?view=events">&larr; Back to Events</a>';
    echo '<h1>' . htmlspecialchars($event['title']) . '</h1>';
    echo '<p class="post-meta">By ' . htmlspecialchars($event['username']) . ' on ' . date('F j, Y, g:i a', strtotime($event['date'])) . '</p>';
    echo '<p><strong>Location:</strong> ' . htmlspecialchars($event['location']) . '</p>';
    echo '<div class="post-content">' . nl2br(htmlspecialchars($event['description'])) . '</div>';

    // RSVP button
    if (isset($_SESSION['user_id']) && $event['date'] < date("today")) {
        $sql_rsvp_check = "SELECT * FROM rsvps WHERE event_id = ? AND user_id = ?";
        $stmt_rsvp_check = $conn->prepare($sql_rsvp_check);
        $stmt_rsvp_check->bind_param("ii", $event_id, $_SESSION['user_id']);
        $stmt_rsvp_check->execute();
        $result_rsvp_check = $stmt_rsvp_check->get_result();
        if ($result_rsvp_check->num_rows > 0) {
            echo '<p>You have RSVPed to this event.</p>';
        } else {
            echo '<form action="actions/rsvp.php" method="POST">';
            echo '    <input type="hidden" name="event_id" value="' . htmlspecialchars($event_id) . '">';
            echo '    <button type="submit">RSVP</button>';
            echo '</form>';
        }
    }

    // Fetch RSVPs
    $sql_rsvps = "SELECT u.username FROM rsvps r JOIN users u ON r.user_id = u.id WHERE r.event_id = ?";
    $stmt_rsvps = $conn->prepare($sql_rsvps);
    $stmt_rsvps->bind_param("i", $event_id);
    $stmt_rsvps->execute();
    $result_rsvps = $stmt_rsvps->get_result();

    echo '<h2>RSVPs</h2>';
    if ($result_rsvps->num_rows > 0) {
        echo '<ul>';
        while ($row = $result_rsvps->fetch_assoc()) {
            echo '<li>' . htmlspecialchars($row['username']) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No RSVPs yet.</p>';
    }

    // Fetch comments
    $sql_comments = "SELECT c.comment, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.event_id = ?";
    $stmt_comments = $conn->prepare($sql_comments);
    $stmt_comments->bind_param("i", $event_id);
    $stmt_comments->execute();
    $result_comments = $stmt_comments->get_result();

    // Comments section
    echo '<hr class="post-divider">';
    echo '<h2>Comments</h2>';

    if ($result_comments->num_rows > 0) {
        echo '<div class="replies-list">';
        while($comment = $result_comments->fetch_assoc()) {
            echo '<div class="reply-card">';
            echo '<p class="reply-meta"><strong>' . htmlspecialchars($comment['username']) . '</strong><span class="reply-date"> on ' . date('F j, Y', strtotime($comment['created_at'])) . '</span></p>';
            echo '<p>' . nl2br(htmlspecialchars($comment['comment'])) . '</p>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>No comments yet.</p>';
    }
    
    if (isset($_SESSION['user_id'])) {
        echo '<form action="actions/add_comment.php" method="POST" class="reply-form">';
        echo '    <input type="hidden" name="event_id" value="' . htmlspecialchars($event_id) . '">';
        echo '    <textarea name="comment" placeholder="Write a comment..." required></textarea>';
        echo '    <button type="submit">Post</button>';
        echo '</form>';
    } else {
        echo '<p><a href="login.php">Log in</a> to leave a comment.</p>';
    }

} else {
    echo '<h1>Event not found</h1>';
}
