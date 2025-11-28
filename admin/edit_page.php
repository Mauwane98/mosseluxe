<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

$page_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 0;
$is_editing = $page_id > 0;

// --- Slug Generation Helper ---
function generate_slug($title, $conn, $ignore_id = 0) {
    // If title is empty, generate a timestamp-based slug
    if (empty(trim($title))) {
        $title = 'page-' . time();
    }

    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');

    // Check for uniqueness
    $original_slug = $slug;
    $counter = 1;
    while (true) {
        $sql = "SELECT id FROM pages WHERE slug = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $slug, $ignore_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) {
            $stmt->close();
            break;
        }
        $stmt->close();
        $slug = $original_slug . '-' . $counter++;
    }
    return $slug;
}

// --- POST Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_message'] = ['message' => 'Invalid CSRF token.', 'type' => 'error'];
        header("Location: pages.php");
        exit();
    }

    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $meta_title = trim($_POST['meta_title']);
    $meta_description = trim($_POST['meta_description']);
    $content = trim($_POST['content']);

    // Only content is required, others are optional
    if (empty($content)) {
        $_SESSION['toast_message'] = ['message' => 'Content cannot be empty.', 'type' => 'error'];
        header("Location: " . $_SERVER['REQUEST_URI']); // Redirect back to the same page
        exit();
    }

    if ($is_editing) {
        // Update existing page
        $sql = "UPDATE pages SET title = ?, subtitle = ?, meta_title = ?, meta_description = ?, content = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssi', $title, $subtitle, $meta_title, $meta_description, $content, $page_id);
        $success_message = 'Page updated successfully!';
    } else {
        // Create new page
        $slug = generate_slug($title, $conn);
        $sql = "INSERT INTO pages (title, slug, subtitle, meta_title, meta_description, content) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssss', $title, $slug, $subtitle, $meta_title, $meta_description, $content);
        $success_message = 'Page created successfully!';
    }

    if ($stmt->execute()) {
        $_SESSION['toast_message'] = ['message' => $success_message, 'type' => 'success'];
        regenerate_csrf_token();
        header("Location: pages.php");
    } else {
        $_SESSION['toast_message'] = ['message' => 'An error occurred while saving the page.', 'type' => 'error'];
        header("Location: " . $_SERVER['REQUEST_URI']);
    }
    exit();
}

// --- Data Fetching for Edit Mode ---
$page = [
    'id' => 0,
    'title' => '',
    'slug' => '',
    'content' => ''
];

