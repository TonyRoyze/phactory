<?php
// This file is views/search.php - Comprehensive search interface

require 'connector.php';

// Get search parameters
$search_term = $_GET['q'] ?? '';
$search_type = $_GET['type'] ?? 'all';
$category = $_GET['category'] ?? '';
$priority = $_GET['priority'] ?? '';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Get admin users for assignment filter (admin only)
$admin_users = [];
if ($_SESSION['user_role'] === 'ADMIN') {
    $admin_sql = "SELECT user_id, full_name FROM users WHERE user_role = 'ADMIN' ORDER BY full_name";
    $admin_result = $conn->query($admin_sql);
    while ($admin = $admin_result->fetch_assoc()) {
        $admin_users[] = $admin;
    }
}

// Get customers for customer filter (admin only)
$customers = [];
if ($_SESSION['user_role'] === 'ADMIN') {
    $customer_sql = "SELECT DISTINCT u.user_id, u.full_name, u.email 
                     FROM users u 
                     INNER JOIN tickets t ON u.user_id = t.customer_id 
                     WHERE u.user_role = 'CUSTOMER' 
                     ORDER BY u.full_name";
    $customer_result = $conn->query($customer_sql);
    while ($customer = $customer_result->fetch_assoc()) {
        $customers[] = $customer;
    }
}
?>

