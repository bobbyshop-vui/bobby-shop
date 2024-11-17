<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Reminders_Details' ) ) :

	class CR_Reminders_Details {

		private $id = -1;
		private $backlink = '';
		private $not_available_lbl = '';

		public function __construct( $reminder_id, $menu_slug ) {
			$this->id = intval( $reminder_id );
			$this->backlink = admin_url( sprintf( 'admin.php?page=%s&tab=sent', $menu_slug ) );
			$this->not_available_lbl = __( 'Not available', 'customer-reviews-woocommerce' );
		}

		public function display() {
			// initial values for variables used in the html template
			$reminder_id = $this->id;
			$ext_id = '';
			$backlink = $this->backlink;
			$order_id = '';
			$order_created = '';
			$order_paid = '';
			$order_completed = '';
			$customer_name = '';
			$customer_email = '';
			$reminder_created = '';
			$reminder_sent = '';
			$reminder_opened = '';
			$form_opened = '';
			$reminder_status = '';
			$reminder_error = '';
			$reminder_cancelation = '';
			$reminder_verification = '';
			$reminder_channel = '';
			$reminder_type = '';
			$reminder_language = '';
			//
			$log = new CR_Reminders_Log();
			$reminder = $log->get_details( $this->id );
			if ( $reminder ) {
				//
				$date_format = get_option( 'date_format', 'F j, Y' );
				$time_format = 'H:i:s (T)';
				$datetime_format = $date_format . ' ' . $time_format;
				//
				$order_id = $reminder['orderId'];
				$ext_id = $reminder['extId'];
				if ( $reminder['customerName'] ) {
					$customer_name = $reminder['customerName'];
				}
				if ( $reminder['customerEmail'] ) {
					$customer_email = '<a href="mailto:' . esc_url( $reminder['customerEmail'] ) . '">' . esc_html( $reminder['customerEmail'] ) . '</a>';
				}
				$reminder_created = get_date_from_gmt( $reminder['dateCreated'], $datetime_format );
				$reminder_sent = get_date_from_gmt( $reminder['dateSent'], $datetime_format );
				if ( $reminder['dateEmailOpened'] ) {
					$reminder_opened = get_date_from_gmt( $reminder['dateEmailOpened'], $datetime_format );
				}
				if ( $reminder['dateFormOpened'] ) {
					$form_opened = get_date_from_gmt( $reminder['dateFormOpened'], $datetime_format );
					if ( ! $reminder_opened ) {
						$reminder_opened = $form_opened;
					}
				}
				$reminder_status_code = $reminder['status'];
				$reminder_status = CR_Reminders_Log::get_status_description( $reminder['status'] );
				if ( in_array( $reminder['status'], array( 'error' ) ) ) {
					$reminder_info = json_decode( $reminder['reminder'] );
					if ( $reminder_info ) {
						$reminder_error = $reminder_info->errorDetails;
					}
				}
				if ( in_array( $reminder['status'], array( 'canceled' ) ) ) {
					$cancelation_info = json_decode( $reminder['reminder'] );
					if ( $cancelation_info ) {
						$reminder_cancelation = $cancelation_info->errorDetails;
					}
				}
				$reminder_verification = CR_Reminders_Log::get_verification_description( $reminder['verification'] );
				$reminder_channel = CR_Reminders_Log::get_channel_description( $reminder['channel'] );
				$reminder_type = CR_Reminders_Log::get_type_description( $reminder['type'] );
				if ( $reminder['language'] ) {
					$reminder_language = $reminder['language'];
				}
				//
				if ( ! $reminder_opened ) {
					$reminder_opened = __( 'Not opened yet', 'customer-reviews-woocommerce' );
				}
				if ( ! $form_opened ) {
					$form_opened = __( 'Not opened yet', 'customer-reviews-woocommerce' );
				}
				if ( ! $ext_id ) {
					$ext_id = $this->not_available_lbl;
				}
				if ( ! $customer_name ) {
					$customer_name = $this->not_available_lbl;
				}
				if ( ! $customer_email ) {
					$customer_email = $this->not_available_lbl;
				}
				if ( ! $customer_email ) {
					$customer_email = $this->not_available_lbl;
				}
				if ( ! $reminder_verification ) {
					$reminder_verification = $this->not_available_lbl;
				}
				if ( ! $reminder_language ) {
					$reminder_language = $this->not_available_lbl;
				}
				//
				$order = wc_get_order( $reminder['orderId'] );
				if ( $order ) {
					$order_id = $order->get_order_number();
					$order_created = get_date_from_gmt( $order->get_date_created(), $datetime_format );
					$order_paid = get_date_from_gmt( $order->get_date_paid(), $datetime_format );
					$order_completed = get_date_from_gmt( $order->get_date_completed(), $datetime_format );
				}
			}
			include plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'templates/reminder-details-admin-page.php';
			exit;
		}

	}

endif;
