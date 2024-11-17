<?php

if ( ! defined( 'ABSPATH' ) ) {
		exit;
}

if ( ! class_exists( 'CR_Reminders_Admin_Menu' ) ):

/**
 * Reminders admin menu class
 *
 * @since 3.5
 */
class CR_Reminders_Admin_Menu {

	/**
		 * @var string The slug identifying this menu
		 */
		protected $menu_slug;

		/**
		 * Constructor
		 *
		 * @since 3.5
		 */
		public function __construct() {
			$this->menu_slug = 'cr-reviews-reminders';

			add_action( 'admin_menu', array( $this, 'register_reminders_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'include_scripts' ) );
			add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );
			add_action( 'wp_ajax_cr_get_reminders_top_row_stats', array( $this, 'get_reminders_top_row_stats' ) );
		}

		/**
		 * Register the reminders submenu
		 *
		 * @since 3.5
		 */
		public function register_reminders_menu() {
			$capability = 'manage_options';
			if (
				! current_user_can( 'manage_options' ) &&
				current_user_can( 'manage_woocommerce' )
			) {
				$capability = 'manage_woocommerce';
			}
			$submenu = add_submenu_page(
				'cr-reviews',
				__( 'Reminders', 'customer-reviews-woocommerce' ),
				__( 'Reminders', 'customer-reviews-woocommerce' ),
				$capability,
				$this->menu_slug,
				array( $this, 'display_reminders_admin_page' )
			);
			if ( $submenu ) {
				add_action( "load-$submenu", array( $this, 'display_screen_options' ) );
			}
	}

	/**
	 * Handles bulk and per-reminder actions.
	 *
	 * @since 3.5
	 *
	 * @param string $action The action to process
	 */
	protected function process_actions( $list_table ) {
		$action = $list_table->current_action();

		$orders = array();
		$reminders = array();

		switch ( $action ) {
			case 'cancel':
			case 'send':
				// Bulk actions
				check_admin_referer( 'bulk-reminders' );

				$orders = ( isset( $_GET['orders'] ) && is_array( $_GET['orders'] ) ) ? $_GET['orders'] : array();
				$reminder_types = ( isset( $_GET['types'] ) && is_array( $_GET['types'] ) ) ? $_GET['types'] : array();
				$orders = array_map(
					function( $order, $type ) {
						return array(
							intval( $order ),
							intval( $type )
						);
					},
					$orders,
					$reminder_types
				);
				break;
			case 'cancelreminder':
			case 'sendreminder':
				// Single-reminder actions
				check_admin_referer( 'manage-reminders' );

				$order_id = ( isset( $_GET['order_id'] ) ) ? intval( $_GET['order_id'] ) : 0;
				$reminder_type = ( isset( $_GET['type'] ) ) ? intval( $_GET['type'] ) : 1;

				if ( $order_id ) {
					$orders[] = array(
						$order_id,
						$reminder_type
					);
				}
		}

		$cancelled = 0;
		$sent = 0;
		$verification = '';
		foreach ( $orders as $order ) {
			$cron_arg = $order[1] > 1 ? array( $order[0], $order[1] ) : array( $order[0] );
			switch ( $action ) {
				case 'cancel':
				case 'cancelreminder':
					wp_clear_scheduled_hook( 'ivole_send_reminder', $cron_arg );
					// logging
					$ord = wc_get_order( $order[0] );
					if ( ! $verification ) {
						$mailer = get_option( 'ivole_mailer_review_reminder', 'cr' );
						$verification = ( 'wp' === $mailer ) ? 'local' : 'verified';
					}
					$log = new CR_Reminders_Log();
					$l_result = $log->add(
						$order[0],
						apply_filters( 'cr_reminders_table_type_log', 'a', $order[1] ),
						'email',
						array(
							200,
							__( 'Review reminder was canceled by a manual action', 'customer-reviews-woocommerce' ),
							array(
								'data' => array(
									'email' => array(
										'to' => Ivole_Email::get_customer_email( $ord )
									),
									'customer' => array(
										'firstname' => $ord->get_billing_first_name(),
										'lastname' => $ord->get_billing_last_name()
									),
									'verification' => $verification,
									'language' => Ivole_Email::fetch_language_trnsl( $order[0], $ord )
								)
							)
						)
					);
					// end of logging
					$cancelled++;
					break;
				case 'send':
				case 'sendreminder':
					wp_clear_scheduled_hook( 'ivole_send_reminder', $cron_arg );
					wp_schedule_single_event( 1, 'ivole_send_reminder', $cron_arg );
					$sent++;
					break;
				default:
					break;
			}
		}

		if ( 0 < count( $reminders ) ) {
			$log = new CR_Reminders_Log();
			$log->delete( $reminders );
		}

		if ( $sent ) {
			wp_cron();
		}

		$redirect_to = remove_query_arg( array( 'reminder' ), wp_get_referer() );
		$redirect_to = add_query_arg( 'paged', $list_table->get_pagenum(), $redirect_to );

		if ( $cancelled ) {
			$redirect_to = add_query_arg( 'cancelled', $cancelled, $redirect_to );
		}

		if ( $sent ) {
			$redirect_to = add_query_arg( 'sent', $sent, $redirect_to );
		}

		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Render the scheduled reminders page
	 *
	 * @since 3.5
	 */
	public function display_reminders_admin_page() {
		// check if a page with reminder details needs to be displayed
		if (
			isset( $_GET['reminder'] ) &&
			$_GET['reminder']
		) {
			$details = new CR_Reminders_Details( $_GET['reminder'], $this->menu_slug );
			$details->display();
		}
		// check which tab is seleted
		if ( isset( $_GET['tab'] ) ) {
			$current_tab = $_GET['tab'];
		} else {
			$current_tab = 'scheduled';
		}
		if ( 'sent' === $current_tab ) {
			$list_table = new CR_Reminders_Log_Table( ['screen' => get_current_screen()] );
		} else {
			$list_table = new CR_Reminders_List_Table( ['screen' => get_current_screen()] );
		}
		$pagenum  = $list_table->get_pagenum();
		$doaction = $list_table->current_action();

		if ( $list_table->current_action() ) {
			$this->process_actions( $list_table );
		} elseif ( ! empty( $_GET['_wp_http_referer'] ) ) {
			wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
			exit;
		}

		$list_table->prepare_items();

		include plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'templates/reminders-admin-page.php';
	}

	public function include_scripts() {
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === $this->menu_slug ) {
			$asset_file = include( plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . '/admin/build/index.asset.php' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_style( 'cr-admin-charts', plugins_url( '/admin/build/index.css', dirname( dirname( __FILE__ ) ) ), array(),  Ivole::CR_VERSION );
			wp_enqueue_script( 'cr-admin-charts', plugins_url( '/admin/build/index.js' , dirname( dirname( __FILE__ ) ) ), $asset_file['dependencies'], Ivole::CR_VERSION, true );
			wp_enqueue_script( 'cr-tiptip', plugins_url( 'js/jquery.tipTip.minified.js' , dirname( dirname( __FILE__ ) ) ), array(), Ivole::CR_VERSION, false );
			wp_enqueue_script( 'cr-admin-settings', plugins_url('js/admin-settings.js', dirname( dirname( __FILE__ ) ) ), array(), Ivole::CR_VERSION, false );
			wp_enqueue_style( 'cr-admin-css', plugins_url('css/admin.css', dirname( dirname( __FILE__ ) ) ), array(), Ivole::CR_VERSION );
		}
	}

	public function display_screen_options() {
		// do not display screen options on a page with reminder details
		if (
			isset( $_GET['reminder'] ) &&
			$_GET['reminder']
		) {
			return;
		}
		$args = array(
			'label' => 'Reminders per page',
			'default' => 20,
			'option' => 'reminders_per_page'
		);
		add_screen_option( 'per_page', $args );
	}

	public function save_screen_options( $screen_option, $option, $value ) {
		if ( 'reminders_per_page' === $option ) {
			if ( $value < 1 || $value > 999 ) {
				return false;
			}
		}
		return $value;
	}

	public function get_reminders_top_row_stats() {
		check_ajax_referer( 'cr-reminders-top-row', 'cr_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die( '-2' );
		}

		// count scheduled review reminders by channels
		$out_channels = array(
			'email' => (object) array(
				'label' => 'Email',
				'count' => 0,
				'part' => 0,
				'class' => 'chartColor1'
			),
			'wa' => (object) array(
				'label' => 'WhatsApp',
				'count' => 0,
				'part' => 0,
				'class' => 'chartColor2'
			)
		);
		$crons = _get_cron_array();
		foreach ( $crons as $timestamp => $hooks ) {
			if ( isset( $hooks['ivole_send_reminder'] ) && $timestamp > 1 ) {
				foreach ( $hooks['ivole_send_reminder'] as $hash => $event ) {
					$ch = 'email';
					if ( isset( $out_channels[$ch] ) ) {
						$out_channels[$ch]->count++;
					}
				}
			}
		}

		// count total scheduled review reminders
		$total_scheduled = 0;
		foreach ( $out_channels as $channel ) {
			$total_scheduled += $channel->count;
		}

		// calculate percentage of different channels
		if ( 0 < $total_scheduled ) {
			foreach ( $out_channels as $channel ) {
				$channel->part = round( $channel->count / $total_scheduled * 100 );
				$channel->count = number_format_i18n( $channel->count );
			}
		}

		// count sent review reminders
		$total_sent = 0;
		$sent_statuses = array(
			'rmd_opened' => (object) array(
				'label' => __( 'Reminder opened', 'customer-reviews-woocommerce' ),
				'count' => 0,
				'part' => 0,
				'class' => 'chartColor1'
			),
			'frm_opened' => (object) array(
				'label' => __( 'Form opened', 'customer-reviews-woocommerce' ),
				'count' => 0,
				'part' => 0,
				'class' => 'chartColor2'
			)
		);
		$sent = CR_Reminders_Log::count_reminders( '' );
		foreach ( $sent as $row ) {
			switch ( $row['status'] ) {
				case 'error':
				case 'sent':
					$total_sent += $row['total'];
					break;
				case 'rmd_opened':
					$sent_statuses[$row['status']]->count += $row['total'];
					$total_sent += $row['total'];
					break;
				case 'frm_opened':
					$sent_statuses[$row['status']]->count = $row['total'];
					$sent_statuses['rmd_opened']->count += $row['total'];
					$total_sent += $row['total'];
					break;
				case 'canceled':
					break;
				default:
					break;
			}
		}

		// calculate percentage of different statuses
		if ( 0 < $total_sent ) {
			foreach ( $sent_statuses as $status ) {
				$status->part = round( $status->count / $total_sent * 100 );
				$status->count = number_format_i18n( $status->count );
			}
		}

		wp_send_json(
			array(
				'scheduled' => number_format_i18n( $total_scheduled, 0 ),
				'channels' => array_values( $out_channels ),
				'sent' => $total_sent,
				'statuses' => array_values( $sent_statuses )
			)
		);
	}

}

endif;
