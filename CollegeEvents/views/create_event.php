<?php
// This file is views/create_event.php

if (!isset($_SESSION['user_id'])) {
    echo '<h1>Access Denied</h1>';
    exit(); // Use exit() instead of break for included files
}
?>
<h1>Create a New Event</h1>
<form action="actions/create_event.php" method="POST" class="post-form">
    <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required>
    </div>
    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="10" required></textarea>
    </div>
    <div class="form-group">
        <label for="location">Location</label>
        <input type="text" id="location" name="location" required>
    </div>
    <div class="form-group">
        <label for="date">Date and Time</label>
        <input type="datetime-local" id="date" name="date" required>
    </div>
    <button type="submit">Create Event</button>
</form>
