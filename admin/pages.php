<?php
require_once 'bootstrap.php';
$conn = get_db_connection();

// Pagination and filters
$per_page = 20; // Adjust as needed
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $per_page;

$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_title = isset($_GET['title']) ? trim($_GET['title']) : '';

// Build query with filters
$where_clauses = [];
$params = [];
$types = '';
if ($filter_status !== '') {
    $where_clauses[] = "status = ?";
    $params[] = $filter_status;
    $types .= 'i';
}
if (!empty($filter_title)) {
    $where_clauses[] = "title LIKE ?";
    $params[] = "%$filter_title%";
    $types .= 's';
}
$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total for pagination
$count_sql = "SELECT COUNT(*) as total FROM pages $where_sql";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_result = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_result = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_result / $per_page);

// Fetch pages
$sql = "SELECT id, title, slug, status FROM pages $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$pages = [];
while ($row = $result->fetch_assoc()) {
    $pages[] = $row;
}
if (!empty($params)) $stmt->close();

$pageTitle = "Manage Pages";
include 'header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">All Pages</h2>
        <a href="edit_page.php" class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">Add New Page</a>
    </div>

    <!-- Filters Form -->
    <form method="GET" class="mb-6 bg-gray-50 p-4 rounded-md">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($filter_title); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Search by title">
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select id="status" name="status" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Status</option>
                    <option value="1" <?php echo $filter_status === '1' ? 'selected' : ''; ?>>Published</option>
                    <option value="0" <?php echo $filter_status === '0' ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">Filter</button>
                <a href="pages.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">Clear</a>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($pages)): ?>
                    <?php foreach ($pages as $page): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($page['title']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">/<?php echo htmlspecialchars($page['slug']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="edit_page.php?id=<?php echo $page['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                <button data-action="delete"
                                        data-item-type="page"
                                        data-item-name="<?php echo htmlspecialchars($page['title']); ?>"
                                        data-item-id="<?php echo $page['id']; ?>"
                                        data-delete-url="delete_page.php"
                                        class="text-red-600 hover:text-red-900">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center text-gray-500 py-6">No pages found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>



<?php include 'footer.php'; ?>
