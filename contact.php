<?php
$pageTitle = "Contact Us - Mossé Luxe";
require_once 'includes/bootstrap.php';

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

// Now include the header, after all PHP logic is processed
require_once 'includes/header.php';
?>

<!-- Main Content -->
    <!-- Contact Form & Details Section -->
    <section class="bg-white py-20 md:py-28">
        <div class="container mx-auto px-4 md:px-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 md:gap-16">
                <!-- Contact Form -->
                <div class="lg:col-span-7">
                    <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter mb-6">Send Us a Message</h2>
                    
                    <?php if (!empty($contact_error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                            <?php echo $contact_error; ?>
                        </div>
                    <?php elseif (!empty($success_message)): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div>
                            <label for="name" class="block text-sm font-medium text-black/80 mb-1">Full Name</label>
                            <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" class="w-full p-3 bg-neutral-50 border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-black/80 mb-1">Email Address</label>
                            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="w-full p-3 bg-neutral-50 border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label for="subject" class="block text-sm font-medium text-black/80 mb-1">Subject</label>
                            <input type="text" id="subject" name="subject" required value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>" class="w-full p-3 bg-neutral-50 border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label for="message" class="block text-sm font-medium text-black/80 mb-1">Message</label>
                            <textarea id="message" name="message" rows="5" required class="w-full p-3 bg-neutral-50 border border-black/20 rounded-md focus:outline-none focus:ring-2 focus:ring-black"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>
                        <input type="submit" value="Send Message" class="w-full bg-black text-white py-4 px-6 font-bold uppercase rounded-md hover:bg-black/80 transition-colors tracking-wider cursor-pointer">
                    </form>
                </div>

                <!-- Contact Details -->
                <div class="lg:col-span-5">
                        <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter mb-6">Contact Details</h2>
                        <div class="bg-neutral-50 p-8 rounded-lg">
                            <div class="space-y-6">
                                <!-- Location -->
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold uppercase tracking-wider mb-1">Our Location</h4>
                                        <p class="text-black/70">Pretoria<br>South Africa</p>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold uppercase tracking-wider mb-1">Email Us</h4>
                                        <p class="text-black/70">info@mosseluxe.co.za</p>
                                    </div>
                                </div>

                                <!-- Phone -->
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold uppercase tracking-wider mb-1">Call Us</h4>
                                        <p class="text-black/70">+27 67 616 0928<br>Monday - Friday: 9:00 AM - 5:00 PM</p>
                                    </div>
                                </div>

                                <!-- Social Links -->
                                <div class="mt-8">
                                    <h4 class="text-lg font-bold uppercase tracking-wider mb-4">Follow Us</h4>
                                    <div class="flex gap-4">
                                        <a href="https://instagram.com/mosseluxe" class="w-10 h-10 bg-black text-white rounded-full flex items-center justify-center hover:bg-gray-800 transition-colors">
                                            <i class="fab fa-instagram"></i>
                                        </a>
                                        <a href="https://twitter.com/mosseluxe" class="w-10 h-10 bg-black text-white rounded-full flex items-center justify-center hover:bg-gray-800 transition-colors">
                                            <i class="fab fa-twitter"></i>
                                        </a>
                                        <a href="https://facebook.com/mosseluxe" class="w-10 h-10 bg-black text-white rounded-full flex items-center justify-center hover:bg-gray-800 transition-colors">
                                            <i class="fab fa-facebook-f"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="bg-neutral-50 py-20 md:py-28">
        <div class="container mx-auto px-4 md:px-6 max-w-3xl">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter">Frequently Asked Questions</h2>
            </div>
            <div class="space-y-4" id="faq-accordion">
                <!-- FAQ Item 1 -->
                <div class="border-b border-black/10 pb-4">
                    <button class="faq-toggle flex justify-between items-center w-full text-left">
                        <span class="text-lg font-bold">What are your shipping options?</span>
                        <svg class="w-6 h-6 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">
                        <p class="pt-4 text-black/70">We offer standard and express shipping options nationwide. Standard shipping typically takes 3-5 business days, while express shipping takes 1-2 business days. All orders over R1500 qualify for free standard shipping.</p>
                    </div>
                </div>
                <!-- FAQ Item 2 -->
                <div class="border-b border-black/10 pb-4">
                    <button class="faq-toggle flex justify-between items-center w-full text-left">
                        <span class="text-lg font-bold">How can I track my order?</span>
                        <svg class="w-6 h-6 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">
                        <p class="pt-4 text-black/70">Once your order has been shipped, you will receive an email with a tracking number and a link to the courier's website. You can also use our <a href="track_order.php" class="font-bold underline hover:text-black">Track Order</a> page to check the status.</p>
                    </div>
                </div>
                <!-- FAQ Item 3 -->
                <div class="border-b border-black/10 pb-4">
                    <button class="faq-toggle flex justify-between items-center w-full text-left">
                        <span class="text-lg font-bold">What is your return policy?</span>
                        <svg class="w-6 h-6 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">
                        <p class="pt-4 text-black/70">We accept returns within 30 days of purchase for items that are unworn, unwashed, and in their original condition with all tags attached. Please visit our Shipping & Returns page for more detailed information on how to initiate a return.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
