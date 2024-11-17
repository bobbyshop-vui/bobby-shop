<?php
/*
Plugin Name: Custom Admin Menu
Description: A plugin to add custom admin menu items for the Shop Manager role and restrict access to other menus.
Version: 1.0
Author: Your Name
*/

// Tạo vai trò Shop Manager
function shoprole_add_shop_manager_role() {
    if (!get_role('shop_manager')) {
        add_role(
            'shop_manager',
            'Shop Manager',
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'edit_pages' => false,
                'edit_others_pages' => false,
                'manage_options' => false,
            )
        );
    }
}
add_action('init', 'shoprole_add_shop_manager_role');

// Thêm menu vào bảng điều khiển của WordPress
function shoprole_add_custom_admin_menu() {
    if (current_user_can('shop_manager')) { // Kiểm tra vai trò 'shop_manager'
        add_menu_page(
            'Quản lý Shop', // Tiêu đề trang
            'Quản lý Shop', // Tiêu đề menu
            'shop_manager', // Quyền truy cập
            'custom-admin-menu', // Slug của trang
            'shoprole_render_custom_admin_menu', // Hàm để hiển thị nội dung trang
            'dashicons-admin-generic', // Biểu tượng menu
            2 // Vị trí menu
        );
    }
}
add_action('admin_menu', 'shoprole_add_custom_admin_menu');

// Hiển thị nội dung của trang quản trị tùy chỉnh
function shoprole_render_custom_admin_menu() {
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Quản lý Shop</h1>
        <div class="list-group">
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>" class="list-group-item list-group-item-action">Danh sách Sản phẩm</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-orders')); ?>" class="list-group-item list-group-item-action">Danh sách Đơn hàng</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=category-manager')); ?>" class="list-group-item list-group-item-action">Quản lý Danh mục</a>
        </div>
    </div>
    <?php
}

// Ngăn không cho vai trò Shop Manager truy cập vào các menu khác
function shoprole_restrict_admin_menu() {
    if (current_user_can('shop_manager')) { // Kiểm tra vai trò 'shop_manager'
        // Xóa các mục menu không cần thiết
        remove_menu_page('index.php'); // Bảng điều khiển
        remove_menu_page('edit.php'); // Bài viết
        remove_menu_page('upload.php'); // Thư viện
        remove_menu_page('edit.php?post_type=page'); // Trang
        remove_menu_page('edit-comments.php'); // Nhận xét
        remove_menu_page('themes.php'); // Giao diện
        remove_menu_page('plugins.php'); // Plugins
        remove_menu_page('users.php'); // Người dùng
        remove_menu_page('tools.php'); // Công cụ
        remove_menu_page('options-general.php'); // Cài đặt
    }
}
add_action('admin_menu', 'shoprole_restrict_admin_menu', 999);

// Giới hạn quyền truy cập các trang không phải menu
function shoprole_restrict_admin_access() {
    if (current_user_can('shop_manager')) { // Kiểm tra vai trò 'shop_manager'
        global $pagenow;
        $allowed_pages = [
            'admin.php?page=custom-admin-menu',
            'edit.php?post_type=product',
            'admin.php?page=wc-orders',
            'admin.php?page=category-manager'
        ];
        // Kiểm tra xem URL hiện tại có trong danh sách cho phép không
        $current_url = $_SERVER['REQUEST_URI'];
        $is_allowed = false;
        foreach ($allowed_pages as $page) {
            if (strpos($current_url, $page) !== false) {
                $is_allowed = true;
                break;
            }
        }
        if (!$is_allowed) {
            wp_redirect(admin_url('admin.php?page=custom-admin-menu'));
            exit;
        }
    }
}
add_action('admin_init', 'shoprole_restrict_admin_access');
