<?php
/**
 * Plugin Name: WooCommerce Custom Email Sender
 * Plugin URI: https://yourwebsite.com/
 * Description: Gửi email với giao diện HTML tùy chỉnh trong WooCommerce.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com/
 * License: GPL2
 */

// Hủy bỏ trực tiếp khi không phải là một phần của WordPress
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Thêm menu trong phần Quản trị
function wce_add_menu() {
    add_menu_page( 
        'Gửi Email Tùy Chỉnh', 
        'Gửi Email Tùy Chỉnh', 
        'manage_options', 
        'wce-custom-email', 
        'wce_email_form_page', 
        'dashicons-email-alt', 
        30 
    );
}
add_action( 'admin_menu', 'wce_add_menu' );

// Form để nhập tiêu đề, nội dung email, địa chỉ email người nhận và checkbox chọn gửi cho tất cả người dùng
function wce_email_form_page() {
    ?>
    <div class="wrap">
        <h1>Gửi Email Tùy Chỉnh</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th><label for="email_subject">Tiêu Đề Email</label></th>
                    <td><input name="email_subject" type="text" id="email_subject" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="email_content">Nội Dung Email</label></th>
                    <td>
                        <textarea name="email_content" id="email_content" rows="10" class="large-text" required></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="recipient_email">Gửi Cho Email</label></th>
                    <td><input name="recipient_email" type="email" id="recipient_email" class="regular-text" placeholder="Để trống để gửi cho tất cả người dùng" /></td>
                </tr>
                <tr>
                    <th><label for="send_to_all">Gửi cho tất cả người dùng?</label></th>
                    <td><input name="send_to_all" type="checkbox" id="send_to_all" value="1" /> Gửi cho tất cả người dùng trong WordPress.</td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="send_custom_email" id="send_custom_email" class="button button-primary" value="Gửi Email">
            </p>
        </form>
    </div>
    <?php

    // Kiểm tra và gửi email nếu nút "Gửi Email" được nhấn
    if ( isset( $_POST['send_custom_email'] ) ) {
        $recipient_email = sanitize_email( $_POST['recipient_email'] );
        $subject = sanitize_text_field( $_POST['email_subject'] );
        $content = wp_kses_post( $_POST['email_content'] );
        $send_to_all = isset( $_POST['send_to_all'] ) ? true : false;

        // Gửi email tới người nhận (hoặc tất cả người dùng)
        wce_send_custom_email( $subject, $content, $recipient_email, $send_to_all );
    }
}

// Hàm gửi email
function wce_send_custom_email( $subject, $content, $recipient_email = '', $send_to_all = false ) {
    // Kiểm tra nếu có email người nhận cụ thể, nếu không thì gửi cho tất cả người dùng
    if ( $send_to_all ) {
        $users = get_users(); // Lấy tất cả người dùng WordPress
    } elseif ( ! empty( $recipient_email ) ) {
        $users = array( get_user_by( 'email', $recipient_email ) ); // Tìm người dùng theo email
    } else {
        return;
    }

    // Thiết lập thông tin người gửi
    $from_email = 'info@bobby-shop.com'; // Sử dụng email info@bobby-shop.com
    $from_name  = 'Bobby Shop';

    // Gửi email cho từng người nhận
    foreach ( $users as $user ) {
        if ( $user ) {
            $to_email = $user->user_email;

            // Xây dựng email HTML
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>', // Thêm From: để gửi từ email info@bobby-shop.com
            );
            $message = wce_build_email_html( $content, $subject ); // Truyền cả $subject vào đây

            wp_mail( $to_email, $subject, $message, $headers );
        }
    }

    // Thông báo đã gửi email thành công
    echo '<div class="updated"><p>Email đã được gửi thành công!</p></div>';
}

// Hàm xây dựng nội dung email với giao diện HTML
function wce_build_email_html( $content, $subject ) {
    ob_start();
    ?>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #555; background-color: #f8f8f8; margin: 0; padding: 0; }
            .email-container { width: 100%; padding: 20px; background-color: #f4f4f4; }
            .email-header { background-color: #FFD700; padding: 20px; color: #fff; text-align: center; width: 100%; } /* Màu nền tiêu đề là vàng và chiều rộng 100% */
            .email-footer { background-color: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; color: #888; width: 100%; }
            .email-footer a { color: #FFD700; text-decoration: none; } /* Màu vàng cho liên kết */
            .content { padding: 20px; background-color: #fff; width: 100%; }
        </style>
    </head>
    <body>
        <div class="email-container">
            <!-- Tiêu đề email -->
            <table class="email-header" role="presentation">
                <tr>
                    <td>
                        <h1><?php echo $subject; ?></h1>  <!-- Tiêu đề email được echo từ $subject -->
                    </td>
                </tr>
            </table>

            <!-- Nội dung email -->
            <table class="content" role="presentation">
                <tr>
                    <td>
                        <h2>Chào bạn,</h2>
                        <p><?php echo $content; ?></p>
                    </td>
                </tr>
            </table>

            <!-- Chân trang email -->
            <table class="email-footer" role="presentation">
                <tr>
                    <td>
                        <p>Cảm ơn bạn đã luôn ủng hộ Bobby Shop.</p>
                        <p>Trân trọng,<br>Đội ngũ Bobby Shop</p>
                    </td>
                </tr>
            </table>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
