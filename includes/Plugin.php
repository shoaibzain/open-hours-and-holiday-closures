<?php
/**
 * Core plugin bootstrap.
 *
 * @package OpenHoursHolidayClosures
 */

namespace OpenHoursHolidayClosures;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    public const OPTION_NAME = 'ohhc_settings';
    public const SHORTCODE_TAG = 'ohhc_open_hours';

    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    public function register(): void
    {
        register_activation_hook(OHHC_FILE, [$this, 'on_activation']);

        add_action('init', [$this, 'register_assets']);
        add_action('init', [$this, 'register_shortcode']);
        add_action('init', [$this, 'register_block']);
        add_action('plugins_loaded', [$this, 'maybe_register_elementor']);
        add_action('admin_init', [Settings::class, 'register']);
        add_action('admin_menu', [Settings::class, 'register_menu']);
    }

    public function on_activation(): void
    {
        $settings = get_option(self::OPTION_NAME, []);

        if (!is_array($settings) || $settings === []) {
            update_option(self::OPTION_NAME, Renderer::get_default_settings());
            return;
        }

        update_option(self::OPTION_NAME, wp_parse_args($settings, Renderer::get_default_settings()));
    }

    public function register_assets(): void
    {
        wp_register_style(
            'ohhc-frontend',
            OHHC_URL . 'assets/css/frontend.css',
            [],
            (string) filemtime(OHHC_PATH . 'assets/css/frontend.css')
        );

        wp_register_script(
            'ohhc-block-editor',
            OHHC_URL . 'assets/js/block-editor.js',
            ['wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-server-side-render'],
            (string) filemtime(OHHC_PATH . 'assets/js/block-editor.js'),
            true
        );
    }

    public function enqueue_frontend_assets(): void
    {
        wp_enqueue_style('ohhc-frontend');
    }

    public function register_shortcode(): void
    {
        add_shortcode(self::SHORTCODE_TAG, function ($atts): string {
            $atts = shortcode_atts(
                [
                    'view'                => 'badge',
                    'show_next'           => 'yes',
                    'show_status'         => 'yes',
                    'show_closure_notice' => 'yes',
                    'class'               => '',
                    'accent_color'        => '',
                    'border_radius'       => '',
                ],
                (array) $atts,
                self::SHORTCODE_TAG
            );

            return $this->render(
                [
                    'view'                => $atts['view'],
                    'show_next'           => $this->is_truthy($atts['show_next']),
                    'show_status'         => $this->is_truthy($atts['show_status']),
                    'show_closure_notice' => $this->is_truthy($atts['show_closure_notice']),
                    'class'               => (string) $atts['class'],
                    'accent_color'        => (string) $atts['accent_color'],
                    'border_radius'       => $atts['border_radius'],
                ]
            );
        });
    }

    public function register_block(): void
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        register_block_type(
            'open-hours/status',
            [
                'api_version'     => 2,
                'editor_script'   => 'ohhc-block-editor',
                'attributes'      => [
                    'view' => [
                        'type'    => 'string',
                        'default' => 'badge',
                    ],
                    'showNext' => [
                        'type'    => 'boolean',
                        'default' => true,
                    ],
                    'showStatus' => [
                        'type'    => 'boolean',
                        'default' => true,
                    ],
                    'showClosureNotice' => [
                        'type'    => 'boolean',
                        'default' => true,
                    ],
                    'compact' => [
                        'type'    => 'boolean',
                        'default' => false,
                    ],
                    'accentColor' => [
                        'type'    => 'string',
                        'default' => '',
                    ],
                    'borderRadius' => [
                        'type'    => 'number',
                        'default' => 14,
                    ],
                ],
                'render_callback' => [$this, 'render_block'],
            ]
        );
    }

    public function render_block(array $attributes = []): string
    {
        return $this->render(
            [
                'view'                => $attributes['view'] ?? 'badge',
                'show_next'           => !empty($attributes['showNext']),
                'show_status'         => !array_key_exists('showStatus', $attributes) || !empty($attributes['showStatus']),
                'show_closure_notice' => !array_key_exists('showClosureNotice', $attributes) || !empty($attributes['showClosureNotice']),
                'compact'             => !empty($attributes['compact']),
                'accent_color'        => isset($attributes['accentColor']) ? (string) $attributes['accentColor'] : '',
                'border_radius'       => $attributes['borderRadius'] ?? '',
                'class'               => isset($attributes['className']) ? (string) $attributes['className'] : '',
            ]
        );
    }

    public function render(array $args = []): string
    {
        $this->enqueue_frontend_assets();

        return Renderer::render($args);
    }

    public function maybe_register_elementor(): void
    {
        if (did_action('elementor/loaded')) {
            $this->boot_elementor_bridge();
            return;
        }

        add_action('elementor/loaded', [$this, 'boot_elementor_bridge']);
    }

    public function boot_elementor_bridge(): void
    {
        add_action('elementor/elements/categories_registered', [$this, 'register_elementor_category']);
        add_action('elementor/widgets/register', [$this, 'register_elementor_widget']);
    }

    public function register_elementor_category($elements_manager): void
    {
        if (!method_exists($elements_manager, 'add_category')) {
            return;
        }

        $elements_manager->add_category(
            'open-hours',
            [
                'title' => esc_html__('Open Hours', 'open-hours-and-holiday-closures'),
                'icon'  => 'fa fa-clock',
            ]
        );
    }

    public function register_elementor_widget($widgets_manager): void
    {
        if (!class_exists('\Elementor\Widget_Base')) {
            return;
        }

        $widget = new Elementor\Business_Hours_Widget();

        if (method_exists($widgets_manager, 'register')) {
            $widgets_manager->register($widget);
            return;
        }

        if (method_exists($widgets_manager, 'register_widget_type')) {
            $widgets_manager->register_widget_type($widget);
        }
    }

    private function is_truthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
