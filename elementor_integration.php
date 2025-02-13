<?php
/*********************************************************************************************
 * Summary for the elementor_integration.php file 
 * Sommaire pour le fichier elementor_integration.php
 *********************************************************************************************
 *
 * 1. add_usqp_widget_category () - Add the "USQP Widgets" Category in Elementor
 *                                      / Ajouter la Catégorie "USQP Widgets" dans Elementor
 * 2. enqueue_elementor_integration_css() - Add elementor_integration css file
 *                                      / Ajouter les fichier css elementor_integration
 * 3. register_usqp_fb_feed_widget () - Save usqp facebook feed widget for Elementor
 *                                      Enregistrer le widget usqp Facebook Feed pour Elementor

 *********************************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// 1. add_usqp_widget_category ()
// Add the "USQP Widgets" Category in Elementor
// / Ajouter la Catégorie "USQP Widgets" dans Elementor
function add_usqp_widget_category( $elements_manager ) {
    $elements_manager->add_category(
        'usqp',
        [
            'title' => __( 'USQP Widgets', 'usqp' ), 
        ]
    );
}

add_action( 'elementor/elements/categories_registered', 'add_usqp_widget_category' );


// 2. enqueue_elementor_integration_css()
// Add elementor_integration css file
// / Ajouter les fichier css elementor_integration
function enqueue_elementor_integration_css() {
    wp_enqueue_style( 'elementor_css_file', plugin_dir_url( __FILE__ ) . 'css/elementor_integration.css' );
}

add_action( 'wp_enqueue_scripts', 'enqueue_elementor_integration_css',-1);

// 3. register_usqp_fb_feed_widget ()
// Save usqp facebook feed widget for Elementor
// / Enregistrer le widget usqp Facebook Feed pour Elementor

// Include the necessary base classes for Elementor
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

// Register the widget with Elementor
function register_usqp_fb_feed_widget( $widgets_manager ) {
    class Facebook_Feed_Widget extends Widget_Base {

        public function get_name() {
            return 'facebook_feed_widget'; 
        }

        public function get_title() {
            return __( 'Facebook Feed', 'usqp' );
        }

        public function get_icon() {
            return 'eicon-facebook-comments';
        }

        public function get_categories() {
            return [ 'usqp' ]; 
        }

        // Function to save widget controls
        // This fonction is used to define the controls of the widget, such as content settings, styles, etc.
        // / Cette fonction est utilisée pour définir les contrôles du widget, tels que les paramètres de contenu, les styles, etc.
        protected function _register_controls() {
            // Section for widget controls
            $this->start_controls_section(
                'content_section',
                [
                    'label' => __( 'Content Settings', 'usqp' ),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            $this->add_control(
                'display_mode',
                [
                    'label' => __( 'Display Mode', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'list' => __( 'List', 'usqp' ),
                        'slider' => __( 'Slider', 'usqp' ),
                    ],
                    'default' => 'list',
                ]
            );

            $this->add_control(
                'content_type',
                [
                    'label' => __( 'Content Type', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'all' => __( 'All', 'usqp' ),
                        'posts' => __( 'Posts', 'usqp' ),
                        'reels' => __( 'Reels', 'usqp' ),
                    ],
                    'default' => 'all',
                ]
            );

            $this->add_control(
                'limit',
                [
                    'label' => __( 'Number of Items to Display', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'min' => -1, 
                    'max' => 100,
                    'step' => 1,
                    'default' => -1,
                ]
            );

            $this->add_control(
                'order',
                [
                    'label' => __( 'Order', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'DESC' => __( 'Descending', 'usqp' ),
                        'ASC' => __( 'Ascending', 'usqp' ),
                    ],
                    'default' => 'DESC',
                ]
            );

            $this->add_control(
                'word_limit',
                [
                    'label' => __( 'Word Limit', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'min' => -1,
                    'max' => 200,
                    'step' => 1,
                    'default' => 75,
                ]
            );

            $this->end_controls_section();

            // Section for widget styles
            // Section for the widget styles (global, slider, list, media, global info, content, link)
            // / Section pour le style du widget (global, slider, list, media, global info, content, link)

            // Section for global style (Global Selector)
            $this->start_controls_section(
                'global_style_section',
                [
                    'label' => __( 'Global Selector', 'usqp' ),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                    
                ]
            );

            $this->add_control(
                'global_padding',
                [
                    'label' => __( 'Padding', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'default' => [
                        'top' => '20',
                        'right' => '20',
                        'bottom' => '20',
                        'left' => '20',
                        'unit' => 'px',
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed ' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->end_controls_section();

            // Section for Slider_skin (Slider layout)
            $this->start_controls_section(
                'slider_skin_section',
                [
                    'label' => __( 'Slider Skin Selector', 'usqp' ),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
            );

            $this->add_control(
                'slides_settings',
                [
                    'label' => __( 'SLIDES SETTINGS', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Background::get_type(),
                [
                    'name' => 'slides_background',
                    'label' => __('Slides Background', 'usqp'),
                    'types' => ['classic', 'gradient'],
                    'selector' => '{{WRAPPER}} .elementor_integration.facebook-feed.slider',
                ]
            );
            

            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name' => 'slides_border',
                    'selector' => '{{WRAPPER}} .elementor_integration.facebook-feed.slider',
                ]
            );

            $this->add_control(
                'slides_border_radius',
                [
                    'label' => __( 'Slides Border Radius', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.slider' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'Arrows_settings',
                [
                    'label' => __( 'ARROWS SETTINGS', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            $this->add_control(
                'arrows_toggle',
                [
                    'label' => __( 'Show Arrows', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'label_on' => __( 'Hide', 'usqp' ),
                    'label_off' => __( 'Show', 'usqp' ),
                    'return_value' => 'none',
                    'default' => '',
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.slider .slick-arrow' => 'display: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->start_controls_tabs('arrows_style_tabs');

            $this->add_control(
                'arrows_scale',
                [
                    'label' => __( 'Arrows Scale', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%'],
                    'range' => [
                        'px' => ['min' => 0.5, 'max' => 3, 'step' => 0.1],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.slider .slick-arrow' => 'transform: scale({{SIZE}});',
                    ],
                ]
            );

            $this->add_control(
                'arrows_top_position',
                [
                    'label' => __( 'Arrows Top Position', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.slider .slick-arrow' => 'top: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->start_controls_tab(
                'arrows_normal',
                [
                    'label' => __( 'Normal', 'usqp' ),
                ]
            );

            $this->add_control(
                'arrows_color',
                [
                    'label' => __( 'Arrows Color', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.slider .slick-arrow::before' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'arrows_background_color',
                [
                    'label' => __( 'Arrows Background Color', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.slider .slick-arrow' => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name' => 'arrows_border',
                    'selector' => '{{WRAPPER}} .elementor_integration.facebook-feed.slider .slick-arrow',
                ]
            );

            $this->end_controls_tab();

            $this->start_controls_tab(
                'arrows_hover',
                [
                    'label' => __( 'Hover', 'usqp' ),
                ]
            );

            $this->add_control(
                'arrows_hover_color',
                [
                    'label' => __( 'Arrows Hover Color', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.slider .slick-arrow:hover::before' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'arrows_hover_background_color',
                [
                    'label' => __( 'Arrows Hover Background Color', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.slider .slick-arrow:hover' => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name' => 'arrows_hover_border',
                    'selector' => '{{WRAPPER}} .elementor_integration.facebook-feed.slider .slick-arrow:hover',
                ]
            );

            $this->end_controls_tab();
            $this->end_controls_tabs();

            $this->add_control(
                'dots_settings',
                [
                    'label' => __( 'DOTS SETTINGS', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            $this->add_control(
                'dots_toggle',
                [
                    'label' => __( 'Show Dots', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'label_on' => __( 'Hide', 'usqp' ),
                    'label_off' => __( 'Show', 'usqp' ),
                    'return_value' => 'none',
                    'default' => '',
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.slider .slick-dots' => 'display: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_control(
                'dots_bottom_position',
                [
                    'label' => __( 'Dots Bottom Position', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%'],
                    'range' => [
                        'px' => ['min' => -100, 'max' => 100, 'step' => 1],
                        '%'  => ['min' => -50, 'max' => 50, 'step' => 1],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => -50,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.slider .slick-dots' => 'bottom: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'dots_color',
                [
                    'label' => __( 'Dots Color', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.slider .slick-dots li button::before' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->end_controls_section();

            // Section for list layout
            $this->start_controls_section(
                'list_skin_section',
                [
                    'label' => __( 'List Skin Selector', 'usqp' ),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
            );

            $this->add_control(
                'list_padding',
                [
                    'label' => __('Padding', 'usqp'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'default' => [
                        'top' => 20,
                        'right' => 20,
                        'bottom' => 20,
                        'left' => 20,
                        'unit' => 'px',
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.list .feed-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Background::get_type(),
                [
                    'name' => 'list_background',
                    'label' => __('Background', 'usqp'),
                    'types' => ['classic', 'gradient'],
                    'selector' => '{{WRAPPER}} .elementor_integration.facebook-feed.list .feed-item',
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name' => 'list_border',
                    'selector' => '{{WRAPPER}} .elementor_integration.facebook-feed.list .feed-item',
                ]
            );

            $this->add_control(
                'list_border_radius',
                [
                    'label' => __('Border Radius', 'usqp'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'default' => [
                        'unit' => 'px',
                        'size' => 0,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.list .feed-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'list_margin',
                [
                    'label' => __('Margin', 'usqp'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'default' => [
                        'top' => 20,
                        'right' => 20,
                        'bottom' => 20,
                        'left' => 20,
                        'unit' => 'px',
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed.list .feed-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->end_controls_section();

            // Section for media styles (Images/Videos)
            $this->start_controls_section(
                'fb_media_section',
                [
                    'label' => __( 'Media Selector (Image/Video)', 'usqp' ),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
            );

            $this->add_control(
                'media_toggle',
                [
                    'label' => __( 'Show Media', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'label_on' => __( 'Hide', 'usqp' ),
                    'label_off' => __( 'Show', 'usqp' ),
                    'return_value' => 'none',
                    'default' => '',
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_media' => 'display: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_control(
                'fb_media_height',
                [
                    'label' => __('Media Height', 'usqp'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%', 'vh'],
                    'range' => [
                        'px' => ['min' => 100, 'max' => 1000, 'step' => 10],
                        '%'  => ['min' => 10, 'max' => 100, 'step' => 1],
                        'vh' => ['min' => 10, 'max' => 100, 'step' => 1],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 400,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_media' => 'height: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'fb_media_padding',
                [
                    'label' => __('Media Padding', 'usqp'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'default' => [
                        'top' => 10,
                        'right' => 10,
                        'bottom' => 10,
                        'left' => 10,
                        'unit' => 'px',
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_media' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'fb_media_border_radius',
                [
                    'label' => __('Media Border Radius', 'usqp'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%'],
                    'range' => [
                        'px' => ['min' => 0, 'max' => 200, 'step' => 1],
                        '%'  => ['min' => 0, 'max' => 50, 'step' => 1],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 0,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_media img' => 'border-radius: {{SIZE}}{{UNIT}};',
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_media video' => 'border-radius: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->end_controls_section();


            // Section for fb_global_info (Global information of the publication)
            $this->start_controls_section(
                'fb_global_info_section',
                [
                    'label' => __( 'Global Info Selector', 'usqp' ),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
            );

            $this->add_control(
                'global_info_toggle',
                [
                    'label' => __( 'Show Global Info', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'label_on' => __( 'Hide', 'usqp' ),
                    'label_off' => __( 'Show', 'usqp' ),
                    'return_value' => 'none',
                    'default' => '',
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_global_info' => 'display: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_control(
                'fb_global_info_padding',
                [
                    'label' => __('Global Info Padding', 'usqp'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'default' => [
                        'top' => 10,
                        'right' => 10,
                        'bottom' => 10,
                        'left' => 10,
                        'unit' => 'px',
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_global_info' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'fb_page_logo_radius',
                [
                    'label' => __('Page Logo Border Radius', 'usqp'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%'],
                    'range' => [
                        'px' => ['min' => 0, 'max' => 100, 'step' => 1],
                        '%'  => ['min' => 0, 'max' => 50, 'step' => 1],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 0,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_global_info .page-logo img' => 'border-radius: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'fb_post_title_typography',
                    'label' => __('Facebook Page Name', 'usqp'),
                    'selector' => '{{WRAPPER}} .elementor_integration.facebook-feed .fb_global_info .post-info h3',
                ]
            );

            $this->add_control(
                'fb_post_title_text_color',
                [
                    'label' => __('Facebook Page Name Color', 'usqp'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_global_info .post-info h3' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'fb_post_text_typography',
                    'label' => __('Publication Date', 'usqp'),
                    'selector' => '{{WRAPPER}} .elementor_integration.facebook-feed .fb_global_info .post-info p',
                ]
            );

            $this->add_control(
                'fb_post_text_color',
                [
                    'label' => __('Publication Date Color', 'usqp'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_global_info .post-info p' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->end_controls_section();


            // Section for fb_content (Publication content)
            $this->start_controls_section(
                'fb_content_section',
                [
                    'label' => __( 'Facebook Content Selector', 'usqp' ),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
            );

            $this->add_control(
                'content_toggle',
                [
                    'label' => __( 'Show Content', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'label_on' => __( 'Hide', 'usqp' ),
                    'label_off' => __( 'Show', 'usqp' ),
                    'return_value' => 'none',
                    'default' => '',
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_content' => 'display: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'fb_text_typography',
                    'label' => __('Content Text', 'usqp'),
                    'selector' => '{{WRAPPER}} .elementor_integration.facebook-feed .fb_content',
                ]
            );

            $this->add_control(
                'fb_content_text_color',
                [
                    'label' => __('Content Text Color', 'usqp'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_content' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'fb_content_padding',
                [
                    'label' => __('Content Padding', 'usqp'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'fb_content_height',
                [
                    'label' => __('Content Height', 'usqp'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em'],
                    'range' => [
                        'px' => ['min' => 50, 'max' => 500, 'step' => 1],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 150,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_content' => 'height: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'fb_content_text_align',
                [
                    'label' => __('Content Text Alignment', 'usqp'),
                    'type' => \Elementor\Controls_Manager::CHOOSE,
                    'options' => [
                        'left' => [
                            'title' => __('Left', 'usqp'),
                            'icon' => 'eicon-text-align-left',
                        ],
                        'center' => [
                            'title' => __('Center', 'usqp'),
                            'icon' => 'eicon-text-align-center',
                        ],
                        'right' => [
                            'title' => __('Right', 'usqp'),
                            'icon' => 'eicon-text-align-right',
                        ],
                        'justify' => [
                            'title' => __('Justify', 'usqp'),
                            'icon' => 'eicon-text-align-justify',
                        ],
                    ],
                    'default' => 'justify',
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_content' => 'text-align: {{VALUE}};',
                    ],
                ]
            );

            $this->end_controls_section();


            // Section for fb_link (Post link)
            $this->start_controls_section(
                'fb_link_section',
                [
                    'label' => __( 'Link Button Selector', 'usqp' ),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
            );
            
            $this->start_controls_tabs('fb_link_tabs');

            $this->add_control(
                'link_toggle',
                [
                    'label' => __( 'Show Link', 'usqp' ),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'label_on' => __( 'Hide', 'usqp' ),
                    'label_off' => __( 'Show', 'usqp' ),
                    'return_value' => 'none',
                    'default' => '',
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_view-link' => 'display: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->start_controls_tab(
                'fb_link_normal',
                [
                    'label' => __( 'Normal', 'usqp' ),
                ]
            );

            $this->add_control(
                'fb_link_padding_normal',
                [
                    'label' => __('Link Button Padding (Normal)', 'usqp'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'default' => [
                        'top' => 10,
                        'right' => 40,
                        'bottom' => 10,
                        'left' => 40,
                        'unit' => 'px',
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_view-link a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            
            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'fb_link_typography', 
                    'label' => __('Link Typography', 'usqp'), 
                    'selector' => '{{WRAPPER}} .elementor_integration.facebook-feed .fb_view-link a', 
                ]
            );

            $this->add_control(
                'fb_link_text_color_normal',
                [
                    'label' => __('Link Text Color (Normal)', 'usqp'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#000',
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_view-link a' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'fb_link_bg_color_normal',
                [
                    'label' => __('Link Background Color (Normal)', 'usqp'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#ffffff',
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_view-link a' => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name' => 'fb_link_border', 
                    'label' => __('Link Border', 'usqp'), 
                    'selector' => '{{WRAPPER}} .elementor_integration.facebook-feed .fb_view-link a', 
                ]
            );

            $this->add_control(
                'fb_link_border_radius_normal',
                [
                    'label' => __('Link Border Radius (Normal)', 'usqp'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%'],
                    'range' => [
                        'px' => ['min' => 0, 'max' => 50, 'step' => 1],
                        '%'  => ['min' => 0, 'max' => 50, 'step' => 1],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 50,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_view-link a' => 'border-radius: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->end_controls_tab();

            $this->start_controls_tab(
                'fb_link_hover',
                [
                    'label' => __( 'Hover', 'usqp' ),
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'fb_link_typography', 
                    'label' => __('Link Typography', 'usqp'), 
                    'selector' => '{{WRAPPER}} .elementor_integration.facebook-feed .fb_view-link a:hover', 
                ]
            );

            $this->add_control(
                'fb_link_text_color_hover',
                [
                    'label' => __('Link Text Color (Hover)', 'usqp'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#ffffff',
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_view-link a:hover' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'fb_link_bg_color_hover',
                [
                    'label' => __('Link Background Color (Hover)', 'usqp'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => 'grey',
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_view-link a:hover' => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name' => 'fb_link_border_hover', 
                    'label' => __('Link Border (Hover)', 'usqp'), 
                    'selector' => '{{WRAPPER}} .elementor_integration.facebook-feed .fb_view-link a:hover', 
                ]
            );

            $this->add_control(
                'fb_link_border_radius_hover',
                [
                    'label' => __('Link Border Radius (Hover)', 'usqp'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%'],
                    'range' => [
                        'px' => ['min' => 0, 'max' => 50, 'step' => 1],
                        '%'  => ['min' => 0, 'max' => 50, 'step' => 1],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .elementor_integration.facebook-feed .fb_view-link a:hover' => 'border-radius: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->end_controls_tab();
            $this->end_controls_tabs();

        $this->end_controls_section();

        $this->start_controls_section(
            'css_custom',
            [
            'label' => __( 'Custom CSS', 'usqp' ), 
            'tab' => \Elementor\Controls_Manager::TAB_STYLE, 
            ]
        );
        $this->add_control(
            'custom_css',
            [
                'label' => __( 'Enter Your Custom CSS', 'usqp' ),
                'type' => \Elementor\Controls_Manager::CODE,
                'language' => 'css',
                'placeholder' => '.elementor_integration .facebook-feed .fb_content .read-more { color: red; }',
                'description' => __( 'Add your custom CSS here (with .elementor_integration prefix).', 'usqp' ) . 
                    '<br><br><strong>' . __( 'Available classes:', 'usqp' ) . '</strong><br>' . 
                    // List of classes available with .elementor_integration
                    '<div class="css-list-container">
                        <pre>.elementor_integration.facebook-feed { }</pre>
                        <pre>.elementor_integration.facebook-feed.slider { }</pre>
                        <pre>.elementor_integration.facebook-feed.slider .slick-arrow { }</pre>
                        <pre>.elementor_integration.facebook-feed.slider .slick-arrow:hover { }</pre>
                        <pre>.elementor_integration.facebook-feed.slider .slick-arrow::before { }</pre>
                        <pre>.elementor_integration.facebook-feed.slider .slick-next::before { }</pre>
                        <pre>.elementor_integration.facebook-feed.slider .slick-prev::before { }</pre>
                        <pre>.elementor_integration.facebook-feed.slider .slick-dots { }</pre>
                        <pre>.elementor_integration.facebook-feed.slider .slick-dots li button::before { }</pre>
                        <pre>.elementor_integration.facebook-feed.list { }</pre>
                        <pre>.elementor_integration.facebook-feed.list .feed-item { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_media { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_media video { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_media img { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_global_info { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_global_info .page-logo { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_global_info .page-logo img { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_global_info .post-info { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_global_info .post-info h3 { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_global_info .post-info p { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_content { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_content .short-text { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_content .full-text { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_content .read-more { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_content .read-less { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_view-link { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_view-link a { }</pre>
                        <pre>.elementor_integration.facebook-feed .fb_view-link a:hover { }</pre>
                    </div>',
            ]
        );

        $this->end_controls_section();      
        }

        // Function to render the widget
        protected function render() {
            // Get widget settings
            $settings = $this->get_settings_for_display();

            // Build the shortcode attribute
            $atts = [
                'limit' => $settings['limit'],
                'order' => $settings['order'],
                'word_limit' => $settings['word_limit'],
                'display' => $settings['display_mode'],
                'type' => $settings['content_type'],
                ];

            // Show content using shortcode
            echo do_shortcode( '[usqp_fb_feed integration="elementor_integration" limit="' . $atts['limit'] . '" order="' . $atts['order'] . '" word_limit="' . $atts['word_limit'] . '" display="' . $atts['display'] . '" type="' . $atts['type'] . '"]' );   

            // Get custom CSS
            $custom_css = ! empty( $settings['custom_css'] ) ? $settings['custom_css'] : ''; 
            // Inject custom CSS into a <style> if defined
            if ( ! empty( $custom_css ) ) {
            echo '<style>
                ' . esc_html( $custom_css ) . '
              </style>';
            }

        }
        }
        // Save the widget in Elementor
        $widgets_manager->register( new \Facebook_Feed_Widget() );

    }

    add_action( 'elementor/widgets/register', 'register_usqp_fb_feed_widget' );



