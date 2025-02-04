<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED Shortcode: mycred_send
 * This shortcode allows the current user to send a pre-set amount of points
 * to a pre-set user. A simpler version of the mycred_transfer shortcode.
 * @see http://codex.mycred.me/shortcodes/mycred_send/ 
 * @since 1.1
 * @version 1.3.1
 */
if ( ! function_exists( 'mycred_render_shortcode_send' ) ) :

function mycred_render_shortcode_send( $atts, $content = '' ) {

    // Ensure the user is logged in
    if ( ! is_user_logged_in() ) return;

    // Define and extract shortcode attributes with defaults
    extract( shortcode_atts( array(
        'amount' => 0, // Points to send
        'to'     => '', // Recipient's user ID
        'log'    => '', // Log description
        'ref'    => 'gift', // Reference type
        'type'   => MYCRED_DEFAULT_TYPE_KEY, // Point type
        'class'  => 'button button-primary btn btn-primary', // CSS classes
        'reload' => 0 // Reload page after transfer
    ), $atts, MYCRED_SLUG . '_send' ) );

    // Check if the specified point type exists
    if ( ! mycred_point_type_exists( $type ) ) return 'Point type not found.';

    global $post;

    // Resolve recipient's user ID
    $to = mycred_get_user_id( $to );

    // Prevent sending points to self or invalid users
    $user_id = get_current_user_id();
    $recipient = absint( $to );
    if ( $recipient === $user_id || $recipient === 0 ) return;

    // Get the point type object
    global $mycred_sending_points;
    $mycred_sending_points = false;
    $mycred = mycred( $type );

   
    // Exclude users who are not part of the point system
    if ( $mycred->exclude_user( $recipient ) || $mycred->exclude_user( $user_id ) ) return;

    // Validate user's balance and account limits
    $account_limit = $mycred->number( apply_filters( 'mycred_transfer_acc_limit', 0 ) );
    $balance = $mycred->get_users_balance( $user_id, $type );


    if ( $balance - $amount < $account_limit ) return; // Insufficient funds

    // Mark the transfer as ready
    $mycred_sending_points = true;

    // Sanitize the button class
    if ( $class != '' )
        $class = ' ' . sanitize_text_field( $class );

    $reload = absint( $reload );


    // Render the transfer button
   $render = '<button type="button" class="mycred-send-points-button btn btn-primary' . $class . '" 
    data-reload="' . $reload . '" 
    data-to="' . $recipient . '" 
    data-ref="' . esc_attr( $ref ) . '" 
    data-log="' . esc_attr( $log ) . '" 
    data-amount="' . esc_attr($amount) . '" 
    data-type="' . esc_attr( $type ) . '" 
    data-post-id="' . get_the_ID() . '">' . $mycred->template_tags_general( $content ) . '</button>';

    // Allow customization via filters
    return apply_filters( 'mycred_send', $render, $atts, $content );
}
endif;

add_shortcode( MYCRED_SLUG . '_send', 'mycred_render_shortcode_send' );

function mycred_send_encrypt_amount($amount, $key) {
    $cipher = "aes-256-cbc";
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
    $encrypted = openssl_encrypt($amount, $cipher, $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function mycred_send_decrypt_amount($encrypted_data, $key) {
    $cipher = "aes-256-cbc";
    $data = base64_decode($encrypted_data);
    $iv_length = openssl_cipher_iv_length($cipher);
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
}


/**
 * myCRED Send Points Ajax
 * @since 0.1
 * @version 1.4.1
 */
if ( ! function_exists( 'mycred_shortcode_send_points_ajax' ) ) :
    function mycred_shortcode_send_points_ajax() {
    check_ajax_referer('mycred-send-points', 'token');
    
    $user_id = get_current_user_id();
    
    if (mycred_force_singular_session($user_id, 'mycred-last-send')) {
        wp_send_json(['status' => 'error', 'message' => 'Multiple requests detected.']);
    }

    $point_type = MYCRED_DEFAULT_TYPE_KEY;
    if (isset($_POST['type'])) {
        $point_type = sanitize_text_field(wp_unslash($_POST['type']));
    }

    if (!mycred_point_type_exists($point_type)) {
        wp_send_json(['status' => 'error', 'message' => 'Invalid point type.']);
    }

    $recipient = isset($_POST['recipient']) ? absint($_POST['recipient']) : 0;
    if ($recipient === 0 || $user_id === $recipient) {
        wp_send_json(['status' => 'error', 'message' => 'Invalid recipient.']);
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    if ($post_id <= 0) {
        wp_send_json(['status' => 'error', 'message' => 'Invalid post ID.']);
    }

    $post_author = get_post_field('post_author', $post_id);
    if ($post_author != $recipient) {
        wp_send_json(['status' => 'error', 'message' => 'Invalid recipient.']);
    }

    $amount_encrypted = isset($_POST['amount']) ? sanitize_text_field(wp_unslash($_POST['amount'])) : '';
    $amount = mycred_send_decrypt_amount($amount_encrypted, '94jCwvMyi9xe3knklOyysUeNeML5szTXsCVRpk8wA9b48Jd5XM6jSJFrwGBTk6WF');

    if ($amount === false || $amount <= 0) {
        wp_send_json(['status' => 'error', 'message' => 'Invalid or manipulated amount.']);
    }

    $mycred = mycred($point_type);
    $amount = $mycred->number($amount);

    $account_limit = $mycred->number(apply_filters('mycred_transfer_acc_limit', $mycred->zero()));
    $balance = $mycred->get_users_balance($user_id, $point_type);
    $new_balance = $balance - $amount;

    if ($new_balance < $account_limit) {
        wp_send_json(['status' => 'error', 'message' => 'Insufficient funds.']);
    }

    $reference = isset($_POST['reference']) ? sanitize_text_field(wp_unslash($_POST['reference'])) : '';
    $log_entry = isset($_POST['log']) ? sanitize_text_field(wp_unslash($_POST['log'])) : '';

    $data = ['ref_type' => 'user'];

    if ($mycred->add_creds($reference, $user_id, -$amount, $log_entry, $recipient, $data, $point_type)) {
        $mycred->add_creds($reference, $recipient, $amount, $log_entry, $user_id, $data, $point_type);
        wp_send_json(['status' => 'success', 'message' => 'Points transferred successfully.']);
    } else {
        wp_send_json(['status' => 'error', 'message' => 'Point transfer failed.']);
    }
}
endif;
add_action( 'wp_ajax_mycred-send-points', 'mycred_shortcode_send_points_ajax' );

function mycred_encrypt_amount_ajax() {
    check_ajax_referer('mycred-send-points', 'token');

    $amount = isset($_POST['amount']) ? sanitize_text_field($_POST['amount']) : null;
    if (!$amount || !is_numeric($amount)) {
        wp_send_json(['status' => 'error', 'message' => 'Invalid amount.']);
    }

    $key = '94jCwvMyi9xe3knklOyysUeNeML5szTXsCVRpk8wA9b48Jd5XM6jSJFrwGBTk6WF'; // secure key
    $encrypted_amount = mycred_send_encrypt_amount($amount, $key);

    if ($encrypted_amount) {
        wp_send_json(['status' => 'success', 'encrypted_amount' => $encrypted_amount]);
    } else {
        wp_send_json(['status' => 'error', 'message' => 'Failed to encrypt amount.']);
    }
}
add_action('wp_ajax_mycred-encrypt-amount', 'mycred_encrypt_amount_ajax');



