<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Reviews_Notifications' ) ) :

	class CR_Reviews_Notifications {

		public function __construct() {
			add_filter( 'notify_post_author', array( $this, 'disable_notification' ), 10, 2 );
		}

		public function disable_notification( $maybe_notify, $comment_id ) {
			if ( get_comment_meta( $comment_id, 'ivole_order_locl', true ) ) {
				$maybe_notify = false;
			}
			return $maybe_notify;
		}

	}

endif;
