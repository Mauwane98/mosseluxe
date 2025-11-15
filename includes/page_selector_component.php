<?php
/**
 * Page Selector Component for Admin Forms
 *
 * Usage Example:
 * <?php
 * $pages = get_pages_for_dropdown();
 * $current_url = $your_current_url_variable;
 * include 'includes/page_selector_component.php';
 * ?>
 *
 * This will render a dropdown with page selection and toggle to custom URL mode
 */

// Set defaults if not provided
$current_url = $current_url ?? '';
$input_id = $input_id ?? 'url';
$input_name = $input_name ?? 'url';
$default_mode = $default_mode ?? 'pages'; // 'pages' or 'custom'

$is_page_selected = false;
$selected_page_slug = '';
?>

<div class="space-y-2">
    <!-- Page Selection Mode -->
    <div id="page-selector-container-<?php echo $input_id; ?>" class="space-y-2">
        <select id="page_selector_<?php echo $input_id; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
            <option value="">Select a Page</option>
            <optgroup label="Site Pages">
                <option value="/" <?php echo ($current_url === '/') ? 'selected' : ''; ?>>Home</option>
                <option value="/shop" <?php echo ($current_url === '/shop') ? 'selected' : ''; ?>>Shop</option>
                <option value="/about" <?php echo ($current_url === '/about') ? 'selected' : ''; ?>>About</option>
                <option value="/contact" <?php echo ($current_url === '/contact') ? 'selected' : ''; ?>>Contact</option>
                <option value="/my-account" <?php echo ($current_url === '/my-account') ? 'selected' : ''; ?>>My Account</option>
                <option value="/wishlist" <?php echo ($current_url === '/wishlist') ? 'selected' : ''; ?>>Wishlist</option>
                <?php
                // Check if current URL matches any page and mark as selected
                foreach ($pages as $page):
                    $page_url = '/page.php?slug=' . $page['slug'];
                    $is_selected = ($current_url === $page_url);
                    if ($is_selected) {
                        $is_page_selected = true;
                    }
                ?>
                <option value="<?php echo $page_url; ?>" <?php echo $is_selected ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($page['title']); ?>
                </option>
                <?php endforeach; ?>
            </optgroup>
        </select>
        <button type="button" id="custom_url_toggle_<?php echo $input_id; ?>" class="text-sm text-blue-600 hover:text-blue-800 underline">
            Or enter custom URL
        </button>
    </div>

    <!-- Custom URL Mode -->
    <div id="custom-url-container-<?php echo $input_id; ?>" class="space-y-2 <?php
        // Show custom URL mode if:
        // 1. Default mode is 'custom', OR
        // 2. URL is non-empty but not a page URL
        $should_show_custom = ($default_mode === 'custom') || (!$is_page_selected && !empty($current_url));
        echo $should_show_custom ? 'block' : 'hidden';
    ?>">
        <input type="text" id="<?php echo $input_id; ?>" name="<?php echo $input_name; ?>"
               value="<?php echo htmlspecialchars($current_url); ?>"
               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"
               autocomplete="off">
        <button type="button" id="page_selector_toggle_<?php echo $input_id; ?>" class="text-sm text-blue-600 hover:text-blue-800 underline">
            Back to page selection
        </button>
    </div>

    <p class="text-xs text-gray-500">Choose a page from the dropdown or enter a custom URL (anchors, external links, etc.)</p>
</div>

<script>
// Page selection functionality for <?php echo $input_id; ?>
document.addEventListener('DOMContentLoaded', function() {
    const inputId = '<?php echo $input_id; ?>';
    const pageSelector = document.getElementById('page_selector_' + inputId);
    const urlInput = document.getElementById(inputId);
    const customUrlToggle = document.getElementById('custom_url_toggle_' + inputId);
    const pageSelectorToggle = document.getElementById('page_selector_toggle_' + inputId);
    const pageSelectorContainer = document.getElementById('page-selector-container-' + inputId);
    const customUrlContainer = document.getElementById('custom-url-container-' + inputId);

    // Handle page selection from dropdown
    if (pageSelector && urlInput) {
        pageSelector.addEventListener('change', function() {
            const selectedValue = this.value;
            if (selectedValue) {
                urlInput.value = selectedValue;
            }
        });
    }

    // Toggle to custom URL input
    if (customUrlToggle && pageSelectorContainer && customUrlContainer) {
        customUrlToggle.addEventListener('click', function(e) {
            e.preventDefault();
            pageSelectorContainer.classList.add('hidden');
            customUrlContainer.classList.remove('hidden');
            urlInput.focus();
        });
    }

    // Toggle back to page selector
    if (pageSelectorToggle && pageSelectorContainer && customUrlContainer) {
        pageSelectorToggle.addEventListener('click', function(e) {
            e.preventDefault();
            customUrlContainer.classList.add('hidden');
            pageSelectorContainer.classList.remove('hidden');
            pageSelector.focus();
        });
    }

    // Update URL when user types in custom field
    if (urlInput) {
        urlInput.addEventListener('input', function() {
            // Clear dropdown selection when typing custom URL
            if (pageSelector) {
                pageSelector.value = '';
            }
        });
    }
});
</script>
