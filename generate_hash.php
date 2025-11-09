<?php
// --- IMPORTANT ---
// 1. Change 'YourNewSecurePassword123!' to the password you want to use.
// 2. Place this file in your `htdocs/mosse-luxe/` folder.
// 3. Access it in your browser: http://localhost/mosse-luxe/generate_hash.php
// 4. Copy the generated hash.
// 5. Paste the hash into the `password` column for your admin user in the database via phpMyAdmin.
// 6. DELETE THIS FILE from your server immediately after use for security.

// --- CHOOSE YOUR PASSWORD HERE ---
$passwordToHash = 'YourNewSecurePassword123!';

// Generate the hash using PHP's recommended algorithm
$hashedPassword = password_hash($passwordToHash, PASSWORD_DEFAULT);

// Display the result in a clean format
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-100 flex items-center justify-center h-screen p-4">
    <div class="w-full max-w-2xl bg-white shadow-lg rounded-lg p-8 text-center">
        <h1 class="text-2xl font-black uppercase tracking-tighter mb-4">Password Hash Generator</h1>
        <p class="text-gray-600 mb-6">Use this tool to securely hash your admin password. Follow the steps carefully.</p>
        
        <div class="text-left mb-4">
            <p class="font-semibold">Password to Hash:</p>
            <p class="text-gray-700 bg-gray-100 p-2 rounded break-words"><?php echo htmlspecialchars($passwordToHash); ?></p>
        </div>
        
        <div class="text-left mb-6">
            <p class="font-semibold text-green-700">Generated Hash (Copy this):</p>
            <p class="text-lg font-mono bg-green-50 p-4 rounded break-words text-green-800"><?php echo htmlspecialchars($hashedPassword); ?></p>
        </div>
        
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 text-left" role="alert">
            <p class="font-bold">Security Warning!</p>
            <p>For your security, please delete this file (<code>generate_hash.php</code>) from your server immediately after you have copied the hash.</p>
        </div>
    </div>
</body>
</html>