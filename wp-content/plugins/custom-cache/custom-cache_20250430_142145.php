<?php
/**
 * Plugin Name: Full Page Cache by PHP (No JS)
 * Description: Ghi toàn bộ HTML trang ngoài vào file, phân biệt user, không cache wp-admin. Loại bỏ style="display: none". Cookie và browser cache 1 năm.
 * Version: 2.7
 */

defined('ABSPATH') || exit;

// === Tạo thư mục cache ===
function custom_cache_create_dirs() {
    $dir = WP_CONTENT_DIR . '/custom_cache/html/';
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }
}
add_action('init', 'custom_cache_create_dirs');

// === Đặt thời gian cookie đăng nhập là 1 năm ===
add_filter('auth_cookie_expiration', function() {
    return 365 * 24 * 60 * 60; // 1 năm
});

// === Tạo đường dẫn file cache ===
function custom_get_cache_file() {
    $base = WP_CONTENT_DIR . '/custom_cache/html/';
    $user_id = is_user_logged_in() ? get_current_user_id() : 'guest';
    $cookie_hash = isset($_COOKIE['wordpress_logged_in_' . COOKIEHASH]) ? md5($_COOKIE['wordpress_logged_in_' . COOKIEHASH]) : 'no_cookie';
    $uri = md5($_SERVER['REQUEST_URI']);

    return $base . $user_id . '_' . $cookie_hash . '_' . $uri . '.html';
}

// === Kiểm tra file cache hợp lệ không ===
function custom_cache_valid($file) {
    $expire = 365 * 24 * 60 * 60; // 1 năm
    return file_exists($file) && (time() - filemtime($file)) < $expire;
}

// === Gửi header để browser cache (1 năm) ===
function custom_cache_browser_headers() {
    header('Cache-Control: public, max-age=31536000'); // 1 năm
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
}

// === Bắt đầu lưu cache toàn trang (chặn từ đầu) ===
function custom_cache_start_buffering() {
    if (is_admin() || defined('DOING_AJAX') || strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false) return;

    $file = custom_get_cache_file();

    if (custom_cache_valid($file)) {
        custom_cache_browser_headers();
        readfile($file);
        exit;
    }

    ob_start(function ($html) use ($file) {
        // Loại bỏ style="display: none" khỏi inline style
        $html = preg_replace_callback('/style="([^"]*)"/i', function($matches) {
            // Loại bỏ tất cả display:none
            $styles = explode(';', $matches[1]);
            $new_styles = [];

            foreach ($styles as $style) {
                $style = trim($style);
                if (!$style) continue;

                // Bỏ các style "display:none" và các kiểu viết linh tinh
                if (preg_match('/^display\s*:\s*none\s*$/i', $style)) continue;

                $new_styles[] = $style;
            }

            return $new_styles ? 'style="' . implode('; ', $new_styles) . '"' : '';
        }, $html);

        file_put_contents($file, $html);
        return $html;
    });
}
add_action('wp_loaded', 'custom_cache_start_buffering', 0); // Bắt toàn trang từ sớm

// === Xóa cache khi có thay đổi ===
function custom_cache_clear() {
    $files = glob(WP_CONTENT_DIR . '/custom_cache/html/*.html');
    foreach ($files as $f) @unlink($f);
}
add_action('save_post', 'custom_cache_clear');
add_action('profile_update', 'custom_cache_clear');
add_action('wp_login', 'custom_cache_clear');
add_action('wp_logout', 'custom_cache_clear');
