<?php
/*
Plugin Name: Custom Admin Links
Description: A plugin to add custom admin menu items for products, orders, and categories.
Version: 1.1
Author: Your Name
*/

// Thêm menu vào bảng điều khiển của WordPress
function cal_add_custom_admin_menu() {
    add_menu_page(
        'Quản lý Shop', // Tiêu đề trang
        'Quản lý Shop', // Tiêu đề menu
        'manage_options', // Quyền truy cập
        'custom-admin-menu', // Slug của trang
        'cal_render_custom_admin_menu', // Hàm để hiển thị nội dung trang
        'dashicons-admin-generic', // Biểu tượng menu
        2 // Vị trí menu
    );
}
add_action('admin_menu', 'cal_add_custom_admin_menu');

// Hiển thị nội dung của trang quản trị tùy chỉnh
function cal_render_custom_admin_menu() {
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Quản lý Shop</h1>
        <div class="row">
            <div class="col-md-4">
                <div class="list-group">
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>" class="list-group-item list-group-item-action">Danh sách Sản phẩm</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-orders')); ?>" class="list-group-item list-group-item-action">Danh sách Đơn hàng</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=category-manager')); ?>" class="list-group-item list-group-item-action">Quản lý Danh mục</a>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Ngăn người dùng với vai trò không được phép truy cập vào trang quản trị chính
function cal_restrict_admin_access() {
    if (current_user_can('shop')) {
        global $pagenow;
        if ($pagenow === 'index.php' || $pagenow === 'admin.php' && !isset($_GET['page']) || $pagenow === 'plugins.php') {
            wp_redirect(admin_url('admin.php?page=custom-admin-menu'));
            exit;
        }
    }
}
add_action('admin_init', 'cal_restrict_admin_access');
