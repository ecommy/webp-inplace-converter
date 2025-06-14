=== WebP Inplace Converter ===
Contributors: ecommy
Tags: webp, images, performance, optimization, converter
Requires at least: 5.0
Tested up to: 6.8.1
Stable tag: 1.0.5
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically converts and serves images in WebP format to browsers that support it, improving website performance and loading times.

== Description ==

WebP Inplace Converter automatically converts your website's images to WebP format and serves them to compatible browsers, while falling back to original formats for browsers that don't support WebP.

WebP is a modern image format that provides superior lossless and lossy compression for images on the web. WebP images are typically 25-35% smaller than comparable JPEG or PNG images, leading to faster page loads and better user experience.

= Key Features =

* **Automatic Conversion**: Automatically converts JPEG, PNG, and GIF images to WebP format on-the-fly
* **Browser Detection**: Only serves WebP images to browsers that support the format
* **Responsive Images Support**: Handles srcset attributes for responsive images
* **Background Images**: Converts CSS background images
* **Lazy Loading Support**: Works with lazy-loaded images (data-src attributes)
* **Mobile Optimization**: Serves smaller images to mobile devices
* **No Configuration Needed**: Works out of the box with no complex setup
* **Admin Dashboard**: Simple admin interface with statistics and regeneration option

= Benefits =

* **Faster Page Loading**: Smaller image sizes mean faster page loads
* **Improved SEO**: Page speed is a ranking factor for search engines
* **Better User Experience**: Faster sites provide better user experience
* **Reduced Bandwidth Usage**: Smaller images mean less bandwidth consumption
* **No Quality Loss**: WebP maintains high image quality despite smaller file sizes

= How It Works =

1. When a page is loaded, the plugin checks if the visitor's browser supports WebP
2. If WebP is supported, the plugin converts images to WebP format (if not already converted)
3. The plugin then serves the WebP version instead of the original
4. For browsers that don't support WebP, the original images are served

The plugin creates a dedicated directory (wp-content/webp-images) to store the WebP versions of your images, maintaining the original directory structure.

== Installation ==

1. Upload the `webp-inplace-converter` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's it! The plugin works automatically with no additional configuration

= Requirements =

* PHP 7.0 or higher
* GD library with WebP support
* Write permissions for the wp-content directory

== Frequently Asked Questions ==

= Do I need to convert my existing images manually? =

No, the plugin converts images automatically when they are requested by a browser that supports WebP. There's no need for manual conversion.

= Will this plugin slow down my website? =

No, the plugin is designed to be lightweight and efficient. Images are converted only once and then cached, so there's no performance impact on subsequent page loads.

= What happens if a browser doesn't support WebP? =

For browsers that don't support WebP, the original images (JPEG, PNG, GIF) will be served automatically.

= Can I see how many images have been converted? =

Yes, the plugin provides statistics in the admin dashboard (Settings > WebP Images) showing the number of converted images and their total size.

= How can I regenerate all WebP images? =

You can regenerate all WebP images by clicking the "Regenerate All WebP Images" button in the plugin's settings page (Settings > WebP Images).

= Will this plugin work with my theme? =

The plugin is designed to work with any WordPress theme. It processes images in the content, featured images, and CSS background images.

= Does this plugin modify my original images? =

No, your original images remain untouched. The plugin creates WebP copies in a separate directory.


== Changelog ==

= 1.0.5 =
* Fixed version number discrepancy
* Enhanced mobile device handling
* Fixed path handling for wp-images folder

= 1.0.4 =
* Added support for lazy-loaded images
* Improved handling of CSS background images

= 1.0.3 =
* Added mobile-specific image resizing
* Improved error handling and logging
* Fixed compatibility issues with some themes

= 1.0.2 =
* Added statistics to admin dashboard
* Improved WebP directory structure

= 1.0.1 =
* Added support for srcset attributes
* Fixed compatibility with caching plugins
* Improved error handling

= 1.0.0 =
* Initial release

== Technical Details ==

The plugin uses the GD library to convert images to WebP format.
It supports JPEG, PNG, and GIF formats, including transparency in PNG and GIF images.

For optimal performance, the plugin:

1. Creates WebP images only when requested
2. Stores converted images in a structured directory
3. Preserves the original directory structure
4. Handles various image inclusion methods (img tags, srcset, CSS backgrounds, etc.)
5. Provides browser detection to ensure compatibility

The plugin is designed to be lightweight and efficient, with minimal impact on server resources.
