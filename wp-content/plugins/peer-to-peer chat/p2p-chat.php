<?php
/*
Plugin Name: Peer-to-Peer Chat with Admin and Users
Description: Plugin nhắn tin giữa admin và người dùng. Admin có thể nhắn tin cho bất kỳ người dùng nào, người dùng có thể nhắn tin cho admin và người dùng khác.
Version: 1.0
Author: Your Name
*/

// Tạo bảng trong cơ sở dữ liệu để lưu tin nhắn
function p2p_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'p2p_chat_messages';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        sender_id BIGINT(20) NOT NULL,
        receiver_id BIGINT(20) NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'p2p_create_table');

// Shortcode để hiển thị khung chat cho cả admin và người dùng
function p2p_chat_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Bạn phải đăng nhập để sử dụng tính năng chat.</p>';
    }

    $current_user = wp_get_current_user();
    $admin_id = 1; // ID của admin (nếu admin không phải ID 1, chỉnh lại)
    $user_id = get_current_user_id();

    // Nếu là admin, hiển thị danh sách tất cả người dùng
    if (current_user_can('administrator')) {
        // Lấy tất cả người dùng có thể gửi tin nhắn (không phân biệt vai trò)
        $users = get_users(); // Đây sẽ lấy tất cả người dùng
        $user_select = '<select id="p2p-user-select">';
        foreach ($users as $user) {
            $user_select .= '<option value="' . $user->ID . '">' . $user->display_name . '</option>';
        }
        $user_select .= '</select>';
    } else {
        // Người dùng thông thường chỉ có thể nhắn với admin
        $user_select = '<input type="hidden" id="p2p-user-id" value="' . $admin_id . '">';
    }

    ob_start();
    ?>
    <div id="p2p-chat-box">
        <div id="p2p-messages"></div>
        <?php echo $user_select; ?>
        <form id="p2p-chat-form">
            <textarea id="p2p-message" placeholder="Nhập tin nhắn..."></textarea>
            <button type="submit">Gửi</button>
        </form>
    </div>

    <style>
        #p2p-chat-box {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
        }

        #p2p-messages {
            height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            padding: 5px;
        }

        #p2p-chat-form textarea {
            width: 100%;
            margin-bottom: 10px;
        }

        #p2p-chat-form button {
            width: 100%;
            padding: 10px;
            background-color: #0073aa;
            color: white;
            border: none;
            cursor: pointer;
        }

        #p2p-chat-form button:hover {
            background-color: #005177;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chatForm = document.getElementById('p2p-chat-form');
            const messageField = document.getElementById('p2p-message');
            const userIdField = document.getElementById('p2p-user-id');
            const userSelect = document.getElementById('p2p-user-select');
            const messagesContainer = document.getElementById('p2p-messages');

            // Nạp tin nhắn
            function loadMessages() {
                const receiver_id = userIdField ? userIdField.value : userSelect.value;

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=p2p_get_messages&receiver_id=' + receiver_id
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          const messages = data.data;
                          let messageHtml = '';
                          messages.forEach(msg => {
                              let sender_name = '';
                              // Kiểm tra sender_id và phân biệt giữa người dùng hiện tại và đối phương
                              if (msg.sender_id == <?php echo get_current_user_id(); ?>) {
                                  sender_name = 'Bạn';  // Người gửi là người dùng hiện tại
                              } else {
                                  sender_name = 'Đối phương'; // Người gửi là đối phương (không phải người dùng hiện tại)
                              }
                              messageHtml += `<p><strong>${sender_name}:</strong> ${msg.message}</p>`;
                          });
                          messagesContainer.innerHTML = messageHtml;
                      }
                  });
            }

            loadMessages();
            setInterval(loadMessages, 5000);

            // Gửi tin nhắn
            chatForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const message = messageField.value;
                const receiver_id = userIdField ? userIdField.value : userSelect.value;

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=p2p_save_message&receiver_id=' + receiver_id + '&message=' + encodeURIComponent(message)
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          messageField.value = '';
                          loadMessages();
                      }
                  });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('p2p_chat', 'p2p_chat_shortcode');

// Lưu tin nhắn vào cơ sở dữ liệu
function p2p_save_message() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'p2p_chat_messages';

    $sender_id = get_current_user_id();
    $receiver_id = intval($_POST['receiver_id']);
    $message = sanitize_textarea_field($_POST['message']);

    $wpdb->insert($table_name, [
        'sender_id' => $sender_id,
        'receiver_id' => $receiver_id,
        'message' => $message,
    ]);

    wp_send_json_success('Tin nhắn đã được gửi.');
}
add_action('wp_ajax_p2p_save_message', 'p2p_save_message');
add_action('wp_ajax_nopriv_p2p_save_message', 'p2p_save_message');

// Tải tin nhắn giữa người dùng và admin
function p2p_get_messages() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'p2p_chat_messages';

    $current_user = get_current_user_id();
    $receiver_id = intval($_POST['receiver_id']);

    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE (sender_id = %d AND receiver_id = %d) OR (sender_id = %d AND receiver_id = %d) ORDER BY created_at ASC",
        $current_user, $receiver_id, $receiver_id, $current_user
    ));

    wp_send_json_success($messages);
}
add_action('wp_ajax_p2p_get_messages', 'p2p_get_messages');
add_action('wp_ajax_nopriv_p2p_get_messages', 'p2p_get_messages');
// Thêm menu trong WordPress admin
function p2p_add_admin_menu() {
    add_menu_page(
        'Hướng dẫn sử dụng P2P Chat', // Tiêu đề trang
        'P2P Chat Hướng dẫn',        // Tên menu
        'manage_options',            // Capability (quyền truy cập)
        'p2p-chat-guide',            // Slug (định danh menu)
        'p2p_chat_guide_page',       // Hàm callback hiển thị nội dung
        'dashicons-format-chat',     // Icon
        20                           // Vị trí menu
    );
}
add_action('admin_menu', 'p2p_add_admin_menu');

// Nội dung trang hướng dẫn
function p2p_chat_guide_page() {
    ?>
    <div class="wrap">
        <h1>Hướng dẫn sử dụng Plugin Peer-to-Peer Chat</h1>
        <p>Plugin này cho phép admin và người dùng nhắn tin trực tiếp với nhau.</p>

        <h2>Sử dụng Shortcode</h2>
        <p>Chèn shortcode sau vào bất kỳ bài viết, trang, hoặc widget để hiển thị khung chat:</p>
        <code>[p2p_chat]</code>

        <h2>Sử dụng trong mã PHP</h2>
        <p>Để sử dụng tính năng chat trong một file PHP, bạn có thể dùng hàm sau:</p>
        <pre><code>
if (function_exists('do_shortcode')) {
    echo do_shortcode('[p2p_chat]');
}
        </code></pre>

        <h2>Lưu ý</h2>
        <p>Đảm bảo rằng người dùng đã đăng nhập để sử dụng tính năng chat. Nếu người dùng không đăng nhập, một thông báo sẽ hiển thị yêu cầu họ đăng nhập.</p>
    </div>
    <?php
}
