# College Events Management System

## Overview

College Events Management System is a PHP-based platform for managing college events. It allows users to create, view, and RSVP to events. It also includes a commenting system for each event.

## Features

- Create, edit, and delete events
- RSVP to events
- Comment on events
- User authentication and profile management
- Admin panel for user management

## Getting Started

### Prerequisites

- PHP 7.0 or higher
- A web server (e.g., Apache, Nginx)
- MySQL or compatible database

### Installation

1. **Download or clone the repository:**
   - Download the project files to your web server directory
   - Ensure all files are in the correct structure as shown in the File Structure section
2. **Copy files to your web server directory.**

3. **Configure database connection:**
   - Create a `connector.php` file in the project root with the following content:
     ```php
     <?php

     $dbServerName = "localhost";
     $dbUserName = "root";
     $dbPassword = "";
     $dbName = "college_events";

     $conn = mysqli_connect($dbServerName, $dbUserName, $dbPassword, $dbName);

     if ($conn->connect_error) {
         die("Connection Failed" . $conn->connect_error);
     }
     ?>
     ```
   - Update the database credentials in `connector.php` if your setup is different.

4. **Import the database schema:**
    - Import the `college_events.sql` file into your MySQL database.

### Usage

- Access the application via your web browser at `http://localhost/` or your server's URL.
- Register as a user to create events and participate in discussions.
- Admins can manage all users through the admin panel.

## File Structure

- `index.php` - Main entry point (redirects to app.php)
- `app.php` - Main application file
- `views/` - Contains the different views of the application
- `actions/` - Contains the different actions that can be performed
- `connector.php` - Database configuration file
- `college_events.sql` - Database schema
- `.gitignore` - Git ignore rules


