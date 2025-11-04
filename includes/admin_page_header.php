<?php
// This file centralizes the HTML head for admin pages to reduce duplication.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Mossé Luxe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin_style.css?v=<?php echo time(); ?>">
    <style>
        /* Ensure admin images are fully visible */
        .product-image-preview, .item-image-preview {
            max-width: 150px;
            height: 150px; 
            object-fit: cover; /* Fill the container, may crop slightly */
            margin-top: 10px;
            border-radius: 0.25rem;
            border: 1px solid #444;
        }
        .product-image-thumbnail, .item-image-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover; /* Fill the container, may crop slightly */
            border-radius: 0.25rem;
        }
    </style>
</head>