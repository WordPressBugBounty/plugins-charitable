<?php

/**
 * Charitable Donation Form Widget
 *
 * @package Elementor_Example_Plugin
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Charitable_Elementor_Donation_Form_Widget
 */
class Charitable_Elementor_Donation_Form_Widget extends \Elementor\Widget_Base
{
    /**
     * Get widget name.
     *
     * @return string Widget name.
     */
    public function get_name()
    {
        return 'charitable_donation_form';
    }

    /**
     * Get widget title.
     *
     * @return string Widget title.
     */
    public function get_title()
    {
        return esc_html__('Donation Form', 'charitable');
    }

    /**
     * Get widget icon.
     *
     * @return string Widget icon.
     */
    public function get_icon()
    {
        return 'eicon-form-horizontal';
    }

    /**
     * Get widget categories.
     *
     * @return array Widget categories.
     */
    public function get_categories()
    {
        return array( 'wpcharitable' );
    }

    /**
     * Register widget controls.
     */
    protected function register_controls()
    {
        // Content Section.
        $this->start_controls_section(
            'content_section',
            array(
                'label' => esc_html__('Content', 'charitable'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        // Get campaigns for dropdown.
        $campaigns = get_posts(
            array(
                'post_type'   => 'campaign',
                'numberposts' => -1,
                'orderby'     => 'title',
                'order'       => 'ASC',
            )
        );

        $campaign_options = array();
        foreach ($campaigns as $campaign) {
            $campaign_options[ $campaign->ID ] = $campaign->post_title;
        }

        $this->add_control(
            'campaign_id',
            array(
                'label'   => esc_html__('Select Campaign', 'charitable'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => $campaign_options,
                'label_block' => true,
                'dynamic' => ['active' => true],
            )
        );

        $this->add_control(
            'form_type',
            array(
                'label'   => esc_html__('Form Type', 'charitable'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'full'  => esc_html__('Full Form', 'charitable'),
                    'minimal' => esc_html__('Minimal Form', 'charitable'),
                ),
                'default' => 'full',
            )
        );

        $this->end_controls_section();

        // Style Section.
        $this->start_controls_section(
            'style_section',
            array(
                'label' => esc_html__('Style', 'charitable'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'padding',
            array(
                'label'      => esc_html__('Padding', 'charitable'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .charitable-elementor-editor-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'     => 'border',
                'selector' => '{{WRAPPER}} .charitable-elementor-editor-wrapper',
            )
        );

        $this->add_control(
            'background_color',
            array(
                'label'     => esc_html__('Background Color', 'charitable'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .charitable-elementor-editor-wrapper' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend.
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();

        $output = '<div class="charitable-elementor-editor-wrapper">';

        if (! empty($settings['campaign_id'])) {
            // Build shortcode attributes
            $shortcode_atts = array();

            // Campaign ID (required)
            $shortcode_atts[] = 'campaign_id="' . absint($settings['campaign_id']) . '"';

            // Form Type (optional)
            if (! empty($settings['form_type'])) {
                $shortcode_atts[] = 'type="' . esc_attr($settings['form_type']) . '"';
            }

            // Build the complete shortcode

            // charitable_donation_form campaign_id="180" type="full"
            $shortcode = '[charitable_donation_form ' . implode(' ', $shortcode_atts) . ']';


            // Process the shortcode
            $output .= do_shortcode($shortcode);

        }

        $output .= '</div>';

        echo wp_kses_post( $output );
    }
}
