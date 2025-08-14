# NewsSystem

## Overview

NewsSystem is a simple PHP-based application for managing and displaying news articles. It allows users to add, edit, and view news items in a user-friendly interface.

## Features

- Add, edit, and delete news articles
- List all news articles
- View individual news articles
- Simple and clean interface

## Getting Started

### Prerequisites

- PHP 7.0 or higher
- A web server (e.g., Apache, Nginx)
- MySQL or compatible database (if using database features)

### Installation

1. **Clone the repository:**
      ### For Linux (Ubuntu/Debian):

   ```bash
   # Install SVN if not already installed
   sudo apt-get update
   sudo apt-get install subversion

   # Checkout the repository
   svn checkout https://github.com/TonyRoyze/phactory

   # To get only a specific folder from the repository, use:
   svn checkout https://github.com/TonyRoyze/phactory/NewsSystem
   ```

   ### For Windows:

   1. Download and install [TortoiseSVN](https://tortoisesvn.net/downloads.html) or [SlikSVN](https://sliksvn.com/download/).
   2. After installation, open **Command Prompt** (for SlikSVN) or use the right-click context menu (for TortoiseSVN).

   **Using Command Prompt (SlikSVN):**
   ```cmd
   svn checkout https://github.com/TonyRoyze/phactory
   svn checkout https://github.com/TonyRoyze/phactory/NewsSystem
   ```

   **Using TortoiseSVN:**
   - Right-click in the folder where you want to download the repository.
   - Select **SVN Checkout...**
   - Enter `https://github.com/TonyRoyze/phactory` as the URL of repository.
   - Click **OK**.

   ### For macOS:

   ```bash
   # Install SVN using Homebrew if not already installed
   brew install svn

   # Checkout the repository
   svn checkout https://github.com/TonyRoyze/phactory

   # To get only a specific folder from the repository, use:
   svn checkout https://github.com/TonyRoyze/phactory/NewsSystem
   ```
2. **Copy files to your web server directory.**

3. **Configure database connection:**
   - Create a `connector.php` file in the project root with the following content:
     ```php
     <?php

     $dbServerName = "localhost";
     $dbUserName = "root";
     $dbPassword = "";
     $dbName = "newsdb";

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
- Use the interface to add, edit, or view news articles.

## File Structure

- `index.php` - Main entry point
- `add_news.php` - Add news articles
- `edit_news.php` - Edit existing articles
- `view_news.php` - View a single article
- `connector.php` - Configuration file
- `.gitignore` - Git ignore rules