if ($is_editing) {
    $stmt = $conn->prepare("SELECT id, title, subtitle, slug, meta_title, meta_description, content FROM pages WHERE id = ?");
    $stmt->bind_param('i', $page_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $page = $result->fetch_assoc();
    $stmt->close();

    if (!$page) {
        $_SESSION['toast_message'] = ['message' => 'Page not found.', 'type' => 'error'];
        header("Location: pages.php");
        exit();
    }
}

$pageTitle = $is_editing ? 'Edit Page' : 'Create New Page';

// Fetch all pages for internal link dropdown
$all_pages = [];
$sql_pages = "SELECT id, title, slug FROM pages WHERE status = 1 ORDER BY title ASC";
if ($result_pages = $conn->query($sql_pages)) {
    while ($row_page = $result_pages->fetch_assoc()) {
        $all_pages[] = $row_page;
    }
    $result_pages->free();
} else {
    error_log("Error fetching pages for internal links: " . $conn->error);
}

include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800"><?php echo $pageTitle; ?></h2>
        <a href="pages.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">Back to Pages</a>
    </div>

    <form action="edit_page.php<?php echo $is_editing ? '?id=' . $page_id : ''; ?>" method="post" class="space-y-6">
        <?php echo generate_csrf_token_input(); ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Title -->
            <div class="md:col-span-2">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Page Title <span class="text-gray-500 text-xs">(optional)</span></label>
                <input type="text" id="title" name="title"
                       value="<?php echo htmlspecialchars($page['title']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"
                       placeholder="Optional page title">
            </div>

            <!-- Subtitle -->
            <div class="md:col-span-2">
                <label for="subtitle" class="block text-sm font-medium text-gray-700 mb-2">Page Subtitle</label>
                <input type="text" id="subtitle" name="subtitle"
                       value="<?php echo htmlspecialchars($page['subtitle'] ?? ''); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"
                       placeholder="Optional subtitle that appears under the title">
            </div>

            <!-- Meta Title -->
            <div class="md:col-span-2">
                <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">Meta Title <span class="text-gray-500 text-xs">(for SEO)</span></label>
                <input type="text" id="meta_title" name="meta_title"
                       value="<?php echo htmlspecialchars($page['meta_title'] ?? ''); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black"
                       placeholder="Optional meta title for search engines">
                <p class="text-xs text-gray-500 mt-1">If empty, page title will be used.</p>
            </div>

            <!-- Meta Description -->
            <div class="md:col-span-2">
                <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">Meta Description <span class="text-gray-500 text-xs">(for SEO)</span></label>
                <textarea id="meta_description" name="meta_description"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black h-20 resize-vertical"
                          placeholder="Optional meta description for search engines"><?php echo htmlspecialchars($page['meta_description'] ?? ''); ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Brief description for search engine results, max 160 characters.</p>
            </div>

            <?php if ($is_editing): ?>
            <!-- Slug (Read-only for existing pages) -->
            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Page Slug</label>
                <input type="text" id="slug" name="slug" readonly
                       value="/<?php echo htmlspecialchars($page['slug']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600">
                <p class="text-xs text-gray-500 mt-1">Slug is auto-generated and cannot be changed.</p>
            </div>
            <!-- Preview Link -->
            <div class="flex items-end">
                <a href="../page.php?slug=<?php echo urlencode($page['slug']); ?>" target="_blank"
                   class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-center">
                    Preview Page
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Content -->
        <div>
            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Page Content</label>
            <div id="editor-toolbar" class="mb-2 border border-gray-300 rounded-t-md bg-gray-50 p-2">
                <button type="button" class="toolbar-btn px-2 py-1 text-xs border border-gray-300 rounded hover:bg-gray-200" onclick="formatText('bold')" title="Bold">
                    <strong>B</strong>
                </button>
                <button type="button" class="toolbar-btn px-2 py-1 text-xs border border-gray-300 rounded hover:bg-gray-200" onclick="formatText('italic')" title="Italic">
                    <em>I</em>
                </button>
                <button type="button" class="toolbar-btn px-2 py-1 text-xs border border-gray-300 rounded hover:bg-gray-200" onclick="formatText('underline')" title="Underline">
                    <u>U</u>
                </button>
                <span class="mx-2 border-l border-gray-300 h-4 inline-block"></span>
                <button type="button" class="toolbar-btn px-2 py-1 text-xs border border-gray-300 rounded hover:bg-gray-200" onclick="formatBlock('h1')" title="Heading 1">H1</button>
                <button type="button" class="toolbar-btn px-2 py-1 text-xs border border-gray-300 rounded hover:bg-gray-200" onclick="formatBlock('h2')" title="Heading 2">H2</button>
                <button type="button" class="toolbar-btn px-2 py-1 text-xs border border-gray-300 rounded hover:bg-gray-200" onclick="formatBlock('h3')" title="Heading 3">H3</button>
                <span class="mx-2 border-l border-gray-300 h-4 inline-block"></span>
                <button type="button" class="toolbar-btn px-2 py-1 text-xs border border-gray-300 rounded hover:bg-gray-200" onclick="formatBlock('p')" title="Paragraph">P</button>
                <button type="button" class="toolbar-btn px-2 py-1 text-xs border border-gray-300 rounded hover:bg-gray-200" onclick="insertList('ul')" title="Bullet List">•</button>
                <button type="button" class="toolbar-btn px-2 py-1 text-xs border border-gray-300 rounded hover:bg-gray-200" onclick="insertList('ol')" title="Numbered List">1.</button>
                <span class="mx-2 border-l border-gray-300 h-4 inline-block"></span>
                <button type="button" class="toolbar-btn px-2 py-1 text-xs border border-gray-300 rounded hover:bg-gray-200" onclick="insertLink()" title="Insert Link">🔗</button>
                <button type="button" class="toolbar-btn px-2 py-1 text-xs border border-gray-300 rounded hover:bg-gray-200" onclick="insertInternalLink()" title="Insert Internal Page Link">🏠</button>
                <button type="button" class="toolbar-btn px-2 py-1 text-xs border border-gray-300 rounded hover:bg-gray-200" onclick="toggleCode()" title="Show/Hide HTML">HTML</button>
            </div>
            <div id="editor-container" class="border-x border-b border-gray-300 rounded-b-md">
                <div id="wysiwyg-editor"
                     contenteditable="true"
                     class="min-h-[400px] p-4 focus:outline-none prose prose-lg max-w-none"
                     oninput="updateTextarea()"><?php echo $page['content']; ?></div>
            </div>
            <textarea id="content" name="content" class="hidden"><?php echo htmlspecialchars($page['content']); ?></textarea>
            <p class="text-xs text-gray-500 mt-1 p-3 pb-2 border-x border-b border-gray-300 rounded-b-md bg-gray-50">Use the toolbar above to format your content. The content will appear exactly as it looks here.</p>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-between items-center border-t pt-6">
            <button type="button" onclick="previewContent()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                Preview Content
            </button>
            <div class="flex space-x-4">
                <a href="pages.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors">
                    <?php echo $is_editing ? 'Save Changes' : 'Create Page'; ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Content Preview Modal -->
<div id="previewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-4/5 max-w-4xl shadow-lg rounded-md bg-white max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center mb-4 pb-3 border-b">
            <h3 class="text-lg font-medium text-gray-900">Content Preview</h3>
            <button onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        <div id="previewContent" class="prose max-w-none overflow-y-auto"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('wysiwyg-editor');
    const textarea = document.getElementById('content');

    // Initialize editor with existing content or convert HTML to text
    if (textarea.value.trim() !== '') {
        // If we have HTML content, parse it into the editor
        const parser = new DOMParser();
        const doc = parser.parseFromString(textarea.value, 'text/html');
        editor.innerHTML = doc.body.innerHTML || textarea.value;
    } else {
        // Start with empty paragraph for new content
        editor.innerHTML = '<p><br></p>';
    }

    // Update textarea whenever editor content changes
    updateTextarea();

    // Focus and cursor management
    editor.addEventListener('keydown', function(e) {
        // Handle Enter key to create new paragraphs
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.execCommand('insertParagraph');
        }

        // Handle Ctrl+B, Ctrl+I, Ctrl+U for formatting
        if (e.ctrlKey) {
            switch(e.key.toLowerCase()) {
                case 'b':
                    e.preventDefault();
                    formatText('bold');
                    break;
                case 'i':
                    e.preventDefault();
                    formatText('italic');
                    break;
                case 'u':
                    e.preventDefault();
                    formatText('underline');
                    break;
            }
        }
    });
});

