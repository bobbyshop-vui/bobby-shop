<?php
/**
 * Plugin Name: Custom Full User Cache with Browser Storage and server cache (Fixed & Enhanced)
 * Description: Cache nội dung trang chủ, admin riêng biệt. Cache an toàn, không gây logout WordPress.
 * Version: 2.1
 * Author: Your Name
 */

defined('ABSPATH') || exit;

// === Tạo thư mục cache ===
function custom_full_cache_create_dirs() {
    $base_dir = WP_CONTENT_DIR . '/custom_cache/';
    $frontend_dir = $base_dir . 'frontend/';
    $admin_dir = $base_dir . 'admin/';

    foreach ([$base_dir, $frontend_dir, $admin_dir] as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
}
add_action('init', 'custom_full_cache_create_dirs');

// === Tạo file cache cho frontend ===
function custom_frontend_cache_file() {
    $cache_dir = WP_CONTENT_DIR . '/custom_cache/frontend/';
    $user_id = is_user_logged_in() ? get_current_user_id() : 'guest';
    $cookie_hash = isset($_COOKIE['wordpress_logged_in_' . COOKIEHASH]) ? md5($_COOKIE['wordpress_logged_in_' . COOKIEHASH]) : 'no_cookie';
    $request_uri = md5($_SERVER['REQUEST_URI']);

    return $cache_dir . $user_id . '_' . $cookie_hash . '_' . $request_uri . '.html';
}

// === Tạo file cache cho admin ===
function custom_admin_cache_file() {
    $cache_dir = WP_CONTENT_DIR . '/custom_cache/admin/';
    $user_id = get_current_user_id();
    $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'dashboard';
    $request_uri = md5($_SERVER['REQUEST_URI']);

    return $cache_dir . 'admin_' . $user_id . '_' . $page . '_' . $request_uri . '.html';
}

// === Kiểm tra file cache còn hiệu lực không ===
function custom_cache_valid($file) {
    $expire_time = 180; // 3 phút
    return file_exists($file) && (time() - filemtime($file)) < $expire_time;
}

// === Bắt đầu cache frontend ===
function custom_frontend_cache_start() {
    if (is_admin() || is_preview() || strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false) {
        return;
    }

    $file = custom_frontend_cache_file();
    if (custom_cache_valid($file)) {
        echo '<div id="custom-cache-content" style="display:none;">';
        readfile($file);
        echo '</div>';
        exit;
    } else {
        ob_start('custom_frontend_cache_callback');
    }
}
add_action('template_redirect', 'custom_frontend_cache_start');

// === Xử lý lưu cache frontend ===
function custom_frontend_cache_callback($buffer) {
    $file = custom_frontend_cache_file();
    file_put_contents($file, $buffer);
    return $buffer;
}

// === Bắt đầu cache admin ===
function custom_admin_cache_start() {
    if (!is_admin() || defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }

    $file = custom_admin_cache_file();
    if (custom_cache_valid($file)) {
        readfile($file);
        exit;
    } else {
        ob_start('custom_admin_cache_callback');
    }
}
add_action('admin_init', 'custom_admin_cache_start');

// === Xử lý lưu cache admin ===
function custom_admin_cache_callback($buffer) {
    $file = custom_admin_cache_file();
    file_put_contents($file, $buffer);
    return $buffer;
}

// === Xóa toàn bộ cache khi cần thiết ===
function custom_full_cache_clear() {
    $frontend_files = glob(WP_CONTENT_DIR . '/custom_cache/frontend/*.html');
    $admin_files = glob(WP_CONTENT_DIR . '/custom_cache/admin/*.html');

    foreach (array_merge($frontend_files, $admin_files) as $file) {
        @unlink($file);
    }
}
add_action('save_post', 'custom_full_cache_clear');
add_action('profile_update', 'custom_full_cache_clear');
add_action('wp_login', 'custom_full_cache_clear');
add_action('wp_logout', 'custom_full_cache_clear');

// === JavaScript lưu LocalStorage cho frontend ===
function custom_frontend_browser_storage() {
    if (is_admin()) return;
    ?>
    <script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        const cacheDiv = document.getElementById("custom-cache-content");
        if (!cacheDiv) return;

        const cacheKey = window.location.href;
        const cachedHTML = cacheDiv.innerHTML;

        if (!localStorage.getItem(cacheKey)) {
            localStorage.setItem(cacheKey, cachedHTML);
        }

        const mainContent = document.querySelector('main, #primary, #content');
        const cachedContent = localStorage.getItem(cacheKey);

        if (mainContent && cachedContent && document.cookie.indexOf("wordpress_logged_in") !== -1) {
            mainContent.innerHTML = cachedContent;
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'custom_frontend_browser_storage');

// === Giữ cookie ổn định và tránh thay đổi liên tục ===
function custom_prevent_cookie_rewrite() {
    if (!is_user_logged_in()) return;

    // Giữ ổn định cookie "wordpress_logged_in"
    setcookie('wordpress_logged_in_' . COOKIEHASH, $_COOKIE['wordpress_logged_in_' . COOKIEHASH], time() + 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
}
add_action('init', 'custom_prevent_cookie_rewrite');
?>
