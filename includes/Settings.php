<?php
/**
 * Admin settings page.
 *
 * @package OpenHoursHolidayClosures
 */

namespace OpenHoursHolidayClosures;

if (!defined('ABSPATH')) {
    exit;
}

final class Settings
{
    private const SETTINGS_GROUP = 'ohhc_settings_group';

    public static function register(): void
    {
        register_setting(
            self::SETTINGS_GROUP,
            Plugin::OPTION_NAME,
            [
                'type'              => 'array',
                'sanitize_callback' => [self::class, 'sanitize_settings'],
                'default'           => Renderer::get_default_settings(),
            ]
        );
    }

    public static function register_menu(): void
    {
        add_options_page(
            __('Open Hours', 'open-hours-and-holiday-closures'),
            __('Open Hours', 'open-hours-and-holiday-closures'),
            'manage_options',
            'open-hours-and-holiday-closures',
            [self::class, 'render_page']
        );
    }

    /**
     * @param mixed $input
     * @return array<string, mixed>
     */
    public static function sanitize_settings($input): array
    {
        $defaults = Renderer::get_default_settings();

        if (!is_array($input)) {
            return $defaults;
        }

        $sanitized = $defaults;

        $sanitized['timezone'] = self::sanitize_timezone($input['timezone'] ?? $defaults['timezone']);
        $sanitized['style_preset'] = in_array($input['style_preset'] ?? '', ['neutral', 'soft', 'contrast'], true)
            ? $input['style_preset']
            : $defaults['style_preset'];

        $sanitized['schedule'] = [];
        foreach (Renderer::get_day_definitions() as $day => $label) {
            $day_input = isset($input['schedule'][$day]) && is_array($input['schedule'][$day]) ? $input['schedule'][$day] : [];
            $sanitized['schedule'][$day] = [
                'closed' => !empty($day_input['closed']) ? 1 : 0,
                'ranges' => self::sanitize_ranges($day_input['ranges'] ?? [], $label),
            ];
        }

        $sanitized['overrides'] = [];
        $override_rows = isset($input['overrides']) && is_array($input['overrides']) ? $input['overrides'] : [];
        foreach ($override_rows as $index => $override) {
            if (!is_array($override)) {
                continue;
            }

            $date = sanitize_text_field((string) ($override['date'] ?? ''));
            $label = sanitize_text_field((string) ($override['label'] ?? ''));
            $closed = !empty($override['closed']) ? 1 : 0;
            $ranges = self::sanitize_override_ranges($override, $index + 1);

            if ($date === '') {
                continue;
            }

            if (!self::is_valid_date($date)) {
                add_settings_error(
                    Plugin::OPTION_NAME,
                    'ohhc_invalid_override_date_' . $index,
                    sprintf(
                        __('Ignored override row %d because the date was invalid.', 'open-hours-and-holiday-closures'),
                        $index + 1
                    ),
                    'error'
                );
                continue;
            }

            if (!$closed && $ranges === []) {
                add_settings_error(
                    Plugin::OPTION_NAME,
                    'ohhc_empty_override_' . $index,
                    sprintf(
                        __('Ignored override row %d because it had no valid time range.', 'open-hours-and-holiday-closures'),
                        $index + 1
                    ),
                    'error'
                );
                continue;
            }

            $sanitized['overrides'][] = [
                'date'   => $date,
                'label'  => $label,
                'closed' => $closed,
                'ranges' => $closed ? [] : $ranges,
            ];
        }

        $sanitized['temporary_closure_enabled'] = !empty($input['temporary_closure_enabled']) ? 1 : 0;
        $sanitized['temporary_closure_message'] = sanitize_text_field((string) ($input['temporary_closure_message'] ?? $defaults['temporary_closure_message']));
        $sanitized['reopen_date_text'] = sanitize_text_field((string) ($input['reopen_date_text'] ?? ''));

        $labels = isset($input['labels']) && is_array($input['labels']) ? $input['labels'] : [];
        $sanitized['labels'] = [
            'open'       => sanitize_text_field((string) ($labels['open'] ?? $defaults['labels']['open'])),
            'closed'     => sanitize_text_field((string) ($labels['closed'] ?? $defaults['labels']['closed'])),
            'opens_next' => sanitize_text_field((string) ($labels['opens_next'] ?? $defaults['labels']['opens_next'])),
            'closed_day' => sanitize_text_field((string) ($labels['closed_day'] ?? $defaults['labels']['closed_day'])),
        ];

        return $sanitized;
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = Renderer::get_settings();
        $timezones = timezone_identifiers_list();
        $override_rows = max(5, count($settings['overrides']) + 2);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Open Hours', 'open-hours-and-holiday-closures'); ?></h1>
            <p><?php esc_html_e('Configure your weekly hours, date-specific overrides, and temporary closure messaging.', 'open-hours-and-holiday-closures'); ?></p>

            <?php settings_errors(Plugin::OPTION_NAME); ?>

            <form action="options.php" method="post">
                <?php settings_fields(self::SETTINGS_GROUP); ?>

                <h2><?php esc_html_e('General', 'open-hours-and-holiday-closures'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ohhc-timezone"><?php esc_html_e('Business Timezone', 'open-hours-and-holiday-closures'); ?></label></th>
                        <td>
                            <select id="ohhc-timezone" name="<?php echo esc_attr(Plugin::OPTION_NAME); ?>[timezone]">
                                <?php foreach ($timezones as $timezone) : ?>
                                    <option value="<?php echo esc_attr($timezone); ?>" <?php selected($settings['timezone'], $timezone); ?>>
                                        <?php echo esc_html($timezone); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ohhc-style-preset"><?php esc_html_e('Style Preset', 'open-hours-and-holiday-closures'); ?></label></th>
                        <td>
                            <select id="ohhc-style-preset" name="<?php echo esc_attr(Plugin::OPTION_NAME); ?>[style_preset]">
                                <option value="neutral" <?php selected($settings['style_preset'], 'neutral'); ?>><?php esc_html_e('Neutral', 'open-hours-and-holiday-closures'); ?></option>
                                <option value="soft" <?php selected($settings['style_preset'], 'soft'); ?>><?php esc_html_e('Soft', 'open-hours-and-holiday-closures'); ?></option>
                                <option value="contrast" <?php selected($settings['style_preset'], 'contrast'); ?>><?php esc_html_e('Contrast', 'open-hours-and-holiday-closures'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Weekly Schedule', 'open-hours-and-holiday-closures'); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Day', 'open-hours-and-holiday-closures'); ?></th>
                            <th><?php esc_html_e('Closed', 'open-hours-and-holiday-closures'); ?></th>
                            <th><?php esc_html_e('Range 1', 'open-hours-and-holiday-closures'); ?></th>
                            <th><?php esc_html_e('Range 2', 'open-hours-and-holiday-closures'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (Renderer::get_day_definitions() as $day => $label) : ?>
                            <?php $day_settings = $settings['schedule'][$day]; ?>
                            <tr>
                                <th scope="row"><?php echo esc_html($label); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo esc_attr(Plugin::OPTION_NAME); ?>[schedule][<?php echo esc_attr($day); ?>][closed]" value="1" <?php checked(!empty($day_settings['closed'])); ?>>
                                        <?php esc_html_e('Closed all day', 'open-hours-and-holiday-closures'); ?>
                                    </label>
                                </td>
                                <td>
                                    <?php self::render_time_pair(Plugin::OPTION_NAME . '[schedule][' . $day . '][ranges][0]', $day_settings['ranges'][0] ?? ['start' => '', 'end' => '']); ?>
                                </td>
                                <td>
                                    <?php self::render_time_pair(Plugin::OPTION_NAME . '[schedule][' . $day . '][ranges][1]', $day_settings['ranges'][1] ?? ['start' => '', 'end' => '']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Special Date Overrides', 'open-hours-and-holiday-closures'); ?></h2>
                <p><?php esc_html_e('Use exact dates for holidays, custom hours, or one-off closures.', 'open-hours-and-holiday-closures'); ?></p>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date', 'open-hours-and-holiday-closures'); ?></th>
                            <th><?php esc_html_e('Label', 'open-hours-and-holiday-closures'); ?></th>
                            <th><?php esc_html_e('Closed', 'open-hours-and-holiday-closures'); ?></th>
                            <th><?php esc_html_e('Range 1', 'open-hours-and-holiday-closures'); ?></th>
                            <th><?php esc_html_e('Range 2', 'open-hours-and-holiday-closures'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($index = 0; $index < $override_rows; $index++) : ?>
                            <?php
                            $override = $settings['overrides'][$index] ?? [
                                'date'   => '',
                                'label'  => '',
                                'closed' => 0,
                                'ranges' => [],
                            ];
                            ?>
                            <tr>
                                <td>
                                    <input type="date" name="<?php echo esc_attr(Plugin::OPTION_NAME); ?>[overrides][<?php echo esc_attr((string) $index); ?>][date]" value="<?php echo esc_attr($override['date']); ?>">
                                </td>
                                <td>
                                    <input type="text" class="regular-text" name="<?php echo esc_attr(Plugin::OPTION_NAME); ?>[overrides][<?php echo esc_attr((string) $index); ?>][label]" value="<?php echo esc_attr($override['label']); ?>" placeholder="<?php esc_attr_e('Holiday or note', 'open-hours-and-holiday-closures'); ?>">
                                </td>
                                <td>
                                    <input type="checkbox" name="<?php echo esc_attr(Plugin::OPTION_NAME); ?>[overrides][<?php echo esc_attr((string) $index); ?>][closed]" value="1" <?php checked(!empty($override['closed'])); ?>>
                                </td>
                                <td>
                                    <?php self::render_override_time_pair(Plugin::OPTION_NAME . '[overrides][' . $index . ']', $override['ranges'][0] ?? ['start' => '', 'end' => ''], 1); ?>
                                </td>
                                <td>
                                    <?php self::render_override_time_pair(Plugin::OPTION_NAME . '[overrides][' . $index . ']', $override['ranges'][1] ?? ['start' => '', 'end' => ''], 2); ?>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Temporary Closure', 'open-hours-and-holiday-closures'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Temporary Closure', 'open-hours-and-holiday-closures'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(Plugin::OPTION_NAME); ?>[temporary_closure_enabled]" value="1" <?php checked(!empty($settings['temporary_closure_enabled'])); ?>>
                                <?php esc_html_e('Override the schedule and show a closure notice', 'open-hours-and-holiday-closures'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ohhc-closure-message"><?php esc_html_e('Closure Message', 'open-hours-and-holiday-closures'); ?></label></th>
                        <td>
                            <input id="ohhc-closure-message" type="text" class="regular-text" name="<?php echo esc_attr(Plugin::OPTION_NAME); ?>[temporary_closure_message]" value="<?php echo esc_attr($settings['temporary_closure_message']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ohhc-reopen-date"><?php esc_html_e('Reopen Date Text', 'open-hours-and-holiday-closures'); ?></label></th>
                        <td>
                            <input id="ohhc-reopen-date" type="text" class="regular-text" name="<?php echo esc_attr(Plugin::OPTION_NAME); ?>[reopen_date_text]" value="<?php echo esc_attr($settings['reopen_date_text']); ?>" placeholder="<?php esc_attr_e('Monday, March 30 at 9:00 AM', 'open-hours-and-holiday-closures'); ?>">
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Status Labels', 'open-hours-and-holiday-closures'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ohhc-label-open"><?php esc_html_e('Open Label', 'open-hours-and-holiday-closures'); ?></label></th>
                        <td><input id="ohhc-label-open" type="text" class="regular-text" name="<?php echo esc_attr(Plugin::OPTION_NAME); ?>[labels][open]" value="<?php echo esc_attr($settings['labels']['open']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ohhc-label-closed"><?php esc_html_e('Closed Label', 'open-hours-and-holiday-closures'); ?></label></th>
                        <td><input id="ohhc-label-closed" type="text" class="regular-text" name="<?php echo esc_attr(Plugin::OPTION_NAME); ?>[labels][closed]" value="<?php echo esc_attr($settings['labels']['closed']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ohhc-label-next"><?php esc_html_e('Next Opening Label', 'open-hours-and-holiday-closures'); ?></label></th>
                        <td><input id="ohhc-label-next" type="text" class="regular-text" name="<?php echo esc_attr(Plugin::OPTION_NAME); ?>[labels][opens_next]" value="<?php echo esc_attr($settings['labels']['opens_next']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ohhc-label-closed-day"><?php esc_html_e('Closed Day Label', 'open-hours-and-holiday-closures'); ?></label></th>
                        <td><input id="ohhc-label-closed-day" type="text" class="regular-text" name="<?php echo esc_attr(Plugin::OPTION_NAME); ?>[labels][closed_day]" value="<?php echo esc_attr($settings['labels']['closed_day']); ?>"></td>
                    </tr>
                </table>

                <?php submit_button(__('Save Open Hours', 'open-hours-and-holiday-closures')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * @param array<int, mixed> $ranges
     * @return array<int, array{start: string, end: string}>
     */
    private static function sanitize_ranges(array $ranges, string $label): array
    {
        $sanitized = [];

        for ($index = 0; $index < 2; $index++) {
            $range = isset($ranges[$index]) && is_array($ranges[$index]) ? $ranges[$index] : [];
            $start = self::sanitize_time($range['start'] ?? '');
            $end = self::sanitize_time($range['end'] ?? '');

            if ($start === '' && $end === '') {
                continue;
            }

            if ($start === '' || $end === '') {
                add_settings_error(
                    Plugin::OPTION_NAME,
                    'ohhc_invalid_range_' . sanitize_key($label . '_' . $index),
                    sprintf(
                        __('Ignored incomplete time range %2$d for %1$s.', 'open-hours-and-holiday-closures'),
                        $label,
                        $index + 1
                    ),
                    'error'
                );
                continue;
            }

            $sanitized[] = [
                'start' => $start,
                'end'   => $end,
            ];
        }

        return $sanitized;
    }

    /**
     * @param array<string, mixed> $override
     * @return array<int, array{start: string, end: string}>
     */
    private static function sanitize_override_ranges(array $override, int $row_number): array
    {
        $pairs = [
            [
                'start' => self::sanitize_time($override['start_1'] ?? ''),
                'end'   => self::sanitize_time($override['end_1'] ?? ''),
            ],
            [
                'start' => self::sanitize_time($override['start_2'] ?? ''),
                'end'   => self::sanitize_time($override['end_2'] ?? ''),
            ],
        ];

        $sanitized = [];
        foreach ($pairs as $index => $pair) {
            if ($pair['start'] === '' && $pair['end'] === '') {
                continue;
            }

            if ($pair['start'] === '' || $pair['end'] === '') {
                add_settings_error(
                    Plugin::OPTION_NAME,
                    'ohhc_invalid_override_range_' . $row_number . '_' . $index,
                    sprintf(
                        __('Ignored incomplete time range %2$d in override row %1$d.', 'open-hours-and-holiday-closures'),
                        $row_number,
                        $index + 1
                    ),
                    'error'
                );
                continue;
            }

            $sanitized[] = $pair;
        }

        return $sanitized;
    }

    /**
     * @param array<string, string> $range
     */
    private static function render_time_pair(string $base_name, array $range): void
    {
        ?>
        <label>
            <span class="screen-reader-text"><?php esc_html_e('Start time', 'open-hours-and-holiday-closures'); ?></span>
            <input type="time" name="<?php echo esc_attr($base_name); ?>[start]" value="<?php echo esc_attr($range['start'] ?? ''); ?>">
        </label>
        <span> - </span>
        <label>
            <span class="screen-reader-text"><?php esc_html_e('End time', 'open-hours-and-holiday-closures'); ?></span>
            <input type="time" name="<?php echo esc_attr($base_name); ?>[end]" value="<?php echo esc_attr($range['end'] ?? ''); ?>">
        </label>
        <?php
    }

    /**
     * @param array<string, string> $range
     */
    private static function render_override_time_pair(string $base_name, array $range, int $number): void
    {
        ?>
        <label>
            <span class="screen-reader-text"><?php esc_html_e('Start time', 'open-hours-and-holiday-closures'); ?></span>
            <input type="time" name="<?php echo esc_attr($base_name); ?>[start_<?php echo esc_attr((string) $number); ?>]" value="<?php echo esc_attr($range['start'] ?? ''); ?>">
        </label>
        <span> - </span>
        <label>
            <span class="screen-reader-text"><?php esc_html_e('End time', 'open-hours-and-holiday-closures'); ?></span>
            <input type="time" name="<?php echo esc_attr($base_name); ?>[end_<?php echo esc_attr((string) $number); ?>]" value="<?php echo esc_attr($range['end'] ?? ''); ?>">
        </label>
        <?php
    }

    private static function sanitize_time($time): string
    {
        $time = trim((string) $time);

        return preg_match('/^\d{2}:\d{2}$/', $time) ? $time : '';
    }

    private static function sanitize_timezone($timezone): string
    {
        $timezone = sanitize_text_field((string) $timezone);

        return in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : (wp_timezone_string() ?: 'UTC');
    }

    private static function is_valid_date(string $date): bool
    {
        $parts = explode('-', $date);
        if (count($parts) !== 3) {
            return false;
        }

        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }
}
