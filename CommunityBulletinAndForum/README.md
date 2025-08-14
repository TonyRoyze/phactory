# CommunityHub

A modern, responsive community forum and bulletin board system built with PHP and MySQL. CommunityHub enables local communities to connect, share information, and engage in meaningful discussions.

## 🌟 Features

### Core Functionality
- **Community Categories**: Organized discussion areas including bulletins, general discussion, local events, help & support, marketplace, and social corner
- **User Management**: Complete registration, authentication, and profile system with avatars and bios
- **Post System**: Create, view, and interact with posts including likes and comments
- **Real-time Features**: Trending topics, community statistics, and recent activity tracking
- **Search & Discovery**: Full-text search across posts and users with advanced filtering
- **Events System**: Community event creation and management
- **Mobile-Responsive**: Fully responsive design optimized for all devices

### Technical Features
- **Performance Optimized**: Built-in caching system and performance monitoring
- **Security First**: CSRF protection, password hashing, and prepared statements
- **Accessibility**: WCAG 2.1 AA compliant with semantic HTML and ARIA labels
- **AJAX-Powered**: Dynamic content loading without page refreshes
- **SEO Friendly**: Clean URLs and optimized meta tags

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

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
   svn checkout https://github.com/TonyRoyze/phactory/CommunityBulletinAndForum
   ```

   ### For Windows:

   1. Download and install [TortoiseSVN](https://tortoisesvn.net/downloads.html) or [SlikSVN](https://sliksvn.com/download/).
   2. After installation, open **Command Prompt** (for SlikSVN) or use the right-click context menu (for TortoiseSVN).

   **Using Command Prompt (SlikSVN):**
   ```cmd
   svn checkout https://github.com/TonyRoyze/phactory
   svn checkout https://github.com/TonyRoyze/phactory/CommunityBulletinAndForum
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
   svn checkout https://github.com/TonyRoyze/phactory/CommunityBulletinAndForum
   ```

2. **Database Setup:**
   ```bash
   # Create database and import schema
   mysql -u root -p
   CREATE DATABASE community_forum;
   exit
   
   # Import the database schema
   mysql -u root -p community_forum < database.sql
   ```

3. **Configure the application:**
   - Copy `includes/config.php.example` to `includes/config.php`
   - Update database credentials and site settings:
   ```php
   // Database Configuration
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'community_forum');
   ```

4. **Set permissions:**
   ```bash
   # Make cache directory writable
   chmod 755 cache/
   chmod 644 .htaccess
   ```

5. **Start development server:**
   ```bash
   # Using PHP built-in server
   php -S localhost:8000
   
   # Or configure your web server to point to the project directory
   ```

6. **Access the application:**
   - Open `http://localhost:8000` in your browser
   - Register a new account or use the demo credentials

## 📁 Project Structure

```
/
├── api/                    # AJAX endpoints and API routes
├── css/                    # Stylesheets (theme.css, style.css)
├── js/                     # JavaScript modules and components
├── includes/               # PHP utilities and configuration
│   ├── config.php         # Database and site configuration
│   ├── database.php       # Database abstraction layer
│   ├── functions.php      # Core business logic
│   └── session.php        # Session management
├── admin/                  # Administrative tools
├── scripts/                # Maintenance and utility scripts
├── cache/                  # Application cache directory
├── templates/              # Reusable template components
├── index.php              # Homepage
├── forum.php              # Category and topic browsing
├── post.php               # Individual post view
├── create-post.php        # Post creation form
├── login.php              # User authentication
├── register.php           # User registration
├── profile.php            # User profile management
├── search.php             # Search functionality
└── database.sql           # Database schema and sample data
```

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+ with MySQLi
- **Database**: MySQL with full-text search indexes
- **Frontend**: HTML5, CSS3, Vanilla JavaScript (ES6+)
- **Icons**: Font Awesome 6.0.0
- **Architecture**: MVC-style with separation of concerns
- **Security**: CSRF protection, prepared statements, secure sessions

## 🔧 Development

### Code Standards
- **PHP**: PSR-12 coding standards, camelCase for functions
- **JavaScript**: ES6+ features, modular class-based architecture
- **CSS**: BEM-like naming, CSS custom properties for theming
- **Security**: Always use prepared statements, validate/sanitize input


