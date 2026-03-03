<?php
/**
 * Uninstall cleanup.
 *
 * @package OpenHoursHolidayClosures
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('ohhc_settings');
