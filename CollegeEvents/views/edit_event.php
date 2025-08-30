<?php
// This file is views/edit_event.php

if (!isset($_SESSION['user_id'])) {
    echo '<h1>Access Denied</h1>';
    exit(); // Use exit() instead of break for included files
}
require 'connector.php';
$event_id = $_GET['id'] ?? 0;
$event_data = null;

if ($event_id > 0) {
    $sql = "SELECT title, description, location, date, user_id FROM events WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $event_data = $result->fetch_assoc();
        // Check if user is author or admin
        if ($event_data['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'ADMIN') {
            echo '<h1>Access Denied</h1>';
            exit(); // Use exit() instead of break for included files
        }
    }
}

if ($event_data) {
    ?>
    <h1>Edit Event: <?= htmlspecialchars($event_data['title']) ?></h1>
    <form action="actions/update_event.php" method="POST" class="post-form">
        <input type="hidden" name="event_id" value="<?php $event_id?>">
        <div class="form-group"> 
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($event_data['title']) ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="10" required><?= htmlspecialchars($event_data['description']) ?></textarea>
        </div>
        <div class="form-group">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?= htmlspecialchars($event_data['location']) ?>" required>
        </div>
        <div class="form-group">
            <label for="date">Date and Time</label>
            <input type="datetime-local" id="date" name="date" value="<?= date('Y-m-d\TH:i', strtotime($event_data['date'])) ?>" required>
        </div>
        <button type="submit">Update Event</button>
    </form>
    <?php
} else {
    echo '<h1>Event not found.</h1>';
}
