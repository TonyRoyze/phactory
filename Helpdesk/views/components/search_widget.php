<?php
// This file is views/components/search_widget.php - Reusable search widget

$widget_id = $widget_id ?? 'search-widget-' . uniqid();
$placeholder = $placeholder ?? 'Search tickets...';
$redirect_view = $redirect_view ?? 'search';
$compact = $compact ?? false;
?>

<div class="search-widget <?= $compact ? 'compact' : '' ?>" id="<?= $widget_id ?>">
    <form class="search-widget-form" action="app.php" method="GET">
        <input type="hidden" name="view" value="<?= htmlspecialchars($redirect_view) ?>">
        <div class="search-widget-input-container">
            <input type="text" 
                   name="q" 
                   class="search-input search-widget-input" 
                   placeholder="<?= htmlspecialchars($placeholder) ?>" 
                   autocomplete="off">
            <button type="submit" class="search-widget-btn">
                <span class="search-icon"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-icon lucide-search"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg></span>
                <?php if (!$compact): ?>
                    <span class="search-text">Search</span>
                <?php endif; ?>
            </button>
        </div>
    </form>
</div>

<style>
.search-widget {
    margin-bottom: 20px;
}

.search-widget.compact {
    margin-bottom: 10px;
}

.search-widget-form {
    position: relative;
}

.search-widget-input-container {
    display: flex;
    gap: 8px;
    align-items: center;
}

.search-widget-input {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.search-widget.compact .search-widget-input {
    padding: 8px 10px;
    font-size: 13px;
}

.search-widget-input:focus {
    outline: none;
    border-color: var(--accent-color);
}

.search-widget-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 15px;
    background-color: var(--accent-color);
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s;
    white-space: nowrap;
}

.search-widget.compact .search-widget-btn {
    padding: 8px 12px;
    font-size: 13px;
}

.search-widget-btn:hover {
    background-color: #204d74;
}

.search-icon {
    font-size: 12px;
}

.search-widget-advanced {
    display: inline-block;
    margin-top: 8px;
    color: var(--accent-color);
    text-decoration: none;
    font-size: 12px;
    padding: 4px 8px;
    border: 1px solid var(--accent-color);
    border-radius: 3px;
    transition: all 0.2s;
}

.search-widget-advanced:hover {
    background-color: var(--accent-color);
    color: white;
}

@media (max-width: 480px) {
    .search-widget-input-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-widget-btn {
        justify-content: center;
    }
}
</style>