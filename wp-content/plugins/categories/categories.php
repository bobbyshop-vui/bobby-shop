<?php
/**
 * Plugin Name: Category Manager
 * Description: Một plugin để quản lý danh mục sản phẩm với Bootstrap.
 * Version: 1.0
 * Author: Bạn
 * License: GPL2
 */

// Đảm bảo rằng WordPress đang chạy
defined('ABSPATH') or die('No script kiddies please!');

// Thêm trang quản lý danh mục vào menu quản trị
function cm_add_admin_menu() {
    add_menu_page(
        'Quản lý Danh mục',      // Tiêu đề trang
        'Quản lý Danh mục',      // Tiêu đề menu
        'manage_options',        // Quyền truy cập
        'category-manager',      // Slug trang
        'cm_admin_page',        // Hàm để hiển thị nội dung trang
        'dashicons-category'    // Biểu tượng menu
    );
}
add_action('admin_menu', 'cm_add_admin_menu');

// Hàm hiển thị nội dung trang quản lý danh mục
function cm_admin_page() {
    global $wpdb;

    // Xử lý tạo danh mục mới
    if (isset($_POST['cm_add_category'])) {
        $category_name = sanitize_text_field($_POST['cm_category_name']);
        if (!empty($category_name)) {
            wp_insert_term($category_name, 'product_cat');
            echo '<div class="notice notice-success is-dismissible"><p>Danh mục đã được tạo.</p></div>';
        }
    }

    // Xử lý xóa danh mục
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['category_id'])) {
        $category_id = intval($_GET['category_id']);
        wp_delete_term($category_id, 'product_cat');
        echo '<div class="notice notice-success is-dismissible"><p>Danh mục đã được xóa.</p></div>';
    }

    // Lấy danh sách danh mục hiện tại
    $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Quản lý Danh mục</h1>
        
        <!-- Form tạo danh mục mới -->
        <form method="post" action="">
            <h2>Tạo danh mục mới</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cm_category_name">Tên danh mục</label></th>
                    <td><input name="cm_category_name" id="cm_category_name" type="text" class="regular-text" required></td>
                </tr>
            </table>
            <?php submit_button('Tạo danh mục', 'primary', 'cm_add_category'); ?>
        </form>

        <!-- Danh sách danh mục -->
        <h2>Danh sách danh mục</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col">Tên danh mục</th>
                    <th scope="col">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo esc_html($category->name); ?></td>
                        <td>
                            <a href="?page=category-manager&action=delete&category_id=<?php echo esc_attr($category->term_id); ?>" class="button button-secondary" onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">Xóa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Thêm Bootstrap vào admin
function cm_enqueue_admin_styles() {
    wp_enqueue_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
}
add_action('admin_enqueue_scripts', 'cm_enqueue_admin_styles');
?>
