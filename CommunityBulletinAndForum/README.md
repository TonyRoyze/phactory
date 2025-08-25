# Community Bulletin & Forum

## Overview

Community Bulletin & Forum is a PHP-based platform for community interaction and information sharing. It allows community members to create bulletin posts, start forum discussions, and engage in conversations across different categories.

## Features

- Create bulletin posts for announcements, events, and marketplace items
- Start and participate in forum discussions
- Organize content by categories (General, Events, Marketplace, Discussions)
- User authentication and profile management
- Admin panel for content and user management
- Reply system for forum topics
- Simple and clean community-focused interface

## Getting Started

### Prerequisites

- PHP 7.0 or higher
- A web server (e.g., Apache, Nginx)
- MySQL or compatible database (if using database features)

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
     $dbName = "community_bulletin_db";

     $conn = mysqli_connect($dbServerName, $dbUserName, $dbPassword, $dbName);

     if ($conn->connect_error) {
         die("Connection Failed" . $conn->connect_error);
     }
     ?>
     ```
   - Update the database credentials in `connector.php` if your setup is different.

4. **Set appropriate permissions:**
   - Ensure the web server can write to any upload or cache directories if used.

### Usage

- Access the application via your web browser at `http://localhost/8000` or your server's URL.
- Register as a community member to create posts and participate in discussions.
- Admins can manage all content and users through the admin panel.

## File Structure

- `index.php` - Main entry point (redirects to community home)
- `home/community.php` - Community bulletin and forum home page
- `home/forum-topic.php` - Individual forum topic view with replies
- `writer/` - Community member interface for creating and managing posts
- `admin/` - Administrative interface for content and user management
- `connector.php` - Database configuration file
- `community_bulletin_db.sql` - Database schema
- `.gitignore` - Git ignore rules

## Categories

The platform supports four main content categories:
- **General**: Community announcements and general information
- **Events**: Community events, meetings, and activities
- **Marketplace**: Buy/sell/trade items within the community
- **Discussions**: Open discussions and questions

## Design Notes

The platform uses a modern, image-free design with:
- Gradient backgrounds instead of image dependencies
- Category-based color coding for visual organization
- Clean, text-focused content presentation
- Responsive design that works on all devices


