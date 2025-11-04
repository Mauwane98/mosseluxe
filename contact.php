<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include header, database connection, CSRF protection
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/csrf.php';
require_once 'includes/config.php'; // To get contact details
$conn = get_db_connection();

$contact_error = '';
$success_message = '';
$csrf_token = generate_csrf_token(); // Generate token for the form

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $contact_error = 'Invalid CSRF token. Please try again.';
    } else {
        // Sanitize and validate inputs
        $name = trim($_POST["name"]);
        $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
        $subject = trim($_POST["subject"]);
        $message = trim($_POST["message"]);

        // Basic validation
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $contact_error = 'Please fill out all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $contact_error = 'Invalid email format.';
        } else {
            // Prepare an insert statement for the messages table
            // Assuming a 'messages' table with columns: id, name, email, subject, message, received_at, is_read
            $received_at = date('Y-m-d H:i:s');
            $is_read = 0; // Default to unread

            $sql_insert_message = "INSERT INTO messages (name, email, subject, message, received_at, is_read) VALUES (?, ?, ?, ?, ?, ?)";
            
            if ($stmt_insert = $conn->prepare($sql_insert_message)) {
                // Bind variables to the prepared statement
                $stmt_insert->bind_param("sssssi", $param_name, $param_email, $param_subject, $param_message, $param_received_at, $param_is_read);

                // Set parameters
                $param_name = $name;
                $param_email = $email;
                $param_subject = $subject;
                $param_message = $message;
                $param_received_at = $received_at;
                $param_is_read = $is_read;

                // Attempt to execute the prepared statement
                if ($stmt_insert->execute()) {
                    $success_message = 'Your message has been sent successfully!';
                    // Optionally clear form fields or redirect
                } else {
                    $contact_error = 'Something went wrong. Please try again later.';
                }
                // Close statement
                $stmt_insert->close();
            } else {
                $contact_error = 'Something went wrong. Please try again later.';
            }
        }
    }
}

?>

<div class="container my-5 py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 text-center">
            <h1 class="display-4 mb-4">Get in Touch</h1>
            <p class="lead text-muted mb-5">Have a question or some feedback? We'd love to hear from you.</p>
        </div>
    </div>
    
    <div class="row">
        <!-- Contact Form -->
        <div class="col-lg-7 mb-5 mb-lg-0 pe-lg-5">
            <div class="card bg-transparent border-0 p-0 text-dark">
                <div class="card-body text-dark">
                    <h3 class="mb-4">Send Us a Message</h3>
                    
                    <?php if (!empty($contact_error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $contact_error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php elseif (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary-dark w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contact Details -->
        <div class="col-lg-5 ps-lg-5 border-start">
            <h3 class="mb-4">Contact Details</h3>
            <div class="d-flex align-items-start mb-4">
                <i class="bi bi-geo-alt-fill fs-4 me-3 text-dark"></i>
                <div>
                    <h5>Our Location</h5>
                    <p class="text-muted"><?php echo CONTACT_ADDRESS; ?></p>
                </div>
            </div>
            <div class="d-flex align-items-start mb-4">
                <i class="bi bi-envelope-fill fs-4 me-3 text-dark"></i>
                <div>
                    <h5>Email Us</h5>
                    <p class="text-muted"><?php echo SMTP_USERNAME; ?></p>
                </div>
            </div>
            <div class="d-flex align-items-start mb-4">
                <i class="bi bi-telephone-fill fs-4 me-3 text-dark"></i>
                <div>
                    <h5>Call Us</h5>
                    <p class="text-muted"><?php echo CONTACT_PHONE; ?></p>
                </div>
            </div>
             <div class="d-flex align-items-start">
                <i class="bi bi-clock-fill fs-4 me-3 text-dark"></i>
                <div>
                    <h5>Our Hours</h5>
                    <p class="text-muted">Monday - Friday: 9:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
