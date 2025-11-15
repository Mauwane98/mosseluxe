<?php

class ImageService {

    /**
     * Processes an uploaded image file, resizes it to fit within specified dimensions,
     * adds padding to make it square, and saves it as a WEBP file.
     *
     * @param array $file The uploaded file array from $_FILES.
     * @param string $target_dir The directory to save the processed image.
     * @param int $target_width The target width for the final image.
     * @param int $target_height The target height for the final image.
     * @param string &$error Reference to a variable to store error messages.
     * @return string|false The relative path to the saved image on success, or false on failure.
     */
    public static function processUpload($file, $target_dir_relative, $target_width, $target_height, &$error) {
        if (!extension_loaded('gd')) {
            $error = 'GD library is not enabled. Image processing is not available.';
            return false;
        }

        // Check file size (e.g., 20MB limit)
        if ($file['size'] > 20 * 1024 * 1024) {
            $error = 'File is too large. Please upload an image smaller than 20MB.';
            return false;
        }

        if (!defined('ABSPATH')) {
            define('ABSPATH', dirname(__DIR__));
        }
        $target_dir = ABSPATH . DIRECTORY_SEPARATOR . rtrim($target_dir_relative, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'An error occurred during file upload.';
            return false;
        }

        $image_info = getimagesize($file["tmp_name"]);
        if ($image_info === false) {
            $error = "File is not a valid image.";
            return false;
        }

        $mime = $image_info['mime'];
        $source_image = null;

        switch ($mime) {
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($file["tmp_name"]);
                break;
            case 'image/png':
                $source_image = imagecreatefrompng($file["tmp_name"]);
                break;
            case 'image/gif':
                $source_image = imagecreatefromgif($file["tmp_name"]);
                break;
            default:
                $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                return false;
        }

        if (!$source_image) {
            $error = "Could not create image resource.";
            return false;
        }

        // Get original dimensions
        $original_width = imagesx($source_image);
        $original_height = imagesy($source_image);

        // Calculate new dimensions to cover the target box while maintaining aspect ratio
        $ratio = $original_width / $original_height;
        if ($ratio > $target_width / $target_height) {
            // Original is wider relative to target, fit by height and crop width
            $new_height = $target_height;
            $new_width = $original_width * ($target_height / $original_height);
        } else {
            // Original is taller relative to target, fit by width and crop height
            $new_width = $target_width;
            $new_height = $original_height * ($target_width / $original_width);
        }

        // Create a new true color image (the canvas)
        $final_image = imagecreatetruecolor($target_width, $target_height);
        $background_color = imagecolorallocate($final_image, 255, 255, 255); // #FFFFFF white
        imagefill($final_image, 0, 0, $background_color); // Fill canvas with the white background

        // Preserve transparency for PNG files
        if ($mime == 'image/png') {
            imagealphablending($final_image, true);
            imagesavealpha($final_image, true);
        }

        // Calculate coordinates to center the resized image on the canvas
        $x_offset = ($target_width - $new_width) / 2;
        $y_offset = ($target_height - $new_height) / 2;

        // Copy and resize the original image onto the canvas
        imagecopyresampled($final_image, $source_image, $x_offset, $y_offset, 0, 0, $new_width, $new_height, $original_width, $original_height);

        // Generate a unique filename with .webp extension
        $unique_filename = uniqid() . '-' . pathinfo($file["name"], PATHINFO_FILENAME) . '.webp';
        $target_file_path = $target_dir . $unique_filename;
        $relative_path = rtrim($target_dir_relative, '/') . '/' . $unique_filename;

        imagewebp($final_image, $target_file_path, 80); // 80 is the quality

        // Free up memory
        imagedestroy($source_image);
        imagedestroy($final_image);

        return $relative_path;
    }
}
