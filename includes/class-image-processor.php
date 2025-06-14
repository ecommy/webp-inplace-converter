<?php
/**
 * Image processing functionality
 *
 * Handles WebP conversion and image resizing
 */

if (!defined('WPINC')) {
    die;
}

class WebP_Image_Processor {
    private $debug_mode = false;
    private $debug_messages = array();

    /**
     * Constructor
     */
    public function __construct($debug_mode = false) {
        $this->debug_mode = $debug_mode;
    }

    /**
     * Convert image to WebP format
     *
     * @param string $image_path Original image path
     * @return string|bool WebP image path or false on failure
     */
    public function convert_to_webp($image_path) {
        // Use the resizing function with no max width
        return $this->convert_to_webp_with_resize($image_path, 1980);
    }

    /**
     * Convert image to WebP format with resizing
     *
     * @param string $image_path Original image path
     * @param int $max_width Maximum width for the image (0 for no resizing)
     * @return string|bool WebP image path or false on failure
     */
    public function convert_to_webp_with_resize($image_path, $max_width) {
        // Check if file exists
        $image_path = strtok($image_path, '?');

        if (!file_exists($image_path)) {
            if ($this->debug_mode) {
                $this->add_debug_message("File not found: $image_path");
            }
            return false;
        }

        // Get file info
        $pathinfo = pathinfo($image_path);
        if (!isset($pathinfo['extension']) || !isset($pathinfo['filename'])) {
            return false;
        }

        // Check if GD extension is loaded
        if (!extension_loaded('gd')) {
            if ($this->debug_mode) {
                $this->add_debug_message("GD extension not loaded");
            }
            return false;
        }

        // Get upload directory
        $upload_dir = wp_upload_dir();

        // Extract the relative path from uploads directory
        $relative_path = str_replace($upload_dir['basedir'] . '/', '', $image_path);

        // Create path for WebP image
        $webp_base_dir = WP_CONTENT_DIR . '/webp-images';
        $webp_dir = $webp_base_dir . '/' . dirname($relative_path);
        $webp_filename = $pathinfo['filename'] . ($max_width ? "_w{$max_width}" : '') . '.webp';
        $webp_path = $webp_dir . '/' . $webp_filename;

        // Debug log the paths
        if ($this->debug_mode) {
            $this->add_debug_message("Original image: $image_path");
            $this->add_debug_message("WebP will be stored at: $webp_path");
            $this->add_debug_message("Relative path: $relative_path");
        }


        // Create directory if it doesn't exist
        if (!file_exists($webp_dir)) {
            wp_mkdir_p($webp_dir);
        }

        // If WebP file already exists, return its path
        if (file_exists($webp_path)) {
            return $webp_path;
        }

        // Create WebP image based on original extension
        $ext = strtolower($pathinfo['extension']);

        // Skip if already webp
        if ($ext === 'webp') {
            return $image_path;
        }

        // Create image resource
        $image = null;
        switch ($ext) {
            case 'jpeg':
            case 'jpg':
                $image = @imagecreatefromjpeg($image_path);
                break;
            case 'png':
                $image = @imagecreatefrompng($image_path);
                if ($image) {
                    // Handle transparency
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
                break;
            case 'gif':
                $image = @imagecreatefromgif($image_path);
                if ($image) {
                    // Handle transparency
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
                break;
            default:
                if ($this->debug_mode) {
                    $this->add_debug_message("Unsupported file format: $ext");
                }
                return false;
        }

        if (!$image) {
            if ($this->debug_mode) {
                $this->add_debug_message("Failed to create image resource from: $image_path");
            }
            return false;
        }

        // Get current dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        // Only resize if the image is larger than max width and max width is specified
        if ($max_width && $width > $max_width) {
            // Calculate new height to maintain aspect ratio
            $new_height = floor($height * ($max_width / $width));

            // Create a new image with the new dimensions
            $new_image = imagecreatetruecolor($max_width, $new_height);

            // Handle transparency for PNG and GIF
            if ($ext === 'png' || $ext === 'gif') {
                imagealphablending($new_image, false);
                imagesavealpha($new_image, true);
                $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
                imagefilledrectangle($new_image, 0, 0, $max_width, $new_height, $transparent);
            }

            // Resize the image
            imagecopyresampled($new_image, $image, 0, 0, 0, 0, $max_width, $new_height, $width, $height);

            // Free memory from the original image
            imagedestroy($image);
            $image = $new_image;

            if ($this->debug_mode) {
                $this->add_debug_message("Resized image from {$width}x{$height} to {$max_width}x{$new_height}");
            }
        }

        // Convert to WebP
        $result = imagewebp($image, $webp_path, 90);
        imagedestroy($image);

        if ($result) {
            if ($this->debug_mode) {
                $this->add_debug_message("Successfully converted and resized: $image_path to WebP");
            }
            return $webp_path;
        }

        if ($this->debug_mode) {
            $this->add_debug_message("Failed to convert and resize: $image_path to WebP");
        }
        return false;
    }

    /**
     * Create WebP directory in uploads
     */
    public function create_webp_dir() {
        $upload_dir = wp_upload_dir();
        $webp_dir = WP_CONTENT_DIR . '/webp-images';

        if (!file_exists($webp_dir)) {
            wp_mkdir_p($webp_dir);
            // Create .htaccess to protect directory listing
            file_put_contents($webp_dir . '/.htaccess', 'Options -Indexes' . PHP_EOL);
        }
    }

    /**
     * Recursively delete a directory and its contents
     */
    public function recursive_delete_directory($dir) {
        if (!file_exists($dir)) {
            return;
        }

        // Initialize WP_Filesystem
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }

        // Use WP_Filesystem to delete the directory recursively
        $wp_filesystem->rmdir($dir, true);
    }


    /**
     * Check if browser supports WebP
     *
     * @return bool Whether WebP is supported
     */
    public function browser_supports_webp() {
        // Check Accept header
        $http_accept = isset($_SERVER['HTTP_ACCEPT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT'])) : '';
        if (!empty($http_accept) && strpos($http_accept, 'image/webp') !== false) {
            return true;
        }

        // Check User-Agent for Chrome, Edge, Firefox, etc.
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = sanitize_textarea_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));

            // Chrome 9+, Opera 12+
            if (preg_match('/Chrome\/([0-9]+)/', $ua, $matches) && (int)$matches[1] >= 9) {
                return true;
            }

            // Edge 18+
            if (preg_match('/Edge\/([0-9]+)/', $ua, $matches) && (int)$matches[1] >= 18) {
                return true;
            }

            // Firefox 65+
            if (preg_match('/Firefox\/([0-9]+)/', $ua, $matches) && (int)$matches[1] >= 65) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add debug message
     */
    public function add_debug_message($message) {
        $this->debug_messages[] = $message;
    }

    /**
     * Log debug info
     */
    public function log_debug_info() {
        if (!empty($this->debug_messages) && (defined('WP_DEBUG') && WP_DEBUG)) {
            foreach ($this->debug_messages as $message) {
                wp_debug_log('WebP Converter: ' . $message);
            }
        }
    }

    /**
     * Get debug messages
     */
    public function get_debug_messages() {
        return $this->debug_messages;
    }
}