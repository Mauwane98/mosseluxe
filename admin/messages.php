<?php
// Start session and include admin authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf.php'; // For CSRF protection on delete action
$conn = get_db_connection();

$messages = [];
$message_details = null;
$message_id_to_view = null;
$message_id_to_delete = null;

// Fetch messages from the database
// Assuming 'messages' table has columns: id, name, email, subject, message, received_at, is_read
$sql_messages = "SELECT id, name, email, subject, received_at, is_read FROM messages ORDER BY received_at DESC";

if ($stmt = $conn->prepare($sql_messages)) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
    } else {
        error_log("Error executing messages query: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Error preparing messages query: " . $conn->error);
}

// Handle POST requests for actions like delete or mark as read
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token!');
    }

    $action = $_POST['action'] ?? '';
    $message_id = isset($_POST['message_id']) ? filter_var(trim($_POST['message_id']), FILTER_SANITIZE_NUMBER_INT) : null;

    if ($message_id) {
        if ($action === 'delete') {
            // Prepare delete statement
            $sql_delete_message = "DELETE FROM messages WHERE id = ?";
            if ($stmt_delete = $conn->prepare($sql_delete_message)) {
                $stmt_delete->bind_param("i", $param_id);
                $param_id = $message_id;
                if ($stmt_delete->execute()) {
                    // Redirect back to messages page with success message
                    header("Location: messages.php?success=message_deleted");
                    exit();
                } else {
                    error_log("Error executing delete message query: " . $stmt_delete->error);
                    header("Location: messages.php?error=delete_failed");
                    exit();
                }
                $stmt_delete->close();
            } else {
                error_log("Error preparing delete message query: " . $conn->error);
                header("Location: messages.php?error=prepare_failed");
                exit();
            }
        } elseif ($action === 'mark_read') {
            // Prepare update statement to mark message as read
            $sql_mark_read = "UPDATE messages SET is_read = 1 WHERE id = ?";
            if ($stmt_mark_read = $conn->prepare($sql_mark_read)) {
                $stmt_mark_read->bind_param("i", $param_id);
                $param_id = $message_id;
                if ($stmt_mark_read->execute()) {
                    // Redirect back to messages page with success message
                    header("Location: messages.php?success=message_marked_read");
                    exit();
                } else {
                    error_log("Error executing mark read query: " . $stmt_mark_read->error);
                    header("Location: messages.php?error=mark_read_failed");
                    exit();
                }
                $stmt_mark_read->close();
            } else {
                error_log("Error preparing mark read query: " . $conn->error);
                header("Location: messages.php?error=prepare_failed");
                exit();
            }
        }
    }
}

// Handle GET request for viewing a specific message
if (isset($_GET['view']) && !empty($_GET['view'])) {
    $message_id_to_view = filter_var(trim($_GET['view']), FILTER_SANITIZE_NUMBER_INT);
    // Fetch the specific message details
    $sql_view_message = "SELECT * FROM messages WHERE id = ?";
    if ($stmt_view = $conn->prepare($sql_view_message)) {
        $stmt_view->bind_param("i", $param_view_id);
        $param_view_id = $message_id_to_view;
        if ($stmt_view->execute()) {
            $result_view = $stmt_view->get_result();
            if ($row_view = $result_view->fetch_assoc()) {
                $message_details = $row_view;
                // Mark message as read if it's not already
                if ($message_details['is_read'] == 0) {
                    $sql_mark_read_get = "UPDATE messages SET is_read = 1 WHERE id = ?";
                    if ($stmt_mark_read_get = $conn->prepare($sql_mark_read_get)) {
                        $stmt_mark_read_get->bind_param("i", $param_view_id);
                        $stmt_mark_read_get->execute(); // Don't need to check result here, just attempt
                        $stmt_mark_read_get->close();
                    }
                }
            }
        }
        $stmt_view->close();
    }
}

