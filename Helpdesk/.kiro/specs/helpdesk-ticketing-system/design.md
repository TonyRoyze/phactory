# Design Document

## Overview

This design document outlines the transformation of the existing forum system into a comprehensive Helpdesk Ticketing and Support System. The system will maintain the current technical stack (HTML5, CSS3, Vanilla JavaScript, PHP, MySQL) while restructuring the data model and user interface to support ticket-based customer support workflows.

The design leverages the existing MVC-like structure with `app.php` as the main controller, view files in the `views/` directory, and action handlers in the `actions/` directory. The current authentication system and CSS framework will be extended to support the new helpdesk functionality.

## Architecture

### System Architecture
The helpdesk system follows a traditional three-tier architecture:

1. **Presentation Layer**: HTML5 views with CSS3 styling and Vanilla JavaScript for interactivity
2. **Application Layer**: PHP scripts handling business logic and request routing
3. **Data Layer**: MySQL database with normalized tables for tickets, users, and related entities

### File Structure
```
/
├── app.php                 # Main application controller
├── connector.php           # Database connection configuration
├── login.php              # User authentication
├── signup.php             # User registration
├── logout.php             # Session termination
├── index.php              # Entry point (redirects to app.php)
├── styles.css             # Main stylesheet
├── actions/               # Server-side action handlers
│   ├── create_ticket.php
│   ├── update_ticket.php
│   ├── add_reply.php
│   ├── assign_ticket.php
│   ├── update_status.php
│   └── upload_file.php
├── views/                 # UI view templates
│   ├── dashboard.php      # Admin dashboard
│   ├── tickets.php        # Ticket listing
│   ├── ticket.php         # Individual ticket view
│   ├── create_ticket.php  # Ticket creation form
│   └── my_tickets.php     # Customer ticket history
└── uploads/               # File attachment storage
```

### Request Flow
1. User requests are routed through `app.php` based on the `view` parameter
2. Authentication is checked via session variables
3. Appropriate view file is included based on user role and requested action
4. Form submissions are processed by action handlers in the `actions/` directory
5. Database operations use prepared statements via the existing `connector.php`

## Components and Interfaces

### User Management Component
**Purpose**: Handle user authentication, registration, and role management

**Key Functions**:
- User registration with role assignment (Customer/Admin)
- Session-based authentication
- Profile management
- Role-based access control

**Interfaces**:
- `login.php`: Authentication form and processing
- `signup.php`: Registration form with role selection
- `views/edit_profile.php`: Profile management interface

### Ticket Management Component
**Purpose**: Core ticket lifecycle management

**Key Functions**:
- Ticket creation with categories and priorities
- Status tracking (Open, In Progress, Resolved, Closed)
- Assignment to support agents
- File attachment handling

**Interfaces**:
- `views/create_ticket.php`: Ticket submission form
- `views/ticket.php`: Individual ticket detail view
- `views/tickets.php`: Ticket listing with filters
- `actions/create_ticket.php`: Ticket creation handler
- `actions/update_ticket.php`: Ticket modification handler

### Communication Component
**Purpose**: Handle replies, comments, and internal notes

**Key Functions**:
- Public replies visible to customers
- Internal notes for admin collaboration
- File attachments for replies
- Chronological conversation threading

**Interfaces**:
- Reply forms embedded in ticket views
- `actions/add_reply.php`: Reply processing handler
- File upload integration

### Dashboard Component
**Purpose**: Provide administrative overview and metrics

**Key Functions**:
- Ticket statistics and metrics
- Recent activity monitoring
- Quick access to urgent tickets
- Performance indicators

**Interfaces**:
- `views/dashboard.php`: Admin dashboard view
- AJAX endpoints for real-time updates

### Search and Filter Component
**Purpose**: Enable efficient ticket discovery and management

**Key Functions**:
- Full-text search across tickets
- Multi-criteria filtering
- Pagination for large result sets
- Saved search preferences

**Interfaces**:
- Search forms integrated into listing views
- Filter controls with AJAX updates
- Pagination controls

