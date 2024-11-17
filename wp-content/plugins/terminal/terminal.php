<?php
/*
Plugin Name: WP Terminal
Description: A terminal interface in WordPress using AJAX and PHP
Version: 1.0
Author: Your Name
*/

// Đảm bảo plugin chỉ chạy khi được kích hoạt
if (!defined('ABSPATH')) {
    exit;
}

// Thêm menu vào Admin Dashboard
function wp_terminal_menu() {
    add_menu_page(
        'WP Terminal', 
        'WP Terminal', 
        'manage_options', 
        'wp-terminal', 
        'wp_terminal_page', 
        'dashicons-editor-code', 
        99
    );
}
add_action('admin_menu', 'wp_terminal_menu');

// Hàm hiển thị giao diện Terminal
function wp_terminal_page() {
    ?>
    <div class="wrap">
        <h1>WP Terminal</h1>
        <div id="terminal"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/xterm@4.15.0/lib/xterm.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@4.15.0/css/xterm.css" />

    <script type="text/javascript">
        var terminal = new Terminal({
            cols: 100,
            rows: 30,
            convertEol: true,
            theme: {
                background: '#000000',
                foreground: '#00FF00'
            }
        });

        terminal.open(document.getElementById('terminal'));
        terminal.writeln("Welcome to WP Terminal!");
        terminal.prompt = '> ';

        // Đặt con trỏ chuột trong terminal
        terminal.onData(function(data) {
            // Gửi lệnh khi người dùng nhấn Enter
            if (data.charCodeAt(0) === 13) { // Kiểm tra dấu Enter
                var command = terminal.buffer.active.getLine(terminal.buffer.active.cursorY).translateToString();
                runCommand(command);
                terminal.write('\r\n' + terminal.prompt);
            } else {
                terminal.write(data); // Xử lý dữ liệu khi gõ vào terminal
            }
        });

        function runCommand(command) {
            // Gửi lệnh tới server bằng fetch (AJAX)
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'wp_terminal_execute',  // Tên action của AJAX
                    command: command,  // Lệnh cần gửi
                })
            })
            .then(response => response.text())  // Nhận kết quả từ server
            .then(data => {
                terminal.writeln(data);  // Hiển thị kết quả lên terminal
                terminal.write('\r\n' + terminal.prompt);  // Đặt con trỏ sau khi nhận kết quả
            })
            .catch(error => {
                terminal.writeln("Error: " + error);
                terminal.write('\r\n' + terminal.prompt);  // Đặt con trỏ sau khi có lỗi
            });
        }
    </script>
    <?php
}

// Xử lý lệnh và thực thi trên server
function wp_terminal_execute() {
    // Kiểm tra quyền truy cập của người dùng
    if (!current_user_can('manage_options')) {
        echo 'You do not have permission to execute commands.';
        wp_die();
    }

    // Lấy lệnh từ frontend
    $command = sanitize_text_field($_POST['command']);

    // Thực thi lệnh và trả kết quả
    $output = exec($command);
    if ($output === null) {
        echo "Error: Command execution failed.";
    } else {
        echo $output;
    }

    wp_die();  // Đảm bảo không có đầu ra thừa
}

add_action('wp_ajax_wp_terminal_execute', 'wp_terminal_execute');  // Xử lý AJAX
?>
