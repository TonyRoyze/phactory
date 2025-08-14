# ProductionManagementSystem

## Overview

ProductionManagementSystem is a PHP-based web application designed to help manage and track production inventory, processes, and sales for manufacturing or production-oriented businesses. It provides an intuitive interface for monitoring products, managing production records, and streamlining workflow.

## Features

- View and manage production inventory
- Add, edit, and delete production records
- Track production batches and process status
- Search for products or batches by name or type
- User-friendly and clean interface

## Getting Started

### Prerequisites

- PHP 7.0 or higher
- A web server (e.g., Apache, Nginx)
- MySQL or compatible database

### Installation

1. **Clone the repository:**

   #### For Linux (Ubuntu/Debian):

   ```bash
   # Install SVN if not already installed
   sudo apt-get update
   sudo apt-get install subversion

   # Checkout the repository
   svn checkout https://github.com/TonyRoyze/phactory

   # To get only this project folder:
   svn checkout https://github.com/TonyRoyze/phactory/ProductionManagementSystem
   ```

   #### For Windows:

   1. Download and install [TortoiseSVN](https://tortoisesvn.net/downloads.html) or [SlikSVN](https://sliksvn.com/download/).
   2. After installation, open **Command Prompt** (for SlikSVN) or use the right-click context menu (for TortoiseSVN).

   **Using Command Prompt (SlikSVN):**
   ```cmd
   svn checkout https://github.com/TonyRoyze/phactory
   svn checkout https://github.com/TonyRoyze/phactory/ProductionManagementSystem
   ```

   **Using TortoiseSVN:**
   - Right-click in the folder where you want to download the repository.
   - Select **SVN Checkout...**
   - Enter `https://github.com/TonyRoyze/phactory` as the URL of repository.
   - Click **OK**.

   #### For macOS:

   ```bash
   # Install SVN using Homebrew if not already installed
   brew install svn

   # Checkout the repository
   svn checkout https://github.com/TonyRoyze/phactory

   # To get only this project folder:
   svn checkout https://github.com/TonyRoyze/phactory/ProductionManagementSystem
   ```

2. **Copy files to your web server directory.**

3. **Configure the database connection:**
   - Create `includes/db.php` and update the database credentials as needed:
     ```php
     <?php
     $servername = "localhost";
     $username = "root";
     $password = "";
     $dbname = "production_management";

     $conn = new mysqli($servername, $username, $password, $dbname);

     if ($conn->connect_error) {
         die("Connection failed: " . $conn->connect_error);
     }
     ?>
     ```

4. **Import the database schema:**
   - Use the provided SQL file (if available) to create the necessary tables in your MySQL database.

5. **Set appropriate permissions:**
   - Ensure the web server can write to any upload or cache directories if used.

### Usage

- Access the application via your web browser at `http://localhost:8000` or your server's URL.
- Use the interface to manage production records and inventory.

## File Structure

- `index.php` - Main entry point
- `add_production.php` - Add new production records
- `edit_production.php` - Edit existing production records
- `view_production.php` - View details of a production batch or item
- `includes/db.php` - Database connection file
- `.gitignore` - Git ignore rules
