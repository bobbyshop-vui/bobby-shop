<?php
/*
Plugin Name: Instant Click Preloader
Description: Tăng tốc độ load trang bằng cách preload khi hover link.
Version: 1.0
Author: Your Name
*/

defined('ABSPATH') || exit;

// Load file JS vào frontend
function icp_enqueue_scripts() {
    wp_enqueue_script(
        'instant-click',
        plugin_dir_url(__FILE__) . 'instant-click.js',
        [],
        '1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'icp_enqueue_scripts');