<div class="search-page">
    <div class="page-header">
        <h1>Search Tickets</h1>
        <div class="header-actions">
            <?php if ($_SESSION['user_role'] === 'ADMIN'): ?>
                <a href="app.php?view=dashboard" class="btn-secondary">← Dashboard</a>
            <?php else: ?>
                <a href="app.php?view=my_tickets" class="btn-secondary">← My Tickets</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Search Form -->
    <div class="main-search-container">
        <form class="enhanced-search-form" id="mainSearchForm">
            <div class="search-input-container">
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Search tickets, descriptions, replies..." 
                       value="<?= htmlspecialchars($search_term) ?>"
                       autocomplete="off">
                <button type="submit" class="btn-search">
                    <span class="search-icon"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-icon lucide-search"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg></span>
                    Search
                </button>
                <a href="#" class="advanced-search-toggle btn-advanced-search-link">Advanced Search</a>

            </div>
            
            <!-- Search Type Options -->
            <div class="search-type-options">
                <label class="search-type-label">
                    <input type="radio" name="search_type" value="all" <?= $search_type === 'all' ? 'checked' : '' ?>>
                    <span>All Content</span>
                </label>
                <label class="search-type-label">
                    <input type="radio" name="search_type" value="titles" <?= $search_type === 'titles' ? 'checked' : '' ?>>
                    <span>Titles Only</span>
                </label>
                <label class="search-type-label">
                    <input type="radio" name="search_type" value="descriptions" <?= $search_type === 'descriptions' ? 'checked' : '' ?>>
                    <span>Descriptions</span>
                </label>
                <label class="search-type-label">
                    <input type="radio" name="search_type" value="replies" <?= $search_type === 'replies' ? 'checked' : '' ?>>
                    <span>Replies</span>
                </label>
            </div>

            <!-- Advanced Search Panel -->
            <div class="advanced-search-panel" id="advancedSearchPanel">
                <h4>Advanced Search Options</h4>
                
                <div class="advanced-search-grid">
                    <!-- Category Filter -->
                    <div class="advanced-search-field">
                        <label for="category">Category</label>
                        <select name="category" id="category">
                            <option value="">All Categories</option>
                            <option value="Technical" <?= $category === 'Technical' ? 'selected' : '' ?>>Technical</option>
                            <option value="Billing" <?= $category === 'Billing' ? 'selected' : '' ?>>Billing</option>
                            <option value="General" <?= $category === 'General' ? 'selected' : '' ?>>General</option>
                        </select>
                    </div>

                    <!-- Priority Filter -->
                    <div class="advanced-search-field">
                        <label for="priority">Priority</label>
                        <select name="priority" id="priority">
                            <option value="">All Priorities</option>
                            <option value="Low" <?= $priority === 'Low' ? 'selected' : '' ?>>Low</option>
                            <option value="Medium" <?= $priority === 'Medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="High" <?= $priority === 'High' ? 'selected' : '' ?>>High</option>
                            <option value="Urgent" <?= $priority === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="advanced-search-field">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="">All Statuses</option>
                            <option value="Open" <?= $status === 'Open' ? 'selected' : '' ?>>Open</option>
                            <option value="In Progress" <?= $status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Resolved" <?= $status === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="Closed" <?= $status === 'Closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>

                    <?php if ($_SESSION['user_role'] === 'ADMIN'): ?>
                    <!-- Customer Filter (Admin Only) -->
                    <div class="advanced-search-field">
                        <label for="customer_id">Customer</label>
                        <select name="customer_id" id="customer_id">
                            <option value="">All Customers</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer['user_id'] ?>">
                                    <?= htmlspecialchars($customer['full_name']) ?> (<?= htmlspecialchars($customer['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Assigned To Filter (Admin Only) -->
                    <div class="advanced-search-field">
                        <label for="assigned_to">Assigned To</label>
                        <select name="assigned_to" id="assigned_to">
                            <option value="">All Assignments</option>
                            <option value="unassigned">Unassigned</option>
                            <?php foreach ($admin_users as $admin): ?>
                                <option value="<?= $admin['user_id'] ?>">
                                    <?= htmlspecialchars($admin['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Date Range -->
                    <div class="advanced-search-field">
                        <label for="date_from">From Date</label>
                        <input type="date" name="date_from" id="date_from" value="<?= htmlspecialchars($date_from) ?>">
                    </div>

                    <div class="advanced-search-field">
                        <label for="date_to">To Date</label>
                        <input type="date" name="date_to" id="date_to" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                </div>

                <div class="advanced-search-actions">
                    <button type="button" class="btn-clear-advanced" onclick="clearAdvancedSearch()">Clear Filters</button>
                    <button type="submit" class="btn-advanced-search">Search with Filters</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Search Results Container -->
    <div class="search-results" id="searchResults">
        <?php if (!empty($search_term)): ?>
            <div class="initial-search-message">
                <p>Enter a search term and click "Search" to see results.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Search Tips -->
    <div class="search-tips-section">
        <h3>Search Tips</h3>
        <div class="tips-grid">
            <div class="tip-item">
                <h4>🔍 Basic Search</h4>
                <p>Enter keywords to search across ticket titles, descriptions, and replies.</p>
            </div>
            <div class="tip-item">
                <h4>🎯 Targeted Search</h4>
                <p>Use the radio buttons to search only in titles, descriptions, or replies.</p>
            </div>
            <div class="tip-item">
                <h4>⚙️ Advanced Filters</h4>
                <p>Use advanced search to filter by category, priority, status, and date range.</p>
            </div>
            <div class="tip-item">
                <h4>💡 Auto-complete</h4>
                <p>Start typing to see suggestions for tickets, customers, and keywords.</p>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize enhanced search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('mainSearchForm');
    const searchInput = document.querySelector('.search-input');
    const resultsContainer = document.getElementById('searchResults');
    const advancedPanel = document.getElementById('advancedSearchPanel');
    const advancedToggle = document.querySelector('.advanced-search-toggle');

    // Initialize enhanced search
    const enhancedSearch = new EnhancedSearch({
        searchInput: searchInput,
        searchForm: searchForm,
        resultsContainer: resultsContainer,
        advancedPanel: advancedPanel,
        advancedToggle: advancedToggle
    });

    // Perform initial search if search term is provided
    <?php if (!empty($search_term)): ?>
        enhancedSearch.performSearch();
    <?php endif; ?>
});

function clearAdvancedSearch() {
    const advancedPanel = document.getElementById('advancedSearchPanel');
    const inputs = advancedPanel.querySelectorAll('input, select');
    inputs.forEach(input => {
        if (input.type === 'radio' || input.type === 'checkbox') {
            input.checked = false;
        } else {
            input.value = '';
        }
    });
    
    // Reset search type to 'all'
    document.querySelector('input[name="search_type"][value="all"]').checked = true;
}
</script>

<style>
/* Additional styles specific to the search page */
.search-page {
    max-width: 1000px;
    margin: 0 auto;
}

.main-search-container {
    background-color: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 30px;
}

.enhanced-search-form {
    position: relative;
}

.search-input-container {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.search-input {
    flex: 1;
    padding: 12px 15px;
    border: 2px solid var(--border-color);
    border-radius: 5px;
    font-size: 16px;
    transition: border-color 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: var(--accent-color);
}

.btn-search {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background-color: var(--accent-color);
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-search:hover {
    background-color: #204d74;
}

.search-icon {
    font-size: 14px;
}

.search-type-options {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.search-type-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 14px;
    color: var(--text-secondary-color);
    transition: color 0.2s;
}

.search-type-label:hover {
    color: var(--text-color);
}

.search-type-label input[type="radio"] {
    margin: 0;
}

.search-type-label input[type="radio"]:checked + span {
    color: var(--accent-color);
    font-weight: 500;
}

.initial-search-message {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-secondary-color);
    background-color: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: 5px;
}

.search-tips-section {
    background-color: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 30px;
    margin-top: 30px;
}

.search-tips-section h3 {
    margin: 0 0 20px 0;
    color: var(--text-color);
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.tip-item {
    padding: 15px;
    background-color: var(--bg-secondary-color);
    border-radius: 5px;
}

.tip-item h4 {
    margin: 0 0 10px 0;
    color: var(--text-color);
    font-size: 14px;
}

.tip-item p {
    margin: 0;
    color: var(--text-secondary-color);
    font-size: 13px;
    line-height: 1.4;
}

@media (max-width: 768px) {
    .search-input-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-type-options {
        flex-direction: column;
        gap: 10px;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
}
</style>