$csrf_token = generate_csrf_token();
$active_page = 'messages';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Messages - Mossé Luxe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <?php 
    $page_title = 'Contact Messages';
    include '../includes/admin_header.php'; 
    ?>

    <?php if ($message_details): ?>
        <!-- Message Detail View -->
        <div class="card p-4 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4><?php echo htmlspecialchars($message_details['subject']); ?></h4>
                        <p class="text-muted mb-0">From: <?php echo htmlspecialchars($message_details['name']); ?> &lt;<?php echo htmlspecialchars($message_details['email']); ?>&gt;</p>
                        <p class="text-muted small">Received: <?php echo date('d M Y, H:i', strtotime($message_details['received_at'])); ?></p>
                    </div>
                    <div>
                        <!-- Mark as Read Button -->
                        <?php if ($message_details['is_read'] == 0): ?>
                            <form action="messages.php" method="POST" class="d-inline" onsubmit="return confirm('Mark this message as read?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="message_id" value="<?php echo $message_details['id']; ?>">
                                <input type="hidden" name="action" value="mark_read">
                                <button type="submit" class="btn btn-sm btn-outline-dark"><i class="bi bi-check-lg"></i> Mark as Read</button>
                            </form>
                        <?php endif; ?>
                        <!-- Delete Button -->
                        <button class="btn btn-sm btn-outline-danger ms-2" data-bs-toggle="modal" data-bs-target="#deleteMessageModal<?php echo $message_details['id']; ?>"><i class="bi bi-trash-fill"></i> Delete</button>
                    </div>
                </div>
                <hr>
                <p class="lead"><?php echo nl2br(htmlspecialchars($message_details['message'])); ?></p>

                <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deleteMessageModal<?php echo $message_details['id']; ?>" tabindex="-1" aria-labelledby="deleteMessageModalLabel<?php echo $message_details['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteMessageModalLabel<?php echo $message_details['id']; ?>">Confirm Deletion</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to delete this message from <?php echo htmlspecialchars($message_details['name']); ?>? This action cannot be undone.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <form action="messages.php" method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="message_id" value="<?php echo $message_details['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-danger">Delete Message</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Messages List Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Received</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $message): ?>
                        <tr class="<?php echo ($message['is_read'] == 0) ? 'unread' : ''; ?>">
                            <td><?php echo htmlspecialchars($message['name']); ?></td>
                            <td><?php echo htmlspecialchars($message['email']); ?></td>
                            <td><?php echo htmlspecialchars($message['subject']); ?></td>
                            <td><?php echo date('d M Y, H:i', strtotime($message['received_at'])); ?></td>
                            <td>
                                <?php if ($message['is_read'] == 0): ?>
                                    <span class="badge bg-warning text-dark">Unread</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Read</span>
                                <?php endif; ?>
                            </td>
                            <td class="message-actions">
                                <!-- View Button -->
                                <a href="messages.php?view=<?php echo $message['id']; ?>" class="btn btn-sm btn-outline-dark me-1"><i class="bi bi-eye-fill"></i> View</a>
                                
                                <!-- Mark as Read Button (if unread) -->
                                <?php if ($message['is_read'] == 0): ?>
                                    <form action="messages.php" method="POST" class="d-inline" onsubmit="return confirm('Mark this message as read?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                        <input type="hidden" name="action" value="mark_read">
                                        <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-check-lg"></i> Mark Read</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Delete Button -->
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteMessageModal<?php echo $message['id']; ?>"><i class="bi bi-trash-fill"></i> Delete</button>

                                <!-- Delete Confirmation Modal -->
                                <div class="modal fade" id="deleteMessageModal<?php echo $message['id']; ?>" tabindex="-1" aria-labelledby="deleteMessageModalLabel<?php echo $message['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteMessageModalLabel<?php echo $message['id']; ?>">Confirm Deletion</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete this message from <?php echo htmlspecialchars($message['name']); ?>? This action cannot be undone.
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form action="messages.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-danger">Delete Message</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No messages received yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
