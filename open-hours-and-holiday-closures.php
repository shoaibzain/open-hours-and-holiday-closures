<?php
/**
 * Plugin Name:       Open Hours and Holiday Closures
 * Plugin URI:        https://wordpress.org/plugins/open-hours-and-holiday-closures/
 * Description:       Show live open or closed status, weekly business hours, holiday overrides, and temporary closure notices.
 * Version:           0.1.0
 * Author:            Zainaster
 * Author URI:        https://profiles.wordpress.org/shoaibzain/
 * Text Domain:       open-hours-and-holiday-closures
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package OpenHoursHolidayClosures
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OHHC_VERSION', '0.1.0');
define('OHHC_FILE', __FILE__);
define('OHHC_BASENAME', plugin_basename(__FILE__));
define('OHHC_PATH', plugin_dir_path(__FILE__));
define('OHHC_URL', plugin_dir_url(__FILE__));

spl_autoload_register(static function ($class) {
    $prefix = 'OpenHoursHolidayClosures\\';
    $base_dir = OHHC_PATH . 'includes/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $relative_path = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
    $file = $base_dir . $relative_path;

    if (file_exists($file)) {
        require $file;
    }
});

function open_hours_render(array $args = []): string
{
    return OpenHoursHolidayClosures\Plugin::instance()->render($args);
}

OpenHoursHolidayClosures\Plugin::instance()->register();
