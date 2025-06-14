<?php
/**
 * WordPress integration
 *
 * Handles integration with WordPress core functionality
 */

if (!defined('WPINC')) {
    die;
}

class WebP_WordPress_Integration {
    private $image_processor;
    private $debug_mode = false;

    /**
     * Constructor
     */
    public function __construct($image_processor, $debug_mode = false) {
        $this->image_processor = $image_processor;
        $this->debug_mode = $debug_mode;
    }

    /**
     * Add WebP as a supported mime type
     */
    public function add_webp_support() {
        add_filter('upload_mimes', function($mimes) {
            $mimes['webp'] = 'image/webp';
            return $mimes;
        });
    }


    /**
     * Replace image URLs in content with WebP versions
     *
     * @param string $content Post content
     * @return string Modified content
     */
    public function replace_image_urls($content) {
        if (!$this->image_processor->browser_supports_webp() || empty($content)) {
            return $content;
        }
        // Check if we're on a mobile device
        $is_mobile = wp_is_mobile();

        // Get upload directory info
        $upload_dir = wp_upload_dir();
        $upload_url = $upload_dir['baseurl'];
        $upload_path = $upload_dir['basedir'];

        // WebP base URL - points to wp-content/webp-images
        $webp_base_url = WP_CONTENT_URL . '/webp-images';

        // Process standard image tags with more comprehensive regex
        preg_match_all('/<img[^>]+src=([\'"])(?<src>[^\'"]*)\1[^>]*>/i', $content, $img_matches);
        if (!empty($img_matches['src'])) {
            foreach ($img_matches['src'] as $image_url) {
                // Skip external images and already processed WebP images
                if (strpos($image_url, $upload_url) === false || strpos($image_url, '.webp') !== false) {
                    continue;
                }

                $image_path = str_replace($upload_url, $upload_path, $image_url);

                // Use resize function for mobile devices
                if ($is_mobile) {
                    $webp_path = $this->image_processor->convert_to_webp_with_resize($image_path, 360);
                } else {
                    $webp_path = $this->image_processor->convert_to_webp($image_path);
                }

                if ($webp_path && file_exists($webp_path)) {
                    // Get the relative path from the original URL (not the file path)
                    $relative_url_path = str_replace($upload_url . '/', '', $image_url);

                    // Get just the directory part and filename for WebP
                    $path_info = pathinfo($relative_url_path);
                    $webp_filename = basename($webp_path);

                    // Construct WebP URL properly
                    if ($path_info['dirname'] && $path_info['dirname'] !== '.') {
                        $webp_url = $webp_base_url . '/' . $path_info['dirname'] . '/' . $webp_filename;
                    } else {
                        $webp_url = $webp_base_url . '/' . $webp_filename;
                    }

                    // Clean up any double slashes
                    $webp_url = preg_replace('#/+#', '/', $webp_url);
                    $webp_url = str_replace(':/', '://', $webp_url);

                    $content = str_replace($image_url, $webp_url, $content);
                    if ($this->debug_mode) {
                        $this->image_processor->add_debug_message("Original URL: $image_url");
                        $this->image_processor->add_debug_message("Relative path: $relative_url_path");
                        $this->image_processor->add_debug_message("WebP URL: $webp_url");
                    }
                }
            }
        }

        // Process srcset attributes
        preg_match_all('/srcset=([\'"])([^\'"]*)\1/i', $content, $srcset_matches);
        if (!empty($srcset_matches[2])) {
            foreach ($srcset_matches[2] as $srcset_value) {
                // Parse individual srcset entries (format: "url width" or "url density")
                $srcset_entries = preg_split('/,\s*/', $srcset_value);
                $updated_entries = array();

                foreach ($srcset_entries as $entry) {
                    // Split entry into URL and descriptor (width/density)
                    $parts = preg_split('/\s+/', trim($entry));
                    if (count($parts) >= 1) {
                        $entry_url = $parts[0];
                        $descriptor = isset($parts[1]) ? $parts[1] : '';

                        // Skip external images and already processed WebP images
                        if (strpos($entry_url, $upload_url) === false || strpos($entry_url, '.webp') !== false) {
                            $updated_entries[] = $entry;
                            continue;
                        }

                        $image_path = str_replace($upload_url, $upload_path, $entry_url);

                        // Use resize function for mobile devices
                        if ($is_mobile) {
                            $webp_path = $this->image_processor->convert_to_webp_with_resize($image_path, 360);
                        } else {
                            $webp_path = $this->image_processor->convert_to_webp($image_path);
                        }

                        if ($webp_path && file_exists($webp_path)) {
                            // Get the relative path from the original URL (not the file path)
                            $relative_url_path = str_replace($upload_url . '/', '', $entry_url);

                            // Get just the directory part and filename for WebP
                            $path_info = pathinfo($relative_url_path);
                            $webp_filename = basename($webp_path);

                            // Construct WebP URL properly
                            if ($path_info['dirname'] && $path_info['dirname'] !== '.') {
                                $webp_url = $webp_base_url . '/' . $path_info['dirname'] . '/' . $webp_filename;
                            } else {
                                $webp_url = $webp_base_url . '/' . $webp_filename;
                            }

                            // Clean up any double slashes
                            $webp_url = preg_replace('#/+#', '/', $webp_url);
                            $webp_url = str_replace(':/', '://', $webp_url);

                            // Reconstruct the srcset entry
                            $updated_entry = $webp_url . ($descriptor ? ' ' . $descriptor : '');
                            $updated_entries[] = $updated_entry;

                            if ($this->debug_mode) {
                                $this->image_processor->add_debug_message("Srcset URL: $entry_url");
                                $this->image_processor->add_debug_message("WebP URL: $webp_url");
                            }
                        } else {
                            // Keep original entry if WebP conversion failed
                            $updated_entries[] = $entry;
                        }
                    } else {
                        // Keep malformed entries as-is
                        $updated_entries[] = $entry;
                    }
                }

                // Replace the entire srcset value
                $new_srcset_value = implode(', ', $updated_entries);
                $content = str_replace($srcset_value, $new_srcset_value, $content);
            }
        }

        // Process background images in CSS
        preg_match_all('/background-image:\s*url\([\'"]?([^\'"()]+)[\'"]?\)/i', $content, $bg_matches);
        if (!empty($bg_matches[1])) {
            foreach ($bg_matches[1] as $bg_url) {
                if (strpos($bg_url, $upload_url) === false || strpos($bg_url, '.webp') !== false) {
                    continue;
                }

                $image_path = str_replace($upload_url, $upload_path, $bg_url);

                if ($is_mobile) {
                    $webp_path = $this->image_processor->convert_to_webp_with_resize($image_path, 360);
                } else {
                    $webp_path = $this->image_processor->convert_to_webp($image_path);
                }

                if ($webp_path && file_exists($webp_path)) {
                    // Get the relative path from the original URL (not the file path)
                    $relative_url_path = str_replace($upload_url . '/', '', $bg_url);

                    // Get just the directory part and filename for WebP
                    $path_info = pathinfo($relative_url_path);
                    $webp_filename = basename($webp_path);

                    // Construct WebP URL properly
                    if ($path_info['dirname'] && $path_info['dirname'] !== '.') {
                        $webp_url = $webp_base_url . '/' . $path_info['dirname'] . '/' . $webp_filename;
                    } else {
                        $webp_url = $webp_base_url . '/' . $webp_filename;
                    }

                    // Clean up any double slashes
                    $webp_url = preg_replace('#/+#', '/', $webp_url);
                    $webp_url = str_replace(':/', '://', $webp_url);

                    $content = str_replace($bg_url, $webp_url, $content);

                    if ($this->debug_mode) {
                        $this->image_processor->add_debug_message("Background URL: $bg_url");
                        $this->image_processor->add_debug_message("WebP URL: $webp_url");
                    }
                }
            }
        }

        // Process data-src attributes (for lazy loading)
        preg_match_all('/data-src=([\'"])(?<src>[^\'"]*)\1/i', $content, $data_src_matches);
        if (!empty($data_src_matches['src'])) {
            foreach ($data_src_matches['src'] as $data_src_url) {
                if (strpos($data_src_url, $upload_url) === false || strpos($data_src_url, '.webp') !== false) {
                    continue;
                }

                $image_path = str_replace($upload_url, $upload_path, $data_src_url);

                if ($is_mobile) {
                    $webp_path = $this->image_processor->convert_to_webp_with_resize($image_path, 360);
                } else {
                    $webp_path = $this->image_processor->convert_to_webp($image_path);
                }

                if ($webp_path && file_exists($webp_path)) {
                    // Get the relative path from the original URL (not the file path)
                    $relative_url_path = str_replace($upload_url . '/', '', $data_src_url);

                    // Get just the directory part and filename for WebP
                    $path_info = pathinfo($relative_url_path);
                    $webp_filename = basename($webp_path);

                    // Construct WebP URL properly
                    if ($path_info['dirname'] && $path_info['dirname'] !== '.') {
                        $webp_url = $webp_base_url . '/' . $path_info['dirname'] . '/' . $webp_filename;
                    } else {
                        $webp_url = $webp_base_url . '/' . $webp_filename;
                    }

                    // Clean up any double slashes
                    $webp_url = preg_replace('#/+#', '/', $webp_url);
                    $webp_url = str_replace(':/', '://', $webp_url);

                    $content = str_replace($data_src_url, $webp_url, $content);

                    if ($this->debug_mode) {
                        $this->image_processor->add_debug_message("Data-src URL: $data_src_url");
                        $this->image_processor->add_debug_message("WebP URL: $webp_url");
                    }
                }
            }
        }

        return $content;
    }
}