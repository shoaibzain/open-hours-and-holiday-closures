<?php
/**
 * Shared rendering and schedule logic.
 *
 * @package OpenHoursHolidayClosures
 */

namespace OpenHoursHolidayClosures;

use DateTimeImmutable;
use DateTimeZone;

if (!defined('ABSPATH')) {
    exit;
}

final class Renderer
{
    /**
     * @return array<string, string>
     */
    public static function get_day_definitions(): array
    {
        return [
            'monday'    => __('Monday', 'open-hours-and-holiday-closures'),
            'tuesday'   => __('Tuesday', 'open-hours-and-holiday-closures'),
            'wednesday' => __('Wednesday', 'open-hours-and-holiday-closures'),
            'thursday'  => __('Thursday', 'open-hours-and-holiday-closures'),
            'friday'    => __('Friday', 'open-hours-and-holiday-closures'),
            'saturday'  => __('Saturday', 'open-hours-and-holiday-closures'),
            'sunday'    => __('Sunday', 'open-hours-and-holiday-closures'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get_default_settings(): array
    {
        $schedule = [];

        foreach (array_keys(self::get_day_definitions()) as $day) {
            $schedule[$day] = [
                'closed' => in_array($day, ['saturday', 'sunday'], true) ? 1 : 0,
                'ranges' => [
                    ['start' => '09:00', 'end' => '17:00'],
                    ['start' => '', 'end' => ''],
                ],
            ];
        }

        return [
            'timezone'                  => self::get_default_timezone_string(),
            'schedule'                  => $schedule,
            'overrides'                 => [],
            'temporary_closure_enabled' => 0,
            'temporary_closure_message' => __('Temporarily closed.', 'open-hours-and-holiday-closures'),
            'reopen_date_text'          => '',
            'labels'                    => [
                'open'       => __('Open now', 'open-hours-and-holiday-closures'),
                'closed'     => __('Closed now', 'open-hours-and-holiday-closures'),
                'opens_next' => __('Opens %s', 'open-hours-and-holiday-closures'),
                'closed_day' => __('Closed today', 'open-hours-and-holiday-closures'),
            ],
            'style_preset'              => 'neutral',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get_settings(): array
    {
        $saved = get_option(Plugin::OPTION_NAME, []);

        if (!is_array($saved)) {
            $saved = [];
        }

        return self::merge_settings($saved);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public static function merge_settings(array $settings): array
    {
        $defaults = self::get_default_settings();

        $settings = wp_parse_args($settings, $defaults);
        $settings['timezone'] = self::sanitize_timezone($settings['timezone'] ?? $defaults['timezone']);
        $settings['style_preset'] = in_array($settings['style_preset'] ?? '', ['neutral', 'soft', 'contrast'], true)
            ? $settings['style_preset']
            : $defaults['style_preset'];

        $merged_schedule = [];
        foreach (self::get_day_definitions() as $day => $label) {
            $source = isset($settings['schedule'][$day]) && is_array($settings['schedule'][$day]) ? $settings['schedule'][$day] : [];
            $merged_schedule[$day] = [
                'closed' => !empty($source['closed']) ? 1 : 0,
                'ranges' => self::normalize_ranges(isset($source['ranges']) && is_array($source['ranges']) ? $source['ranges'] : []),
            ];
        }
        $settings['schedule'] = $merged_schedule;

        $merged_overrides = [];
        if (!empty($settings['overrides']) && is_array($settings['overrides'])) {
            foreach ($settings['overrides'] as $override) {
                if (!is_array($override) || empty($override['date'])) {
                    continue;
                }

                $date = sanitize_text_field((string) $override['date']);
                if (!self::is_valid_date($date)) {
                    continue;
                }

                $merged_overrides[] = [
                    'date'   => $date,
                    'label'  => sanitize_text_field((string) ($override['label'] ?? '')),
                    'closed' => !empty($override['closed']) ? 1 : 0,
                    'ranges' => self::normalize_ranges(isset($override['ranges']) && is_array($override['ranges']) ? $override['ranges'] : []),
                ];
            }
        }
        $settings['overrides'] = $merged_overrides;

        $settings['temporary_closure_enabled'] = !empty($settings['temporary_closure_enabled']) ? 1 : 0;
        $settings['temporary_closure_message'] = sanitize_text_field((string) $settings['temporary_closure_message']);
        $settings['reopen_date_text'] = sanitize_text_field((string) $settings['reopen_date_text']);

        $labels = isset($settings['labels']) && is_array($settings['labels']) ? $settings['labels'] : [];
        $settings['labels'] = [
            'open'       => sanitize_text_field((string) ($labels['open'] ?? $defaults['labels']['open'])),
            'closed'     => sanitize_text_field((string) ($labels['closed'] ?? $defaults['labels']['closed'])),
            'opens_next' => sanitize_text_field((string) ($labels['opens_next'] ?? $defaults['labels']['opens_next'])),
            'closed_day' => sanitize_text_field((string) ($labels['closed_day'] ?? $defaults['labels']['closed_day'])),
        ];

        return $settings;
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function render(array $args = []): string
    {
        $settings = self::get_settings();
        $args = wp_parse_args(
            $args,
            [
                'view'                => 'badge',
                'show_next'           => true,
                'show_status'         => true,
                'show_closure_notice' => true,
                'class'               => '',
                'accent_color'        => '',
                'border_radius'       => '',
                'compact'             => false,
            ]
        );

        $view = in_array($args['view'], ['full', 'today', 'badge'], true) ? $args['view'] : 'badge';
        $state = self::get_current_state($settings);

        $classes = array_filter(
            [
                'ohhc-open-hours',
                'ohhc-preset-' . $settings['style_preset'],
                !empty($args['compact']) ? 'is-compact' : '',
                ...self::sanitize_class_list((string) $args['class']),
            ]
        );

        $styles = [];
        $accent_color = sanitize_hex_color((string) $args['accent_color']);
        if ($accent_color) {
            $styles[] = '--ohhc-accent:' . $accent_color;
        }

        $border_radius = is_numeric($args['border_radius']) ? max(0, (int) $args['border_radius']) : '';
        if ($border_radius !== '') {
            $styles[] = '--ohhc-radius:' . $border_radius . 'px';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>"<?php echo $styles ? ' style="' . esc_attr(implode(';', $styles)) . '"' : ''; ?>>
            <?php if ('badge' === $view) : ?>
                <?php echo self::render_badge_view($state, $args); ?>
            <?php elseif ('today' === $view) : ?>
                <?php echo self::render_today_view($state, $args); ?>
            <?php else : ?>
                <?php echo self::render_full_view($state, $settings, $args); ?>
            <?php endif; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public static function get_current_state(array $settings): array
    {
        $timezone = new DateTimeZone(self::sanitize_timezone($settings['timezone']));
        $now = new DateTimeImmutable('now', $timezone);
        $today = $now->format('Y-m-d');
        $yesterday = $now->modify('-1 day')->format('Y-m-d');
        $today_entry = self::resolve_entry_for_date($today, $settings);
        $yesterday_entry = self::resolve_entry_for_date($yesterday, $settings);
        $is_open = false;

        foreach ($today_entry['ranges'] as $range) {
            if (self::datetime_in_range($now, $today, $range, $timezone)) {
                $is_open = true;
                break;
            }
        }

        if (!$is_open) {
            foreach ($yesterday_entry['ranges'] as $range) {
                if (self::datetime_in_range($now, $yesterday, $range, $timezone)) {
                    $is_open = true;
                    break;
                }
            }
        }

        $next_open = null;
        if (!$is_open && empty($settings['temporary_closure_enabled'])) {
            $next_open = self::find_next_opening($now, $settings, $timezone);
        }

        $status_label = !empty($settings['temporary_closure_enabled'])
            ? $settings['temporary_closure_message']
            : ($is_open ? $settings['labels']['open'] : $settings['labels']['closed']);

        $closure_notice = '';
        if (!empty($settings['temporary_closure_enabled'])) {
            $closure_notice = $settings['temporary_closure_message'];
            if (!empty($settings['reopen_date_text'])) {
                $closure_notice .= ' ' . sprintf(
                    __('Expected to reopen: %s', 'open-hours-and-holiday-closures'),
                    $settings['reopen_date_text']
                );
            }
        }

        $today_hours = self::format_entry_ranges($today_entry, $settings, $timezone);
        if ($today_hours === '') {
            $today_hours = $settings['labels']['closed_day'];
        }

        $next_text = '';
        if ($next_open instanceof DateTimeImmutable) {
            $next_text = sprintf(
                $settings['labels']['opens_next'],
                self::format_next_opening_text($next_open, $now)
            );
        }

        return [
            'timezone'       => $timezone,
            'now'            => $now,
            'today_key'      => strtolower($now->format('l')),
            'is_open'        => $is_open && empty($settings['temporary_closure_enabled']),
            'status_label'   => $status_label,
            'next_text'      => $next_text,
            'today_hours'    => $today_hours,
            'today_entry'    => $today_entry,
            'closure_notice' => $closure_notice,
            'override_label' => $today_entry['source'] === 'override' && !empty($today_entry['label']) ? $today_entry['label'] : '',
        ];
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $args
     */
    private static function render_badge_view(array $state, array $args): string
    {
        ob_start();
        ?>
        <div class="ohhc-open-hours__badge <?php echo esc_attr($state['is_open'] ? 'is-open' : 'is-closed'); ?>">
            <?php if (!empty($args['show_status'])) : ?>
                <span class="ohhc-open-hours__badge-text"><?php echo esc_html($state['status_label']); ?></span>
            <?php endif; ?>
            <?php if (!empty($args['show_next']) && !empty($state['next_text'])) : ?>
                <span class="ohhc-open-hours__next"><?php echo esc_html($state['next_text']); ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($args['show_closure_notice']) && !empty($state['closure_notice'])) : ?>
            <div class="ohhc-open-hours__notice"><?php echo esc_html($state['closure_notice']); ?></div>
        <?php endif; ?>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $args
     */
    private static function render_today_view(array $state, array $args): string
    {
        ob_start();
        ?>
        <div class="ohhc-open-hours__header">
            <?php if (!empty($args['show_status'])) : ?>
                <div class="ohhc-open-hours__status <?php echo esc_attr($state['is_open'] ? 'is-open' : 'is-closed'); ?>">
                    <span class="ohhc-open-hours__dot" aria-hidden="true"></span>
                    <span><?php echo esc_html($state['status_label']); ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($args['show_next']) && !empty($state['next_text'])) : ?>
                <div class="ohhc-open-hours__next"><?php echo esc_html($state['next_text']); ?></div>
            <?php endif; ?>
        </div>
        <div class="ohhc-open-hours__today">
            <div class="ohhc-open-hours__today-label"><?php esc_html_e("Today's Hours", 'open-hours-and-holiday-closures'); ?></div>
            <div class="ohhc-open-hours__today-hours"><?php echo esc_html($state['today_hours']); ?></div>
            <?php if (!empty($state['override_label'])) : ?>
                <div class="ohhc-open-hours__override-label"><?php echo esc_html($state['override_label']); ?></div>
            <?php endif; ?>
        </div>
        <?php if (!empty($args['show_closure_notice']) && !empty($state['closure_notice'])) : ?>
            <div class="ohhc-open-hours__notice"><?php echo esc_html($state['closure_notice']); ?></div>
        <?php endif; ?>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $args
     */
    private static function render_full_view(array $state, array $settings, array $args): string
    {
        ob_start();
        ?>
        <div class="ohhc-open-hours__header">
            <?php if (!empty($args['show_status'])) : ?>
                <div class="ohhc-open-hours__status <?php echo esc_attr($state['is_open'] ? 'is-open' : 'is-closed'); ?>">
                    <span class="ohhc-open-hours__dot" aria-hidden="true"></span>
                    <span><?php echo esc_html($state['status_label']); ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($args['show_next']) && !empty($state['next_text'])) : ?>
                <div class="ohhc-open-hours__next"><?php echo esc_html($state['next_text']); ?></div>
            <?php endif; ?>
        </div>
        <ul class="ohhc-open-hours__schedule">
            <?php foreach (self::get_day_definitions() as $day => $label) : ?>
                <?php
                $entry = $day === $state['today_key']
                    ? $state['today_entry']
                    : [
                        'ranges' => $settings['schedule'][$day]['ranges'],
                        'closed' => !empty($settings['schedule'][$day]['closed']),
                        'label'  => '',
                        'source' => 'schedule',
                    ];
                ?>
                <li class="ohhc-open-hours__row <?php echo esc_attr($day === $state['today_key'] ? 'is-today' : ''); ?>">
                    <span class="ohhc-open-hours__day"><?php echo esc_html($label); ?></span>
                    <span class="ohhc-open-hours__hours <?php echo esc_attr(empty($entry['ranges']) ? 'is-closed' : ''); ?>">
                        <?php echo esc_html(self::format_entry_ranges($entry, $settings, $state['timezone']) ?: $settings['labels']['closed_day']); ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if (!empty($state['override_label'])) : ?>
            <div class="ohhc-open-hours__footer">
                <div class="ohhc-open-hours__override-label"><?php echo esc_html($state['override_label']); ?></div>
            </div>
        <?php endif; ?>
        <?php if (!empty($args['show_closure_notice']) && !empty($state['closure_notice'])) : ?>
            <div class="ohhc-open-hours__notice"><?php echo esc_html($state['closure_notice']); ?></div>
        <?php endif; ?>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private static function resolve_entry_for_date(string $date, array $settings): array
    {
        if (!empty($settings['temporary_closure_enabled'])) {
            return [
                'ranges' => [],
                'closed' => true,
                'label'  => '',
                'source' => 'temporary_closure',
            ];
        }

        foreach ($settings['overrides'] as $override) {
            if (($override['date'] ?? '') !== $date) {
                continue;
            }

            return [
                'ranges' => !empty($override['closed']) ? [] : $override['ranges'],
                'closed' => !empty($override['closed']),
                'label'  => $override['label'],
                'source' => 'override',
            ];
        }

        $date_object = DateTimeImmutable::createFromFormat('Y-m-d', $date, new DateTimeZone('UTC'));
        $day_key = $date_object instanceof DateTimeImmutable ? strtolower($date_object->format('l')) : 'monday';
        $day_schedule = $settings['schedule'][$day_key] ?? ['closed' => 1, 'ranges' => []];

        return [
            'ranges' => !empty($day_schedule['closed']) ? [] : $day_schedule['ranges'],
            'closed' => !empty($day_schedule['closed']),
            'label'  => '',
            'source' => 'schedule',
        ];
    }

    /**
     * @param array<int, array{start: string, end: string}> $ranges
     * @return array<int, array{start: string, end: string}>
     */
    private static function normalize_ranges(array $ranges): array
    {
        $normalized = [];

        for ($index = 0; $index < 2; $index++) {
            $range = isset($ranges[$index]) && is_array($ranges[$index]) ? $ranges[$index] : [];
            $start = self::sanitize_time($range['start'] ?? '');
            $end = self::sanitize_time($range['end'] ?? '');

            if ($start === '' || $end === '') {
                continue;
            }

            $normalized[] = [
                'start' => $start,
                'end'   => $end,
            ];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $entry
     * @param array<string, mixed> $settings
     */
    private static function format_entry_ranges(array $entry, array $settings, DateTimeZone $timezone): string
    {
        if (!empty($entry['closed']) || empty($entry['ranges'])) {
            return '';
        }

        $segments = [];
        foreach ($entry['ranges'] as $range) {
            $segments[] = self::format_time_range($range['start'], $range['end'], $timezone);
        }

        return implode(' , ', $segments);
    }

    private static function format_time_range(string $start, string $end, DateTimeZone $timezone): string
    {
        $display_start = DateTimeImmutable::createFromFormat('Y-m-d H:i', '2000-01-01 ' . $start, $timezone);
        $display_end = DateTimeImmutable::createFromFormat('Y-m-d H:i', '2000-01-01 ' . $end, $timezone);

        if (!$display_start instanceof DateTimeImmutable || !$display_end instanceof DateTimeImmutable) {
            return $start . ' - ' . $end;
        }

        $time_format = get_option('time_format') ?: 'g:i a';
        $label = $display_start->format($time_format) . ' - ' . $display_end->format($time_format);

        if ($end <= $start) {
            $label .= ' ' . __('(next day)', 'open-hours-and-holiday-closures');
        }

        return $label;
    }

    /**
     * @param array{start: string, end: string} $range
     */
    private static function datetime_in_range(DateTimeImmutable $now, string $base_date, array $range, DateTimeZone $timezone): bool
    {
        $start = self::create_datetime($base_date, $range['start'], $timezone);
        $end = self::create_datetime($base_date, $range['end'], $timezone);

        if (!$start || !$end) {
            return false;
        }

        if ($range['end'] <= $range['start']) {
            $end = $end->modify('+1 day');
        }

        return $now >= $start && $now < $end;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function find_next_opening(DateTimeImmutable $now, array $settings, DateTimeZone $timezone): ?DateTimeImmutable
    {
        for ($offset = 0; $offset <= 7; $offset++) {
            $candidate_date = $now->modify('+' . $offset . ' day');
            $entry = self::resolve_entry_for_date($candidate_date->format('Y-m-d'), $settings);

            if (empty($entry['ranges'])) {
                continue;
            }

            foreach ($entry['ranges'] as $range) {
                $start = self::create_datetime($candidate_date->format('Y-m-d'), $range['start'], $timezone);
                if ($start instanceof DateTimeImmutable && $start > $now) {
                    return $start;
                }
            }
        }

        return null;
    }

    private static function format_next_opening_text(DateTimeImmutable $next_open, DateTimeImmutable $now): string
    {
        $time_format = get_option('time_format') ?: 'g:i a';

        if ($next_open->format('Y-m-d') === $now->format('Y-m-d')) {
            return sprintf(
                __('today at %s', 'open-hours-and-holiday-closures'),
                $next_open->format($time_format)
            );
        }

        if ($next_open->format('Y-m-d') === $now->modify('+1 day')->format('Y-m-d')) {
            return sprintf(
                __('tomorrow at %s', 'open-hours-and-holiday-closures'),
                $next_open->format($time_format)
            );
        }

        return sprintf(
            __('%1$s at %2$s', 'open-hours-and-holiday-closures'),
            $next_open->format('l'),
            $next_open->format($time_format)
        );
    }

    private static function create_datetime(string $date, string $time, DateTimeZone $timezone): ?DateTimeImmutable
    {
        $datetime = DateTimeImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $time, $timezone);

        return $datetime instanceof DateTimeImmutable ? $datetime : null;
    }

    private static function sanitize_time(string $time): string
    {
        $time = trim($time);

        return preg_match('/^\d{2}:\d{2}$/', $time) ? $time : '';
    }

    private static function sanitize_timezone($timezone): string
    {
        $timezone = sanitize_text_field((string) $timezone);

        return in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : self::get_default_timezone_string();
    }

    private static function get_default_timezone_string(): string
    {
        $timezone = wp_timezone_string();

        if ($timezone && in_array($timezone, timezone_identifiers_list(), true)) {
            return $timezone;
        }

        return 'UTC';
    }

    private static function is_valid_date(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }

    /**
     * @return array<int, string>
     */
    private static function sanitize_class_list(string $class_names): array
    {
        if ($class_names === '') {
            return [];
        }

        $classes = preg_split('/\s+/', trim($class_names));
        if (!is_array($classes)) {
            return [];
        }

        $sanitized = [];
        foreach ($classes as $class_name) {
            $class_name = sanitize_html_class($class_name);
            if ($class_name !== '') {
                $sanitized[] = $class_name;
            }
        }

        return array_values(array_unique($sanitized));
    }
}
