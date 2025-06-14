<?php
/**
 * Main plugin class
 *
 * Coordinates all plugin functionality
 */

if (!defined('WPINC')) {
    die;
}

class WebP_Inplace_Converter {

    public $debug_mode = false;
    private $image_processor;
    private $wordpress_integration;

    /**
     * Initialize the plugin
     */
    public function init() {

        // Initialize image processor
        $this->image_processor = new WebP_Image_Processor($this->debug_mode);

        // Create a WebP directory if it doesn't exist
        $this->image_processor->create_webp_dir();

        // Initialize WordPress integration
        $this->wordpress_integration = new WebP_WordPress_Integration($this->image_processor, $this->debug_mode);
        $this->wordpress_integration->add_webp_support();

        // Register admin actions
        add_action('admin_init', array($this, 'handle_admin_actions'));

        // Use output buffer as the final safeguard for anything we missed
        add_action('wp_head', array($this, 'buffer_start'), 1);
        add_action('wp_footer', array($this, 'buffer_end'), 9999);
    }


    /**
     * Start output buffer
     */
    public function buffer_start() {
        ob_start(array($this, 'process_final_output'));
    }

    /**
     * End output buffer
     */
    public function buffer_end() {
        if (ob_get_level()) {
            ob_end_flush();
        }
    }

    /**
     * Process final HTML output
     */
    public function process_final_output($html) {
        // Only process if browser supports WebP
        if (!$this->image_processor->browser_supports_webp()) {
            return $html;
        }

        // Process standard images first
        $html = $this->wordpress_integration->replace_image_urls($html);
        // Log debug info if enabled
        if ($this->debug_mode) {
            $this->image_processor->log_debug_info();
        }

        return $html;
    }

    /**
     * Handle admin actions including regeneration
     */
    public function handle_admin_actions() {
        if (current_user_can('manage_options') && isset($_GET['webp_regenerate']) && $_GET['webp_regenerate'] === '1' && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'webp_regenerate_action')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>All WebP images have been cleared.</p></div>';
            });
        }
    }

    /**
     * Regenerate all WebP images using the new directory structure
     */
    public function regenerate_all_webp_images() {
        // Get upload directory info
        $webp_dir = WP_CONTENT_DIR . '/webp-images';

        // Delete the entire WebP images directory to force regeneration
        if (file_exists($webp_dir)) {
            $this->image_processor->recursive_delete_directory($webp_dir);
        }

        // Recreate the directory
        $this->image_processor->create_webp_dir();

        if ($this->debug_mode) {
            $this->image_processor->add_debug_message("Deleted all WebP images to force regeneration with new naming scheme");
        }
    }

    /**
     * Static activation method
     */
    public static function activate() {
        // Load required files if not already loaded
        if (!class_exists('WebP_Image_Processor')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-image-processor.php';
        }

        // Create WebP directory
        $processor = new WebP_Image_Processor();
        $processor->create_webp_dir();
    }

    /**
     * Static deactivation method
     */
    public static function deactivate() {
        // Load required files if not already loaded
        if (!class_exists('WebP_Image_Processor')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-image-processor.php';
        }

        // Optionally clean up WebP images on deactivation
        // This is commented out to avoid data loss on deactivation
         $processor = new WebP_Image_Processor();
         $processor->recursive_delete_directory(WP_CONTENT_DIR . '/webp-images');
    }

}