## Data Models

### Database Schema

#### Users Table (Modified from existing)
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    user_role ENUM('CUSTOMER', 'ADMIN') DEFAULT 'CUSTOMER',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Tickets Table (New)
```sql
CREATE TABLE tickets (
    ticket_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('Technical', 'Billing', 'General') NOT NULL,
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    status ENUM('Open', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Open',
    customer_id INT NOT NULL,
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(user_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id)
);
```

#### Ticket Replies Table (New)
```sql
CREATE TABLE ticket_replies (
    reply_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    author_id INT NOT NULL,
    content TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(user_id)
);
```

#### File Attachments Table (New)
```sql
CREATE TABLE attachments (
    attachment_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NULL,
    reply_id INT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (reply_id) REFERENCES ticket_replies(reply_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);
```

#### Categories Table (New)
```sql
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    color_code VARCHAR(7) DEFAULT '#2E6DA4',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Data Relationships
- **Users** have many **Tickets** (as customers)
- **Users** can be assigned many **Tickets** (as admins)
- **Tickets** have many **Ticket Replies**
- **Tickets** and **Ticket Replies** can have many **Attachments**
- **Tickets** belong to one **Category**

### Data Validation Rules
- Email addresses must be unique and valid format
- Passwords must be at least 6 characters
- Ticket titles are required and limited to 255 characters
- File uploads limited to 5MB and specific MIME types
- Status transitions follow business rules (e.g., only admins can close tickets)

## Error Handling

### Client-Side Validation
- Form validation using HTML5 attributes and JavaScript
- Real-time feedback for invalid inputs
- File upload validation (size, type, count)
- Required field highlighting

### Server-Side Validation
- Input sanitization and validation for all form submissions
- SQL injection prevention using prepared statements
- XSS prevention through output escaping
- File upload security checks

### Error Response Strategy
- User-friendly error messages for validation failures
- Detailed logging for system errors
- Graceful degradation for JavaScript failures
- Consistent error page templates

### Database Error Handling
- Connection failure recovery
- Transaction rollback for data integrity
- Constraint violation handling
- Deadlock detection and retry logic

## Testing Strategy

### Unit Testing Approach
- PHP unit tests for core business logic functions
- Database operation testing with test fixtures
- Input validation testing with edge cases
- File upload functionality testing

### Integration Testing
- End-to-end ticket creation and resolution workflows
- User authentication and authorization flows
- File attachment upload and download processes
- Email notification delivery (if implemented)

### User Acceptance Testing
- Customer ticket submission and tracking scenarios
- Admin ticket management and assignment workflows
- Search and filtering functionality validation
- Mobile responsiveness testing

### Security Testing
- SQL injection vulnerability testing
- XSS attack prevention validation
- File upload security testing
- Session management security verification
- Access control testing for different user roles

### Performance Testing
- Database query optimization validation
- File upload performance testing
- Concurrent user load testing
- Search functionality performance validation

### Browser Compatibility Testing
- Cross-browser JavaScript functionality
- CSS rendering consistency
- Mobile device compatibility
- Accessibility compliance testing

## UI/UX Design Considerations

### Design Principles
- Maintain consistency with existing forum design patterns
- Prioritize clarity and ease of use for support workflows
- Implement responsive design for mobile accessibility
- Use color coding for ticket priorities and statuses

### Navigation Structure
- Dashboard-centric design for admins
- Simplified ticket-focused navigation for customers
- Breadcrumb navigation for deep ticket views
- Quick action buttons for common tasks

### Visual Hierarchy
- Clear distinction between customer and admin interfaces
- Priority-based visual indicators for tickets
- Status-based color coding throughout the system
- Consistent typography and spacing

### Accessibility Features
- Semantic HTML markup for screen readers
- Keyboard navigation support
- High contrast color schemes
- Alt text for all images and icons

This design provides a comprehensive foundation for transforming the existing forum system into a professional helpdesk ticketing system while maintaining the current technical architecture and complexity level.