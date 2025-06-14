<?php
/**
 * Admin functionality
 *
 * Handles all admin-related functionality for the plugin
 */

if (!defined('WPINC')) {
    die;
}

class WebP_Inplace_Converter_Admin {

    private $plugin_name;
    private $version;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        $this->plugin_name = 'webp-inplace-converter';
        $this->version = WEBP_INPLACE_CONVERTER_VERSION;

        // Add admin menu and settings
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Add settings link to plugins page
        add_filter('plugin_action_links_webp-inplace-converter/webp-inplace-converter.php',
            array($this, 'add_settings_link'));
    }

    /**
     * Register the admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            'WebP Image Converter',
            'WebP Images',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_settings_page')
        );
    }

    /**
     * Add settings link to plugin page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=' . $this->plugin_name . '">' . __('Settings', 'webp-inplace-converter') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Display the settings page
     */
    public function display_settings_page() {
        // Get upload directory info for statistics
        $webp_dir = WP_CONTENT_DIR . '/webp-images';

        $webp_count = 0;
        $webp_size = 0;

        if (file_exists($webp_dir)) {
            // Get count and size of WebP files
            $webp_count = $this->count_files_in_directory($webp_dir);
            $webp_size = $this->get_directory_size($webp_dir);
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <style>
                .webp-cards-container {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 20px;
                    margin-top: 20px;
                }
                .webp-cards-container .card {
                    flex: 1;
                    min-width: 300px;
                    box-sizing: border-box;
                    margin: 0;
                }
                @media (max-width: 960px) {
                    .webp-cards-container {
                        flex-direction: column;
                    }
                }
            </style>

            <div class="webp-cards-container">
                <div class="card">
                    <h2><?php _e('WebP Inplace Converter Settings', 'webp-inplace-converter'); ?></h2>
                    <p><?php _e('WebP Inplace Converter automatically converts your images to WebP format when they are displayed to browsers that support it.', 'webp-inplace-converter'); ?></p>

                    <h3><?php _e('Statistics', 'webp-inplace-converter'); ?></h3>
                    <p>
                        <?php _e('Total WebP images:', 'webp-inplace-converter'); ?> <strong><?php echo number_format($webp_count); ?></strong><br>
                        <?php _e('WebP images size:', 'webp-inplace-converter'); ?> <strong><?php echo $this->format_size($webp_size); ?></strong>
                    </p>

                    <h3><?php _e('Actions', 'webp-inplace-converter'); ?></h3>
                    <p>
                        <a href="<?php echo admin_url('options-general.php?page=' . $this->plugin_name . '&webp_regenerate=1'); ?>" class="button button-primary">
                            <?php _e('Regenerate All WebP Images', 'webp-inplace-converter'); ?>
                        </a>
                        <span class="description"><?php _e('This will delete all existing WebP images and regenerate them when they are requested.', 'webp-inplace-converter'); ?></span>
                    </p>
                </div>

                <div class="card">
                    <h2><?php _e('Browser Support', 'webp-inplace-converter'); ?></h2>
                    <p><?php _e('WebP is supported by most modern browsers:', 'webp-inplace-converter'); ?></p>
                    <ul>
                        <li><?php _e('Chrome (version 9+)', 'webp-inplace-converter'); ?></li>
                        <li><?php _e('Firefox (version 65+)', 'webp-inplace-converter'); ?></li>
                        <li><?php _e('Edge (version 18+)', 'webp-inplace-converter'); ?></li>
                        <li><?php _e('Opera (version 12+)', 'webp-inplace-converter'); ?></li>
                        <li><?php _e('Safari (version 14+)', 'webp-inplace-converter'); ?></li>
                    </ul>
                    <p><?php _e('For browsers that do not support WebP, the original image format will be used.', 'webp-inplace-converter'); ?></p>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Count files in a directory recursively
     */
    private function count_files_in_directory($dir) {
        if (!file_exists($dir)) {
            return 0;
        }

        $count = 0;
        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $count += $this->count_files_in_directory($path);
            } else {
                if (pathinfo($path, PATHINFO_EXTENSION) === 'webp') {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Get directory size recursively
     */
    private function get_directory_size($dir) {
        if (!file_exists($dir)) {
            return 0;
        }

        $size = 0;
        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $size += $this->get_directory_size($path);
            } else {
                if (pathinfo($path, PATHINFO_EXTENSION) === 'webp') {
                    $size += filesize($path);
                }
            }
        }

        return $size;
    }

    /**
     * Format file size into human readable format
     */
    private function format_size($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}

// Initialize admin class
$webp_inplace_converter_admin = new WebP_Inplace_Converter_Admin();
