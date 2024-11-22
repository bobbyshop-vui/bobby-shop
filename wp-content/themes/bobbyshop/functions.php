<?php
// Thêm hỗ trợ WooCommerce vào theme
function bobbyshop_add_woocommerce_support() {
    add_theme_support('woocommerce');
}
// Kết hợp Bootstrap với WordPress
function bobbyshop_enqueue_scripts() {
    // Bootstrap CSS
    wp_enqueue_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    
    // Bootstrap JS và phụ thuộc
    wp_enqueue_script('jquery');
    wp_enqueue_script('popper-js', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js', ['jquery'], null, true);
    wp_enqueue_script('bootstrap-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', ['jquery', 'popper-js'], null, true);
    
    // Style của theme
    wp_enqueue_style('theme-style', get_stylesheet_uri());

    // Script tùy chỉnh của theme
    wp_enqueue_script('theme-custom-script', get_template_directory_uri() . '/script.js', ['jquery'], null, true);
}
add_action('wp_enqueue_scripts', 'bobbyshop_enqueue_scripts');

// Tùy chỉnh kiểu dáng của các sản phẩm trong WooCommerce
function bobbyshop_woocommerce_custom_styles() {
    echo '
    <style>
        .woocommerce .products .product {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px;
            border-radius: 5px;
        }
        .woocommerce .product .woocommerce-Price-amount {
            color: #007bff;
        }
        .woocommerce .product .woocommerce-loop-product__title {
            font-size: 1.25rem;
        }
        .dropdown-menu .cat-item {
            list-style: none;
        }

        .dropdown-menu .cat-item a {
            display: block;
            padding: 10px;
            color: #333;
            text-decoration: none;
        }

        .dropdown-menu .cat-item a:hover {
            background-color: #f8f9fa;
            color: #007bff;
        }
        .custom-search-form {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .custom-search-input {
            width: 250px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
            margin-right: 10px;
        }

        .custom-search-button {
            padding: 10px 20px;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .custom-search-button:hover {
            background-color: #0056b3;
        }
        /* Container chính cho phần bình luận */
        .woocommerce-comments {
            margin: 30px 0;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        /* Tiêu đề phần bình luận */
        .woocommerce-comments h2 {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        /* Tiêu đề sản phẩm trong phần bình luận */
        .woocommerce-comments h3.woocommerce-Reviews-title {
            font-size: 20px;
            color: #0073e6;
            margin-bottom: 10px;
        }
        
        /* Số lượng đánh giá */
        .woocommerce-comments h4 {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
        }
        
        /* Danh sách các bình luận */
        .commentlist {
            list-style: none;
            padding-left: 0;
        }
        
        /* Phần tử bình luận */
        .comment {
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
            margin-bottom: 15px;
        }
        
        /* Thông tin tác giả bình luận */
        .comment-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        /* Ảnh đại diện tác giả */
        .comment-author {
            display: flex;
            align-items: center;
        }
        
        .comment-author img {
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .comment-author .author-name {
            font-weight: bold;
            color: #333;
        }
        
        /* Ngày tháng bình luận */
        .comment-date {
            font-size: 14px;
            color: #888;
        }
        
        /* Nội dung bình luận */
        .comment-content {
            font-size: 16px;
            color: #444;
            margin-bottom: 10px;
        }
        
        /* Đánh giá sao */
        .comment-rating p {
            font-size: 16px;
            color: #ffcc00;
        }
        
        /* Form đánh giá và bình luận */
        .woocommerce-Reviews-form {
            margin-top: 30px;
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        /* Trường nhập liệu */
        .woocommerce-Reviews-form p {
            margin-bottom: 15px;
        }
        
        /* Label cho trường nhập liệu */
        .woocommerce-Reviews-form label {
            font-size: 16px;
            color: #333;
            display: block;
            margin-bottom: 5px;
        }
        
        /* Input text và email */
        .woocommerce-Reviews-form input[type="text"],
        .woocommerce-Reviews-form input[type="email"],
        .woocommerce-Reviews-form select,
        .woocommerce-Reviews-form textarea {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
        
        /* Form textarea */
        .woocommerce-Reviews-form textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        /* Nút gửi */
        .woocommerce-Reviews-form .form-submit input[type="submit"] {
            background-color: #0073e6;
            color: #fff;
            font-size: 16px;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        /* Nút gửi hover */
        .woocommerce-Reviews-form .form-submit input[type="submit"]:hover {
            background-color: #005bb5;
        }
        
        /* Trường hợp có thông báo lỗi */
        .woocommerce-Reviews-form .error {
            color: #ff0000;
            font-size: 14px;
        }
        
        /* Style cho các lựa chọn đánh giá sao */
        .woocommerce-Reviews-form select#rating {
            padding: 10px;
        }
        
        /* Cải thiện phần hover của từng bình luận */
        .commentlist .comment:hover {
            background-color: #f1f1f1;
        }
        
        /* Cải thiện phần viền của mỗi bình luận */
        .commentlist .comment {
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .sidebarad {
            display: flex;
            justify-content: flex-end;
        }
    </style>';
}
add_action('wp_head', 'bobbyshop_woocommerce_custom_styles');

// Thay đổi tiêu đề của trang WooCommerce
function bobbyshop_woocommerce_page_title($title, $id) {
    if (is_shop() && $id === get_option('woocommerce_shop_page_id')) {
        return 'Cửa hàng của chúng tôi';
    }
    return $title;
}
add_filter('the_title', 'bobbyshop_woocommerce_page_title', 10, 2);

// Thay đổi văn bản nút "Thêm vào giỏ hàng"
function bobbyshop_custom_add_to_cart_text($text) {
    return __('Mua ngay', 'woocommerce');
}
add_filter('woocommerce_product_single_add_to_cart_text', 'bobbyshop_custom_add_to_cart_text');
add_filter('woocommerce_product_archive_add_to_cart_text', 'bobbyshop_custom_add_to_cart_text');
// Thêm đoạn mã vào functions.php của theme (hoặc child theme)
function custom_redirect_to_wp_login_page() {
    if ( ! is_user_logged_in() ) {
        // Kiểm tra xem người dùng có truy cập vào trang tài khoản WooCommerce không
        if ( is_account_page() ) {
            wp_redirect( wp_login_url() ); // Chuyển hướng đến trang đăng nhập của WordPress
            exit;
        }
    }
}
add_action( 'template_redirect', 'custom_redirect_to_wp_login_page' );
add_filter('woocommerce_add_to_cart_redirect', 'stay_on_current_page');

function stay_on_current_page($url) {
    return wc_get_raw_referer(); // Quay lại trang trước đó (nơi chứa nút bấm)
}
function add_nav_link_class_to_woocommerce_buttons($button) {
    // Kiểm tra và thêm class 'nav-link' vào các nút
    $button = str_replace('class="', 'class="nav-link ', $button);
    return $button;
}

// Áp dụng cho các nút trong danh sách sản phẩm (Add to cart)
add_filter('woocommerce_loop_add_to_cart_link', 'add_nav_link_class_to_woocommerce_buttons', 10, 1);

// Áp dụng cho nút trên trang chi tiết sản phẩm (Add to cart)
add_filter('woocommerce_product_add_to_cart_link', 'add_nav_link_class_to_woocommerce_buttons', 10, 1);

// Áp dụng cho nút xóa sản phẩm khỏi giỏ hàng
add_filter('woocommerce_cart_item_remove_link', 'add_nav_link_class_to_woocommerce_buttons', 10, 1);

// Áp dụng cho nút "Thanh toán" trên trang giỏ hàng/checkout
function add_nav_link_class_to_order_button_html($button_html) {
    $button_html = str_replace('class="', 'class="nav-link ', $button_html);
    return $button_html;
}
add_filter('woocommerce_order_button_html', 'add_nav_link_class_to_order_button_html', 10, 1);

// Áp dụng cho các nút cập nhật giỏ hàng, tiếp tục mua sắm, vv.
function add_nav_link_class_to_cart_actions($button_html) {
    $button_html = str_replace('class="', 'class="nav-link ', $button_html);
    return $button_html;
}
add_filter('woocommerce_cart_actions', 'add_nav_link_class_to_cart_actions', 10, 1);
