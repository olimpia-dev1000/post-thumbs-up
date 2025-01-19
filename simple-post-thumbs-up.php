<?php
/*
Plugin Name: Simple Post Thumbs Up
Plugin URI: https://github.com/olimpia-dev1000/post-thumbs-up
Description: Adds thumbs up functionality to posts with IP tracking
Version: 1.2
Author URI: https://olimpiadev.nl
GitHub Plugin URI: https://github.com/olimpia-dev1000/post-thumbs-up
GitHub Branch: main
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Simple_Post_Thumbs_Up
{
    public function __construct()
    {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        add_action('wp_ajax_thumbs_up', array($this, 'handle_thumbs_up'));
        add_action('wp_ajax_nopriv_thumbs_up', array($this, 'handle_thumbs_up'));
        add_shortcode('thumbs_up_button', array($this, 'shortcode_thumbs_up'));
        add_action('wp_enqueue_scripts', array($this, 'load_dashicons'));
    }

    public function init()
    {
        if (!get_option('simple_post_thumbs_up_initialized')) {
            add_option('simple_post_thumbs_up_initialized', true);
        }
    }

    function load_dashicons()
    {
        wp_enqueue_style('dashicons');
    }

    public function register_scripts()
    {
        wp_register_style(
            'simple-thumbs-up-style',
            plugins_url('css/thumbs-up.css', __FILE__),
            array(),
            '1.2'
        );
        wp_enqueue_style('simple-thumbs-up-style');

        wp_register_script(
            'simple-thumbs-up-script',
            plugins_url('js/thumbs-up.js', __FILE__),
            array('jquery'),
            '1.2',
            true
        );
        wp_enqueue_script('simple-thumbs-up-script');

        wp_localize_script('simple-thumbs-up-script', 'thumbsUpAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('thumbs-up-nonce')
        ));
    }

    private function get_user_ip()
    {
        // Check for proxy addresses first
        $headers = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                $ip_array = explode(',', $_SERVER[$header]);
                return trim($ip_array[0]);
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }

    private function has_user_liked($post_id)
    {
        $user_ip = $this->get_user_ip();
        $liked_ips = get_post_meta($post_id, 'liked_ips', true);

        if (!is_array($liked_ips)) {
            $liked_ips = array();
        }

        return in_array($user_ip, $liked_ips);
    }

    private function add_ip_to_likes($post_id)
    {
        $user_ip = $this->get_user_ip();
        $liked_ips = get_post_meta($post_id, 'liked_ips', true);

        if (!is_array($liked_ips)) {
            $liked_ips = array();
        }

        $liked_ips[] = $user_ip;
        update_post_meta($post_id, 'liked_ips', array_unique($liked_ips));
    }

    public function handle_thumbs_up()
    {
        check_ajax_referer('thumbs-up-nonce', 'nonce');

        $post_id = intval($_POST['post_id']);

        // Check if IP has already liked
        if ($this->has_user_liked($post_id)) {
            wp_send_json_error(array(
                'message' => 'You have already liked this post',
                'likes' => get_post_meta($post_id, 'post_likes', true) ?: 0
            ));
            return;
        }

        // Add the IP to liked list
        $this->add_ip_to_likes($post_id);

        // Update like count
        $likes = get_post_meta($post_id, 'post_likes', true) ?: 0;
        $likes++;
        update_post_meta($post_id, 'post_likes', $likes);

        wp_send_json_success(array(
            'message' => 'Like added successfully',
            'likes' => $likes
        ));
    }

    public function display_thumbs_up_button($post_id = null)
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        $likes = get_post_meta($post_id, 'post_likes', true) ?: 0;
        $has_liked = $this->has_user_liked($post_id);

        $html = '<div class="thumbs-up-container">';
        $html .= '<span class="likes-count">' . esc_html($likes) . '</span>';

        $html .= '<button class="thumbs-up-button ' . ($has_liked ? 'liked' : '') . '" ' .
            'data-post-id="' . esc_attr($post_id) . '" ' .
            ($has_liked ? 'disabled' : '') . '>';
        $html .= '<span class="dashicons dashicons-thumbs-up"></span><span class="likes-text">Likes</span>';
        $html .= '</button>';
        $html .= '</div>';

        return $html;
    }

    public function shortcode_thumbs_up($atts)
    {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID()
        ), $atts);

        return $this->display_thumbs_up_button($atts['post_id']);
    }
}

$simple_post_thumbs_up = new Simple_Post_Thumbs_Up();

register_activation_hook(__FILE__, function () {
    add_option('simple_post_thumbs_up_initialized', true);
});

register_deactivation_hook(__FILE__, function () {
    delete_option('simple_post_thumbs_up_initialized');
});
