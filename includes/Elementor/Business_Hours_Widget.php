<?php
/**
 * Elementor widget bridge.
 *
 * @package OpenHoursHolidayClosures
 */

namespace OpenHoursHolidayClosures\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use OpenHoursHolidayClosures\Plugin;
use OpenHoursHolidayClosures\Renderer;

final class Business_Hours_Widget extends Widget_Base
{
    public function get_name(): string
    {
        return 'ohhc-business-hours-status';
    }

    public function get_title(): string
    {
        return esc_html__('Business Hours Status', 'open-hours-and-holiday-closures');
    }

    public function get_icon(): string
    {
        return 'eicon-clock';
    }

    public function get_categories(): array
    {
        return ['open-hours', 'general'];
    }

    public function get_keywords(): array
    {
        return ['business hours', 'open', 'closed', 'holiday', 'store'];
    }

    public function get_style_depends(): array
    {
        return ['ohhc-frontend'];
    }

    protected function register_controls(): void
    {
        $this->register_content_controls();
        $this->register_container_style_controls();
        $this->register_status_style_controls();
        $this->register_badge_style_controls();
        $this->register_schedule_style_controls();
        $this->register_notice_style_controls();
    }

    private function register_content_controls(): void
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Display', 'open-hours-and-holiday-closures'),
            ]
        );

        $this->add_control(
            'view',
            [
                'label'   => esc_html__('View', 'open-hours-and-holiday-closures'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'badge',
                'options' => [
                    'badge' => esc_html__('Badge', 'open-hours-and-holiday-closures'),
                    'today' => esc_html__('Today', 'open-hours-and-holiday-closures'),
                    'full'  => esc_html__('Full', 'open-hours-and-holiday-closures'),
                ],
            ]
        );

        $this->add_control(
            'show_status',
            [
                'label'        => esc_html__('Show Status', 'open-hours-and-holiday-closures'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'open-hours-and-holiday-closures'),
                'label_off'    => esc_html__('No', 'open-hours-and-holiday-closures'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_next',
            [
                'label'        => esc_html__('Show Next Opening', 'open-hours-and-holiday-closures'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'open-hours-and-holiday-closures'),
                'label_off'    => esc_html__('No', 'open-hours-and-holiday-closures'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_closure_notice',
            [
                'label'        => esc_html__('Show Closure Notice', 'open-hours-and-holiday-closures'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'open-hours-and-holiday-closures'),
                'label_off'    => esc_html__('No', 'open-hours-and-holiday-closures'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'compact',
            [
                'label'        => esc_html__('Compact Mode', 'open-hours-and-holiday-closures'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'open-hours-and-holiday-closures'),
                'label_off'    => esc_html__('No', 'open-hours-and-holiday-closures'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->end_controls_section();
    }

    private function register_container_style_controls(): void
    {
        $this->start_controls_section(
            'section_style_container',
            [
                'label' => esc_html__('Container', 'open-hours-and-holiday-closures'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'accent_color',
            [
                'label'     => esc_html__('Accent Color', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-accent: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'background_start_color',
            [
                'label'     => esc_html__('Background Start', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-surface: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'background_end_color',
            [
                'label'     => esc_html__('Background End', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-surface-soft: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label'     => esc_html__('Text Color', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-text: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'muted_text_color',
            [
                'label'     => esc_html__('Muted Text Color', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-muted: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'border_color',
            [
                'label'     => esc_html__('Border Color', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-border: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'divider_color',
            [
                'label'     => esc_html__('Divider Color', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-divider: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'border_radius',
            [
                'label'      => esc_html__('Border Radius', 'open-hours-and-holiday-closures'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'default'    => [
                    'size' => 14,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-radius: {{SIZE}}{{UNIT}}; border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label'      => esc_html__('Padding', 'open-hours-and-holiday-closures'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .ohhc-open-hours' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_min_width',
            [
                'label'      => esc_html__('Minimum Width', 'open-hours-and-holiday-closures'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 1200,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ohhc-open-hours' => 'min-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'container_shadow',
                'selector' => '{{WRAPPER}} .ohhc-open-hours',
            ]
        );

        $this->end_controls_section();
    }

    private function register_status_style_controls(): void
    {
        $this->start_controls_section(
            'section_style_status',
            [
                'label' => esc_html__('Status', 'open-hours-and-holiday-closures'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'status_text_color',
            [
                'label'     => esc_html__('Status Text Color', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours__status' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'open_status_color',
            [
                'label'     => esc_html__('Open Dot Color', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-open-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'closed_status_color',
            [
                'label'     => esc_html__('Closed Dot Color', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-closed-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'status_gap',
            [
                'label'      => esc_html__('Status Gap', 'open-hours-and-holiday-closures'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ohhc-open-hours__status' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'status_typography',
                'selector' => '{{WRAPPER}} .ohhc-open-hours__status',
            ]
        );

        $this->end_controls_section();
    }

    private function register_badge_style_controls(): void
    {
        $this->start_controls_section(
            'section_style_badge',
            [
                'label' => esc_html__('Badge', 'open-hours-and-holiday-closures'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'badge_neutral_background',
            [
                'label'     => esc_html__('Neutral Badge Background', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-badge-neutral-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'badge_open_background',
            [
                'label'     => esc_html__('Open Badge Background', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-badge-open-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'badge_open_text_color',
            [
                'label'     => esc_html__('Open Badge Text', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-badge-open-text: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'badge_closed_background',
            [
                'label'     => esc_html__('Closed Badge Background', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-badge-closed-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'badge_closed_text_color',
            [
                'label'     => esc_html__('Closed Badge Text', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-badge-closed-text: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'badge_padding',
            [
                'label'      => esc_html__('Badge Padding', 'open-hours-and-holiday-closures'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ohhc-open-hours__badge-text' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'badge_typography',
                'selector' => '{{WRAPPER}} .ohhc-open-hours__badge, {{WRAPPER}} .ohhc-open-hours__badge-text',
            ]
        );

        $this->end_controls_section();
    }

    private function register_schedule_style_controls(): void
    {
        $this->start_controls_section(
            'section_style_schedule',
            [
                'label' => esc_html__('Schedule', 'open-hours-and-holiday-closures'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'day_text_color',
            [
                'label'     => esc_html__('Day Label Color', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours__day' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'today_day_color',
            [
                'label'     => esc_html__('Today Highlight Color', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours__row.is-today .ohhc-open-hours__day' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'hours_text_color',
            [
                'label'     => esc_html__('Hours Text Color', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours__hours, {{WRAPPER}} .ohhc-open-hours__today-hours' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'day_typography',
                'selector' => '{{WRAPPER}} .ohhc-open-hours__day',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'hours_typography',
                'selector' => '{{WRAPPER}} .ohhc-open-hours__hours, {{WRAPPER}} .ohhc-open-hours__today-hours',
            ]
        );

        $this->add_responsive_control(
            'row_spacing',
            [
                'label'      => esc_html__('Row Vertical Padding', 'open-hours-and-holiday-closures'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ohhc-open-hours__row' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function register_notice_style_controls(): void
    {
        $this->start_controls_section(
            'section_style_notice',
            [
                'label' => esc_html__('Notice', 'open-hours-and-holiday-closures'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'notice_background',
            [
                'label'     => esc_html__('Notice Background', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours' => '--ohhc-notice-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'notice_text_color',
            [
                'label'     => esc_html__('Notice Text Color', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours__notice' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'notice_border_color',
            [
                'label'     => esc_html__('Notice Accent Border', 'open-hours-and-holiday-closures'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ohhc-open-hours__notice' => 'border-left-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'notice_border_radius',
            [
                'label'      => esc_html__('Notice Radius', 'open-hours-and-holiday-closures'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ohhc-open-hours__notice' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'notice_padding',
            [
                'label'      => esc_html__('Notice Padding', 'open-hours-and-holiday-closures'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ohhc-open-hours__notice' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'notice_typography',
                'selector' => '{{WRAPPER}} .ohhc-open-hours__notice',
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();

        $output = Plugin::instance()->render(
            [
                'view'                => $settings['view'] ?? 'badge',
                'show_status'         => ($settings['show_status'] ?? '') === 'yes',
                'show_next'           => ($settings['show_next'] ?? '') === 'yes',
                'show_closure_notice' => ($settings['show_closure_notice'] ?? '') === 'yes',
                'compact'             => ($settings['compact'] ?? '') === 'yes',
                'accent_color'        => $settings['accent_color'] ?? '',
                'border_radius'       => isset($settings['border_radius']['size']) ? (int) $settings['border_radius']['size'] : '',
            ]
        );

        echo \wp_kses($output, Renderer::get_allowed_html_tags());
    }
}