// Update textarea with editor HTML
function updateTextarea() {
    const editor = document.getElementById('wysiwyg-editor');
    const textarea = document.getElementById('content');
    textarea.value = editor.innerHTML;
}

// Text formatting functions
function formatText(command) {
    document.execCommand(command, false, null);
    updateTextarea();
}

function formatBlock(tagName) {
    document.execCommand('formatBlock', false, tagName);
    updateTextarea();
}

function insertList(listType) {
    document.execCommand('insert' + (listType === 'ul' ? 'UnorderedList' : 'OrderedList'), false, null);
    updateTextarea();
}

function insertLink() {
    const url = prompt('Enter the URL:', 'http://');
    if (url && url.trim() !== '') {
        document.execCommand('createLink', false, url);
        updateTextarea();
    }
}

function insertInternalLink() {
    const pages = <?php echo json_encode($all_pages); ?>;
    if (pages.length === 0) {
        alert('No pages available for linking.');
        return;
    }

    // Create a modal or dropdown for page selection
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;';
    modal.innerHTML = `
        <div style="background: white; padding: 20px; border-radius: 8px; max-width: 400px; width: 90%;">
            <h3 style="margin-top: 0;">Select a Page to Link To</h3>
            <select id="internal-page-select" style="width: 100%; padding: 8px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px;">
                <option value="">Choose a page...</option>
                <?php foreach ($all_pages as $page): ?>
                <option value="<?php echo SITE_URL; ?>page/<?php echo htmlspecialchars($page['slug']); ?>"><?php echo htmlspecialchars($page['title']); ?></option>
                <?php endforeach; ?>
            </select>
            <div style="text-align: right; margin-top: 15px;">
                <button id="cancel-link" style="margin-right: 10px; padding: 8px 16px; background: #ccc;">Cancel</button>
                <button id="insert-link" style="padding: 8px 16px; background: #000; color: white;">Insert Link</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    document.getElementById('insert-link').onclick = function() {
        const selectedPage = document.getElementById('internal-page-select').value;
        if (selectedPage) {
            document.execCommand('createLink', false, selectedPage);
            updateTextarea();
        }
        document.body.removeChild(modal);
    };

    document.getElementById('cancel-link').onclick = function() {
        document.body.removeChild(modal);
    };
}

function toggleCode() {
    const editor = document.getElementById('wysiwyg-editor');
    const textarea = document.getElementById('content');
    const container = document.getElementById('editor-container');
    const toolbar = document.getElementById('editor-toolbar');
    const btn = event.target;

    if (textarea.classList.contains('hidden')) {
        // Show HTML code
        textarea.classList.remove('hidden');
        editor.classList.add('hidden');
        textarea.value = editor.innerHTML;
        btn.textContent = 'WYSIWYG';
        btn.title = 'Switch to Visual Editor';
    } else {
        // Show visual editor
        textarea.classList.add('hidden');
        editor.classList.remove('hidden');
        editor.innerHTML = textarea.value;
        btn.textContent = 'HTML';
        btn.title = 'Show/Hide HTML';
    }
}

// Prevent form submission when clicking toolbar buttons
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('toolbar-btn')) {
        e.preventDefault();
    }
});

function previewContent() {
    const content = document.getElementById('content').value;
    document.getElementById('previewContent').innerHTML = content;
    document.getElementById('previewModal').classList.remove('hidden');
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.add('hidden');
}

// Add some basic styling to ensure proper formatting
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        #wysiwyg-editor h1 {
            font-size: 2em;
            font-weight: bold;
            margin: 0.67em 0;
        }
        #wysiwyg-editor h2 {
            font-size: 1.5em;
            font-weight: bold;
            margin: 0.75em 0;
        }
        #wysiwyg-editor h3 {
            font-size: 1.17em;
            font-weight: bold;
            margin: 0.83em 0;
        }
        #wysiwyg-editor p {
            margin: 1em 0;
        }
        #wysiwyg-editor ul, #wysiwyg-editor ol {
            margin-left: 2em;
            padding-left: 0;
        }
        #wysiwyg-editor li {
            margin-bottom: 0.5em;
        }
        #wysiwyg-editor a {
            color: #3b82f6;
            text-decoration: underline;
        }
        #wysiwyg-editor strong, #wysiwyg-editor b {
            font-weight: bold;
        }
        #wysiwyg-editor em, #wysiwyg-editor i {
            font-style: italic;
        }
        #wysiwyg-editor u {
            text-decoration: underline;
        }
        #wysiwyg-editor[contenteditable]:focus {
            outline: none;
        }
    `;
    document.head.appendChild(style);
});
</script>

<?php include 'footer.php'; ?>
