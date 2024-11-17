<?php
// Load WordPress environment
require_once('wp-blog-header.php');
require_once('wp-load.php');
// Lấy URL của trang chủ
$home_url = get_home_url();

// Lấy URL của trang hiện tại
$current_url = home_url(add_query_arg(array(),$wp->request));
// Get parameters from URL
$selectedCategory = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : 'all';
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$page_id = isset($_GET['page_id']) ? intval($_GET['page_id']) : 0;
$product_slug = isset($_GET['product']) ? sanitize_text_field($_GET['product']) : ''; // Get product slug if available

// Query arguments to fetch products
$args = [
    'post_type' => 'product',
    'posts_per_page' => -1
];

// Handle search queries
if (!empty($search_query)) {
    $args['s'] = $search_query;
}



// Fetch products
$products = new WP_Query($args);

// Handle page_id to fetch specific page content if provided
$page_content = '';
if ($page_id) {
    $page = get_post($page_id);
    if ($page) {
        $page_content = apply_filters('the_content', $page->post_content);
    } else {
        $page_content = '<p>Trang không tồn tại.</p>';
    }
}

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>">
<style>
    /* Căn giữa toàn bộ sản phẩm */
    .woocommerce-product-card {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    /* Nút tùy chỉnh */
    .woocommerce-product-card .btn-custom {
        display: inline-block;
        width: 100%;
        max-width: 300px; /* Giới hạn chiều rộng nút */
        height: 50px;
        padding: 10px;
        text-align: center;
        background-color: #007bff;
        color: #ffffff;
        border: none;
        border-radius: 5px;
        text-decoration: none;
        font-size: 1.05rem;
        margin-bottom: 10px;
    }

    /* Nút tùy chỉnh khi hover */
    .woocommerce-product-card .btn-custom:hover {
        background-color: #0056b3;
        color: #ffffff;
    }

    /* Căn giữa nội dung của thẻ sản phẩm */
    .woocommerce-product-card .card-body {
        text-align: center;
        width: 100%;  /* Đảm bảo chiếm toàn bộ chiều rộng */
    }

    /* Nút thêm vào giỏ */
    .woocommerce-product-card .btn-add-to-cart {
        display: block;
        width: 100%;
        max-width: 300px; /* Giới hạn chiều rộng nút */
        padding: 10px;
        background-color: #28a745;
        color: #ffffff;
        border: none;
        border-radius: 5px;
        font-size: 1.25rem;
        cursor: pointer;
        text-align: center;
        margin-bottom: 10px;
    }

    /* Nút thêm vào giỏ khi hover */
    .woocommerce-product-card .btn-add-to-cart:hover {
        background-color: #218838;
    }

    /* Khi không có hình ảnh sản phẩm */
    .woocommerce-product-card .no-image-text {
        text-align: center;
        padding: 20px;
        font-style: italic;
        color: #888;
    }

    /* Đảm bảo quảng cáo căn đúng vị trí bên phải */
    .ad-column {
        display: inline-block;
        position: fixed; /* Cố định vị trí quảng cáo */
        top: 50%; /* Đặt quảng cáo ở giữa chiều dọc của màn hình */
        right: 0; /* Đảm bảo quảng cáo nằm sát bên phải */
        transform: translateY(-50%); /* Căn giữa theo chiều dọc */
        z-index: 9999; /* Đảm bảo quảng cáo luôn ở trên cùng */
        width: 250px; /* Đặt chiều rộng cho quảng cáo */
        height: auto; /* Đảm bảo quảng cáo có chiều cao tự động */
        background-color: #ffcc00; /* Màu nền quảng cáo */
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1); /* Thêm bóng mờ để nổi bật */
    }

    /* Đảm bảo phần nội dung không bị che khuất khi có quảng cáo */
    .content-column {
        width: calc(100% - 270px); /* Giảm chiều rộng của nội dung để dành chỗ cho quảng cáo */
        margin-right: 270px; /* Khoảng cách giữa quảng cáo và nội dung */
    }

    /* Mobile: Đảm bảo quảng cáo chuyển xuống dưới */
    @media (max-width: 767px) {
        .ad-column {
            position: relative; /* Đặt lại position trên mobile */
            top: 0;
            right: 0;
            transform: none; /* Xóa transform */
            width: 100%; /* Quảng cáo chiếm toàn bộ chiều rộng trên di động */
            margin-bottom: 20px; /* Khoảng cách dưới quảng cáo */
        }

        .content-column {
            width: 100%; /* Nội dung chiếm toàn bộ chiều rộng */
            margin-right: 0; /* Không cần margin khi quảng cáo ở dưới */
        }
    }

    /* Căn giữa footer */
    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .footer-content .contact-info,
    .footer-content .fb-page {
        flex: 1;
        margin: 0 10px;
    }

    .fb-page iframe {
        border: none;
        overflow: hidden;
        width: 100%;
        height: 500px;
    }

    footer {
        background-color: #f8f9fa;
        padding: 20px;
    }

    .logo-image {
        width: 100%;
        height: 100%;
    }
    html, body {
    overflow-x: hidden;
}
</style>
</head>
<body <?php body_class(); ?>>
<nav>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="<?php echo esc_url(home_url('/')); ?>">Bobby shop</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <?php if (is_user_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo esc_url(wc_get_cart_url()); ?>">Giỏ hàng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo wp_logout_url(home_url()); ?>">Đăng xuất</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo wp_login_url(); ?>">Đăng nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo wp_registration_url(); ?>">Đăng ký</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Danh mục
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown" id="categoryDropdown">
                        <a class="dropdown-item" href="?category=all">Tất cả sản phẩm</a>
                        <?php
                        $product_categories = get_terms([
                            'taxonomy' => 'product_cat',
                            'hide_empty' => true,
                        ]);

                        if (!empty($product_categories) && !is_wp_error($product_categories)) {
                            foreach ($product_categories as $category) {
                                $category_link = get_term_link($category);
                                echo "<a class='dropdown-item' href='" . esc_url($category_link) . "'>" . esc_html($category->name) . "</a>";
                            }
                        } else {
                            echo "<p class='dropdown-item'>Không có danh mục nào.</p>";
                        }
                        ?>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="?posts=">Blog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="?page_id=69">Account</a>
                </li>
            </ul>
            <form class="form-inline my-2 my-lg-0" action="<?php echo esc_url(home_url('/')); ?>" method="GET">
                <input class="form-control mr-sm-2" type="search" name="s" placeholder="Tìm kiếm sản phẩm..." aria-label="Tìm kiếm sản phẩm" value="<?php echo esc_attr($search_query); ?>">
                <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Tìm kiếm</button>
            </form>
        </div>
    </nav>
</nav>
<main class="container mt-3 mx-auto">
    <div class="row">
    <!-- Cột nội dung (9 phần trên màn hình lớn, 12 phần trên màn hình nhỏ) -->
    <div class="col-lg-9">
        <?php
        // Lấy tham số GET
        $route = key($_GET); // Lấy key đầu tiên từ $_GET

        switch ($route) {
            case 'posts':
                // Nếu là trang chủ hoặc trang chính
                if (is_home() || is_front_page()) {
                    if (have_posts()) :
                        while (have_posts()) : the_post();
                            ?>
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title"><a href="<?php echo esc_url(get_permalink()); ?>"><?php the_title(); ?></a></h5>
                                    <h6 class="card-subtitle mb-2 text-muted"><?php echo get_the_date(); ?></h6>
                                    <p class="card-text"><?php echo wp_trim_words(get_the_content(), 40, '...'); ?></p>
                                </div>
                            </div>
                            <?php
                        endwhile;
                        the_posts_pagination(array(
                            'prev_text' => __('Previous page', 'textdomain'),
                            'next_text' => __('Next page', 'textdomain'),
                        ));
                    else :
                        ?>
                        <p>No posts found.</p>
                        <style>
                            .row {
                                display: none;
                            }
                        </style>
                        <?php
                    endif;
                }
                break;

            case 'p':
                // Nếu tham số ?p= được sử dụng
                $post_id = intval($_GET['p']); // Lấy ID bài viết từ tham số
                $post = get_post($post_id); // Lấy bài viết tương ứng

                if ($post) : // Nếu bài viết tồn tại
                    setup_postdata($post); // Thiết lập dữ liệu bài viết
                    ?>
                    <article>
                        <h1><?php the_title(); ?></h1>
                        <div><?php the_content(); ?></div>
                        <div class="comments-section mt-4">
                            <?php
                            // Kiểm tra và hiển thị bình luận nếu được mở
                            if (comments_open() || get_comments_number()) :
                                comments_template(); // Tải template bình luận, bao gồm phần nhập bình luận và phần hiển thị bình luận
                            endif;
                            ?>
                        </div>
                    </article>
                    <?php
                    wp_reset_postdata(); // Đặt lại dữ liệu bài viết
                else :
                    // Nếu bài viết không tồn tại
                    echo '<p>Post not found.</p>';
                endif;
                break;

            case 'page':
                // Hiển thị nội dung của các trang
                while (have_posts()) : the_post();
                    ?>
                    <article>
                        <h1><?php the_title(); ?></h1>
                        <div><?php the_content(); ?></div>
                    </article>
                    <?php
                endwhile;
                break;

            default:
                break;
        }
        ?>
    </div>

    <!-- Cột quảng cáo chỉ hiển thị khi có tham số 'posts' hoặc 'p' -->
    <?php if (isset($_GET['posts']) || isset($_GET['p'])): ?>
        <div class="col-lg-3 col-md-4">
            <div class="sidebarad">
                <?php the_ad(224); // Hiển thị quảng cáo với ID là 224 ?>
            </div>
        </div>
    <?php endif; ?>
</div>
        <div class="col-lg-9">
            <?php
            $product_cat = isset($_GET['product_cat']) ? sanitize_text_field($_GET['product_cat']) : '';
            // Kiểm tra xem danh mục có tồn tại không
            if (!empty($product_cat)) {
                // Sử dụng shortcode để hiển thị sản phẩm trong danh mục
                echo do_shortcode('[products category="' . esc_attr($product_cat) . '"]');
            }
            function display_product_by_slug() {
                // Lấy slug sản phẩm từ URL
                $slug = get_query_var('product');
            
                if (!$slug) {
                    return 'Không có sản phẩm nào được tìm thấy.';
                }
            
                // Lấy sản phẩm dựa trên slug
                $product_post = get_page_by_path($slug, OBJECT, 'product');
            
                if (!$product_post) {
                    return 'Sản phẩm không tồn tại.';
                }
            
                // Chuyển đổi ID sản phẩm thành đối tượng sản phẩm
                $product = wc_get_product($product_post->ID);
            
                // Kiểm tra sản phẩm có hợp lệ không
                if (!$product || !is_a($product, 'WC_Product')) {
                    return 'Sản phẩm không tồn tại.';
                }
            
                // Bắt đầu output buffer
                ob_start();
                ?>
                <div class="woocommerce">
                    <div class="product">
                        <div class="col-md-2">
                    <?php 
                    // Kiểm tra nếu URL có tham số 'product'
                    if (isset($_GET['product']) && !empty($_GET['product'])): 
                    ?>
                        </div>
                    <?php endif; ?>
                </div>
                        <div class="row">
                            <!-- Hình ảnh sản phẩm -->
                            <div class="col-md-4">
                                <div class="product-image">
                                    <?php echo $product->get_image(); ?>
                                </div>
                            </div>
                    
                            <!-- Chi tiết sản phẩm -->
                            <div class="col-md-8">
                                <div class="product-details">
                                    <h2 class="product_title entry-title"><?php echo esc_html($product->get_name()); ?></h2>
                                    <div class="woocommerce-product-details__short-description">
                                        <?php echo wp_kses_post($product->get_description()); ?>
                                        <div class="d-flex">
                                    </div>
                                    </div>
                                    <p class="price"><?php echo $product->get_price_html(); ?></p>
                                    
                                    <!-- Các nút thêm vào giỏ và mua ngay -->
                                    <div class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
                                        <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" class="btn btn-success"><?php esc_html_e('Cho vào giỏ hàng', 'woocommerce'); ?></a>
                                        <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" class="btn btn-primary"><?php esc_html_e('Mua ngay', 'woocommerce'); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                        <div class="woocommerce-comments">
                            <h2><?php esc_html_e('Bình luận', 'woocommerce'); ?></h2>
                        
                            <?php
                            // Kiểm tra nếu biến $product_slug có giá trị
                            global $product_slug;
                        
                            if (!empty($product_slug)) {
                                // Lấy sản phẩm theo slug
                                $product = wc_get_product(get_posts(array(
                                    'name' => $product_slug,
                                    'post_type' => 'product',
                                    'posts_per_page' => 1,
                                    'fields' => 'ids'
                                ))[0]);
                        
                                // Kiểm tra nếu sản phẩm không hợp lệ
                                if (!$product || !is_a($product, 'WC_Product')) {
                                    echo '<p>' . esc_html__('Sản phẩm không hợp lệ hoặc không tồn tại.', 'woocommerce') . '</p>';
                                    return;
                                }
                        
                                // Lấy số lượng bình luận và tên sản phẩm
                                $comments_number = get_comments_number($product->get_id());
                                $product_name = $product->get_name();
                        
                                // Kiểm tra nếu bình luận đang mở
                                if (!comments_open()) {
                                    return;
                                }
                            ?>
                            
                            <div id="reviews" class="woocommerce-Reviews">
                                <div id="comments">
                                    <h2 class="woocommerce-Reviews-title">
                                        <?php
                                        $count = $product->get_review_count();
                                        if ($count && wc_review_ratings_enabled()) {
                                            $reviews_title = sprintf(esc_html(_n('%1$s review for %2$s', '%1$s reviews for %2$s', $count, 'woocommerce')), esc_html($count), '<span>' . get_the_title() . '</span>');
                                            echo apply_filters('woocommerce_reviews_title', $reviews_title, $count, $product);
                                        } else {
                                            esc_html_e('Reviews', 'woocommerce');
                                        }
                                        ?>
                                    </h2>
                                        <ol class="commentlist">
                                            <?php
                                            global $wpdb;
                                            
                                            // Kiểm tra nếu trang hiện tại là sản phẩm
                                            if (is_product()) {
                                                $post = get_post();  // Lấy đối tượng post hiện tại
                                                $product_id = $post->ID;  // Truy cập ID của sản phẩm từ đối tượng post
                                            }
                                        
                                            // Truy vấn lấy các bình luận và rating từ cơ sở dữ liệu
                                            $comments = $wpdb->get_results(
                                                $wpdb->prepare(
                                                    "
                                                    SELECT 
                                                        c.comment_ID, 
                                                        c.comment_author, 
                                                        c.comment_content, 
                                                        cm.meta_value AS rating,
                                                        c.user_id AS comment_user_id  -- Thêm trường user_id vào để kiểm tra quyền sở hữu
                                                    FROM 
                                                        {$wpdb->comments} AS c
                                                    LEFT JOIN 
                                                        {$wpdb->commentmeta} AS cm ON c.comment_ID = cm.comment_id 
                                                        AND cm.meta_key = 'rating'
                                                    WHERE 
                                                        c.comment_post_ID = %d 
                                                        AND c.comment_approved = 1 
                                                        AND (c.comment_type = '' OR c.comment_type = 'review')
                                                    ORDER BY 
                                                        c.comment_date DESC
                                                    ",
                                                    $product_id  // Sử dụng $product_id để lấy bình luận cho sản phẩm
                                                )
                                            );
                                        
                                            // Kiểm tra và hiển thị bình luận
                                            if ($comments) {
                                                foreach ($comments as $comment) {
                                                    echo '<li class="comment">';
                                                    echo '<div class="comment-author">' . esc_html($comment->comment_author) . '</div>';
                                                    echo '<div class="comment-content">' . esc_html($comment->comment_content) . '</div>';
                                        
                                                    // Hiển thị đánh giá sao nếu có
                                                    if ($comment->rating) {
                                                        echo '<div class="comment-rating">';
                                                        echo str_repeat('★', intval($comment->rating)); // Hiển thị số sao
                                                        echo '</div>';
                                                    }
                                        
                                                    // Thêm nút chỉnh sửa nếu người dùng là chủ của bình luận hoặc admin
                                                    if (is_user_logged_in() && (get_current_user_id() == $comment->comment_user_id || current_user_can('administrator'))) {
                                                        $edit_url = get_edit_comment_link($comment->comment_ID); // Lấy URL chỉnh sửa bình luận
                                                        echo '<div class="comment-edit-link">';
                                                        echo '<a href="' . esc_url($edit_url) . '">' . esc_html__('Chỉnh sửa', 'woocommerce') . '</a>';
                                                        echo '</div>';
                                                    }
                                        
                                                    echo '</li>';
                                                }
                                            } else {
                                                echo '<li>' . esc_html__('Hiện đang chưa có bình luận nào.', 'woocommerce') . '</li>';
                                            }
                                            ?>
                                        </ol>
                                        <?php if (get_comment_pages_count() > 1 && get_option('page_comments')) : ?>
                                            <nav class="woocommerce-pagination">
                                                <?php
                                                paginate_comments_links(
                                                    apply_filters(
                                                        'woocommerce_comment_pagination_args',
                                                        array(
                                                            'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
                                                            'next_text' => is_rtl() ? '&larr;' : '&rarr;',
                                                            'type' => 'list',
                                                        )
                                                    )
                                                );
                                                ?>
                                            </nav>
                                        <?php endif; ?>
                                </div>
                        
                                <?php if (get_option('woocommerce_review_rating_verification_required') === 'no' || wc_customer_bought_product('', get_current_user_id(), $product->get_id())) : ?>
                                    <div id="review_form_wrapper">
                                        <div id="review_form">
                                            <?php
                                            $commenter = wp_get_current_commenter();
                                            $comment_form = array(
                                                'title_reply' => have_comments() ? esc_html__('Add a review', 'woocommerce') : sprintf(esc_html__('Be the first to review &ldquo;%s&rdquo;', 'woocommerce'), get_the_title()),
                                                'title_reply_to' => esc_html__('Leave a Reply to %s', 'woocommerce'),
                                                'comment_notes_after' => '',
                                                'label_submit' => esc_html__('Submit', 'woocommerce'),
                                            );
                        
                                            $name_email_required = (bool) get_option('require_name_email', 1);
                                            $fields = array(
                                                'author' => array(
                                                    'label' => __('Name', 'woocommerce'),
                                                    'type' => 'text',
                                                    'value' => $commenter['comment_author'],
                                                    'required' => $name_email_required,
                                                ),
                                                'email' => array(
                                                    'label' => __('Email', 'woocommerce'),
                                                    'type' => 'email',
                                                    'value' => $commenter['comment_author_email'],
                                                    'required' => $name_email_required,
                                                ),
                                            );
                        
                                            $comment_form['fields'] = array();
                                            foreach ($fields as $key => $field) {
                                                $field_html = '<p class="comment-form-' . esc_attr($key) . '">';
                                                $field_html .= '<label for="' . esc_attr($key) . '">' . esc_html($field['label']);
                                                if ($field['required']) {
                                                    $field_html .= '&nbsp;<span class="required">*</span>';
                                                }
                                                $field_html .= '</label><input id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" type="' . esc_attr($field['type']) . '" value="' . esc_attr($field['value']) . '" size="30" ' . ($field['required'] ? 'required' : '') . ' /></p>';
                                                $comment_form['fields'][$key] = $field_html;
                                            }
                        
                                            $account_page_url = wc_get_page_permalink('myaccount');
                                            if ($account_page_url) {
                                                $comment_form['must_log_in'] = '<p class="must-log-in">' . sprintf(esc_html__('You must be %1$slogged in%2$s to post a review.', 'woocommerce'), '<a href="' . esc_url($account_page_url) . '">', '</a>') . '</p>';
                                            }
                        
                                            if (wc_review_ratings_enabled()) {
                                                $comment_form['comment_field'] = '<div class="comment-form-rating"><label for="rating">' . esc_html__('Your rating', 'woocommerce') . (wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '') . '</label><select name="rating" id="rating" required>
                                                    <option value="">' . esc_html__('Rate&hellip;', 'woocommerce') . '</option>
                                                    <option value="5">' . esc_html__('Perfect', 'woocommerce') . '</option>
                                                    <option value="4">' . esc_html__('Good', 'woocommerce') . '</option>
                                                    <option value="3">' . esc_html__('Average', 'woocommerce') . '</option>
                                                    <option value="2">' . esc_html__('Not that bad', 'woocommerce') . '</option>
                                                    <option value="1">' . esc_html__('Very poor', 'woocommerce') . '</option>
                                                </select></div>';
                                            }
                        
                                            $comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__('Your review', 'woocommerce') . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>';
                                            comment_form(apply_filters('woocommerce_product_review_comment_form_args', $comment_form));
                                            ?>
                                        </div>
                                    </div>
                                <?php else : ?>
                                    <p class="woocommerce-verification-required"><?php esc_html_e('Only logged in customers who have purchased this product may leave a review.', 'woocommerce'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                <style>
                    @media (max-width: 768px) {
                        .product-info {
                            flex-direction: column; /* Đặt chiều dọc cho layout trên điện thoại */
                            align-items: flex-start; /* Căn trái cho hình ảnh và thông tin sản phẩm */
                        }
                        .product-image {
                            margin-right: 0; /* Bỏ margin bên phải cho điện thoại */
                            margin-bottom: 20px; /* Thêm margin dưới hình ảnh */
                            max-width: 100%; /* Đảm bảo hình ảnh không vượt quá chiều rộng của khối cha */
                        }
                    }
                </style>
            <?php
            }
            }
            if ($product_slug) {
                echo display_product_by_slug();
            } elseif ($page_id) {
                // Display page content if page_id is provided
                ?>
                <div class="col-md-12">
                    <?php echo $page_content; ?>
                </div>
            <?php
            }
            // Kiểm tra xem URL có chứa tham số 'posts', 'p', 'page', 'page_id', 's' hoặc 'product_cat'
            if (isset($_GET['posts']) || isset($_GET['p']) || isset($_GET['page']) || isset($_GET['page_id']) || isset($_GET['s']) || isset($_GET['product_cat']) || isset($_GET['product'])) {
                // Nếu có tham số tìm kiếm (s=), bạn muốn hiển thị kết quả tìm kiếm
                if (isset($_GET['s'])) {
                    // Thực hiện truy vấn tìm kiếm với từ khóa từ tham số 's'
                    $search_query = new WP_Query(array(
                        'post_type' => 'product', // Lọc chỉ sản phẩm
                        's' => sanitize_text_field($_GET['s']), // Lấy giá trị từ 's' trong URL
                    ));
                
                    if ($search_query->have_posts()) {
                        echo '<div class="row">';
                        while ($search_query->have_posts()) {
                            $search_query->the_post();
                            ?>
                            <div class="col-md-4 mb-4">
                                <div class="product-card woocommerce-product-card">
                                    <?php if (has_post_thumbnail()): ?>
                                        <a href="<?php the_permalink(); ?>" class="product-link">
                                            <img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'medium')); ?>" class="card-img-top" alt="<?php the_title_attribute(); ?>">
                                        </a>
                                    <?php else: ?>
                                        <div class="no-image-text">Không có ảnh sản phẩm</div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="<?php the_permalink(); ?>" class="product-link"><?php the_title(); ?></a>
                                        </h5>
                                        <p class="card-text"><?php echo wp_trim_words(get_the_excerpt(), 15); ?></p>
                                        <?php
                                        $product = wc_get_product(get_the_ID());
                                        ?>
                                        <!-- Nút "Thêm vào giỏ hàng" -->
                                        <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" class="btn btn-primary"><?php esc_html_e('Thêm vào giỏ hàng', 'woocommerce'); ?></a>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        echo '</div>';
                    } else {
                        echo '<p>Không có sản phẩm nào phù hợp với từ khóa tìm kiếm.</p>';
                    }
                    wp_reset_postdata(); // Reset lại truy vấn sau khi tìm kiếm xong
                }
                ?>
                
                <!-- CSS trực tiếp cho sản phẩm -->
                <style>
                /* Giao diện sản phẩm */
                .woocommerce .products {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 30px;
                }
                
                .woocommerce .product-card {
                    background-color: #fff;
                    border: 1px solid #ddd;
                    border-radius: 10px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                    transition: all 0.3s ease;
                }
                
                .woocommerce .product-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
                }
                
                .woocommerce .product-card img {
                    width: 100%;
                    height: auto;
                    display: block;
                }
                
                .woocommerce .product-card .card-body {
                    padding: 15px;
                }
                
                .woocommerce .product-card .card-body .card-title {
                    font-size: 1.25rem;
                    margin-bottom: 10px;
                    font-weight: bold;
                }
                
                .woocommerce .product-card .card-body .card-text {
                    font-size: 0.9rem;
                    color: #555;
                }
                
                .woocommerce .product-card .btn-primary {
                    background-color: #28a745;
                    color: #fff;
                    padding: 10px;
                    text-align: center;
                    display: inline-block;
                    text-decoration: none;
                }
                
                .woocommerce .product-card .btn-primary:hover {
                    background-color: #218838;
                }
                
                /* Thêm hover cho sản phẩm */
                .product-card:hover .card-body {
                    background-color: #f7f7f7;
                }
                
                .product-link {
                    color: #333;
                    text-decoration: none;
                }
                
                .product-link:hover {
                    color: #007cba;
                    text-decoration: underline;
                }
                </style>
            <?php
            } else {
                // Nếu không có tham số tìm kiếm, hiển thị sản phẩm mặc định
                ?>
<div class="row">
    <!-- Cột sản phẩm -->
    <div class="col-md-10">
        <div class="row">
            <?php 
            // Sử dụng shortcode WooCommerce để hiển thị tất cả sản phẩm
            echo do_shortcode('[products]'); 
            ?>
        </div>
    </div>
    <!-- Cột quảng cáo bên phải -->
    <div class="col-md-2">
        <?php 
        if ($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == ''): 
        ?>
            <div class="d-flex justify-content-end">
                <div class="sidebarad p-2 ms-auto">
                    <?php the_ad(224); // Hiển thị quảng cáo với ID là 224 ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
</div>
            <?php } ?>
        </div>
    </div>
</main>
<footer>
    <footer class="bg-light py-6">
    <div class="container">
        <div class="footer-content">
            <div class="contact-info">
                <p>&copy; <?php echo date('Y'); ?> - <?php bloginfo('name'); ?>. Tất cả quyền được bảo lưu.</p>
                <p><strong>Thông tin liên hệ:</strong></p>
                <p>Email: <a href="mailto:info@bobby-shop.com">info@bobby-shop.com</a></p>
                <p>Điện thoại: <a href="tel:+84374719431">0374719431</a></p> <!-- Số điện thoại -->
            </div>
            <div class="fb-page">
                <iframe src="https://www.facebook.com/plugins/page.php?href=https%3A%2F%2Fwww.facebook.com%2Fprofile.php%3Fid%3D61558580887545&tabs=timeline&width=340&height=500&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=true&appId" 
                        width="340" 
                        height="500" 
                        style="border:none;overflow:hidden" 
                        scrolling="no" 
                        frameborder="0" 
                        allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" 
                        allowfullscreen>
                </iframe>
            </div>
        </div>
    </div>
</footer>

</footer>

<?php wp_footer(); ?>
</body>
</html>