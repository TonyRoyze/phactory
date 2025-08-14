# PetStore

## Overview

PetStore is a simple PHP-based web application for managing pet store inventory and sales. It allows users to view available pets, add new pets, and manage store data through a user-friendly interface.

## Features

- View list of available pets
- Add, edit, and delete pet records
- Search for pets by type or name
- Simple and clean interface

## Getting Started

### Prerequisites

- PHP 7.0 or higher
- A web server (e.g., Apache, Nginx)
- MySQL or compatible database

### Installation

1. **Clone the repository:**
   
   ### For Linux (Ubuntu/Debian):

   ```bash
   # Install SVN if not already installed
   sudo apt-get update
   sudo apt-get install subversion

   # Checkout the repository (replace https://github.com/TonyRoyze/phactory with the actual URL)
   svn checkout https://github.com/TonyRoyze/phactory

   # To get only a specific folder from the repository, use:
   svn checkout https://github.com/TonyRoyze/phactory/PetStore
   ```

   ### For Windows:

   1. Download and install [TortoiseSVN](https://tortoisesvn.net/downloads.html) or [SlikSVN](https://sliksvn.com/download/).
   2. After installation, open **Command Prompt** (for SlikSVN) or use the right-click context menu (for TortoiseSVN).

   **Using Command Prompt (SlikSVN):**
   ```cmd
   svn checkout https://github.com/TonyRoyze/phactory
   svn checkout https://github.com/TonyRoyze/phactory/PetStore
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
   svn checkout https://github.com/TonyRoyze/phactory/PetStore
   ```

2. **Copy files to your web server directory.**

3. **Configure database connection:**
   - Create `includes/db.php` and update the database credentials if necessary:
     ```php
     <?php
     $servername = "localhost";
     $username = "root";
     $password = "";
     $dbname = "petstore";

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

- Access the application via your web browser at `http://localhost/8000` or your server's URL.
- Use the interface to manage pets and store data.

## File Structure

- `index.php` - Main entry point
- `add_pet.php` - Add new pets
- `edit_pet.php` - Edit existing pet records
- `view_pet.php` - View a single pet's details
- `includes/db.php` - Database connection file
- `.gitignore` - Git ignore rules
