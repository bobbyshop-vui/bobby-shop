<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'CR_Reminders_Log_Table' ) ) :

	class CR_Reminders_Log_Table extends WP_List_Table {

		private $not_available_lbl = '';

		public function __construct( $args = array() ) {
			$this->not_available_lbl = __( 'Not available', 'customer-reviews-woocommerce' );
			parent::__construct( array(
				'plural'   => 'reminders',
				'singular' => 'reminder',
				'ajax'     => false,
				'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
			) );
		}

		public function prepare_items() {
			global $search, $status;

			$search = ( isset( $_REQUEST['s'] ) ) ? trim( esc_html( wp_unslash( $_REQUEST['s'] ) ) ) : '';
			$status = isset( $_REQUEST['status'] ) ? $_REQUEST['status'] : 'rmd_all';
			if ( ! in_array( $status, array( 'rmd_all', 'rmd_canceled', 'rmd_error', 'rmd_sent', 'rmd_opened', 'frm_opened' ) ) ) {
				$status = 'rmd_all';
			}

			$orderby = ( isset( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'sent';
			$order = ( isset( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc';

			$per_page = $this->get_per_page();
			$page = $this->get_pagenum();
			$start = ( $page - 1 ) * $per_page;

			$log = new CR_Reminders_Log();
			$reminders = $log->get( $start, $per_page, $orderby, $order, $search, $status );

			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable, 'order' );
			$this->items = $reminders['records'];

			$this->set_pagination_args( array(
				'total_items' => $reminders['total'],
				'per_page' => $per_page,
			) );
		}

		public function get_per_page() {
			return $this->get_items_per_page( 'reminders_per_page', 20 );
		}

		/**
		 * Prints the content displayed if there are no reminders.
		 *
		 * @since 3.5
		 */
		public function no_items() {
			if( 'cr' === get_option( 'ivole_scheduler_type' ) ) {
				/* translators: please keep '%1$s' and '%2$s' as is   */
				echo sprintf( __( 'The plugin is configured to use CR Cron for sending review reminders (%1$s\'Reminders Scheduler\' setting%2$s).', 'customer-reviews-woocommerce' ), '<a href="' . admin_url( 'admin.php?page=cr-reviews-settings' ) . '" title="Plugin Settings">', '</a>' );
				echo ' ';
				/* translators: please keep '%1$s' and '%2$s' as is   */
				echo sprintf( __( 'Please log in to your account on %1$sCusRev website%2$s to view and manage the reminders.', 'customer-reviews-woocommerce' ), '<a href="https://www.cusrev.com/login.html" target="_blank" rel="noopener noreferrer">', '</a>' );
			} else {
				_e( 'There are no review reminders to display', 'customer-reviews-woocommerce' );
			}
		}

		protected function get_bulk_actions() {
			$actions = array(
				'delete' => __( 'Delete', 'customer-reviews-woocommerce' )
			);

			return $actions;
		}

		public function get_columns() {
			return array(
				'cb'		=> '<input type="checkbox" />',
				'order'		=> __( 'Order', 'customer-reviews-woocommerce' ),
				'customer'	=> __( 'Customer', 'customer-reviews-woocommerce' ),
				'type'	=> __( 'Type', 'customer-reviews-woocommerce' ),
				'verification' => __( 'Verification', 'customer-reviews-woocommerce' ),
				'sent'	=> __( 'Sent', 'customer-reviews-woocommerce' ),
				'status'	=> __( 'Status', 'customer-reviews-woocommerce' ),
				'language'	=> __( 'Language', 'customer-reviews-woocommerce' ),
				'action'	=> ''
			);
		}

		/**
		 * Returns the columns which are sortable
		 *
		 * @since 3.5
		 *
		 * @return array
		 */
		protected function get_sortable_columns() {
			return array(
				'order'		=> array( 'order', false ),
				'customer'	=> array( 'customer', false ),
				'sent'	=> array( 'sent', false )
			);
		}

		protected function get_default_primary_column_name() {
			return 'order';
		}

		public function display() {
			wp_nonce_field( "fetch-list-" . get_class( $this ), '_ajax_fetch_list_nonce' );

			$this->display_tablenav( 'top' );

			$this->screen->render_screen_reader_content( 'heading_list' );

			?>
			<table class="wp-list-table cr-reminders-log-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
				<thead>
					<tr>
						<?php $this->print_column_headers(); ?>
					</tr>
				</thead>

				<tbody id="the-reminder-list" data-wp-lists="list:reminder">
					<?php $this->display_rows_or_placeholder(); ?>
				</tbody>

				<tfoot>
					<tr>
						<?php $this->print_column_headers( false ); ?>
					</tr>
				</tfoot>

			</table>
			<?php
		}

		public function single_row( $reminder ) {
			echo '<tr id="reminder-' . $reminder['id'] . '" class="reminder-row">';
			$this->single_row_columns( $reminder );
			echo "</tr>\n";
		}

		public function column_cb( $reminder ) {
			?>
				<label class="screen-reader-text" for="cb-select-<?php echo $reminder['id']; ?>"><?php _e( 'Select reminder', 'customer-reviews-woocommerce' ); ?></label>
				<input class="reminder-checkbox" id="cb-select-<?php echo $reminder['id']; ?>" type="checkbox" name="reminders[]" value="<?php echo $reminder['id']; ?>" />
			<?php
		}

		public function column_order( $reminder ) {
			?>
			<a href="<?php echo esc_url( get_edit_post_link( $reminder['orderId'] ) ); ?>"><?php echo $reminder['orderId']; ?></a>
			<?php
		}

		public function column_customer( $reminder ) {
			if ( $reminder['customerName'] || $reminder['customerEmail'] ) :
				?>
				<strong><?php echo $reminder['customerName']; ?></strong>
				<br>
				<a href="<?php echo 'mailto:' . $reminder['customerEmail']; ?>"><?php echo $reminder['customerEmail']; ?></a>
				<?php
			else :
				echo esc_html( $this->not_available_lbl );
			endif;
		}

		public function column_type( $reminder ) {
			$type = CR_Reminders_Log::get_type_description( $reminder['type'] );
			echo esc_html( $type );
		}

		public function column_sent( $reminder ) {
			$local_timestamp = get_date_from_gmt( $reminder['dateSent'], 'Y-M-d H:i:s (T)' );
			echo esc_html( $local_timestamp );
		}

		public function column_status( $reminder ) {
			echo esc_html(
				CR_Reminders_Log::get_status_description( $reminder['status'] )
			);
		}

		public function column_verification( $reminder ) {
			$col = CR_Reminders_Log::get_verification_description( $reminder['verification'] );
			if ( $col ) {
				echo esc_html( $col );
			} else {
				echo esc_html( $this->not_available_lbl );
			}
		}

		public function column_language( $reminder ) {
			if ( $reminder['language'] ) {
				echo esc_html( $reminder['language'] );
			} else {
				echo esc_html( $this->not_available_lbl );
			}
		}

		public function column_action( $reminder ) {
			$actions = '';
			if ( isset( $reminder['id'] ) && $reminder['id'] ) {
				$url = esc_url(
					admin_url(
						sprintf( 'admin.php?page=cr-reviews-reminders&reminder=%d', intval( $reminder['id'] ) )
					)
				);
				$link = '<a href="' . $url . '" aria-label="' . esc_attr__( 'Show details' ) . '" title="' . esc_attr__( 'Show details' ) . '" class="button cr-col-actions-button"><span class="dashicons dashicons-arrow-right-alt"></span></a>';
				$actions = '<div class="cr-col-actions">' . $link . '</div>';
			}
			return $actions;
		}

		protected function get_views() {
			global $status, $search;
			$views = array();
			$num_reminders = $this->count_reminders();
			$statuses = array(
				'rmd_all' => _nx_noop(
					'All <span class="count">(%s)</span>',
					'All <span class="count">(%s)</span>',
					'reminders',
					'customer-reviews-woocommerce'
				),
				'rmd_canceled' => _nx_noop(
					'Canceled <span class="count">(%s)</span>',
					'Canceled <span class="count">(%s)</span>',
					'reminders',
					'customer-reviews-woocommerce'
				),
				'rmd_error' => _nx_noop(
					'Error <span class="count">(%s)</span>',
					'Error <span class="count">(%s)</span>',
					'reminders',
					'customer-reviews-woocommerce'
				),
				'rmd_sent' => _nx_noop(
					'Sent <span class="count">(%s)</span>',
					'Sent <span class="count">(%s)</span>',
					'reminders',
					'customer-reviews-woocommerce'
				),
				'rmd_opened' => _nx_noop(
					'Reminder Opened <span class="count">(%s)</span>',
					'Reminder Opened <span class="count">(%s)</span>',
					'reminders',
					'customer-reviews-woocommerce'
				),
				'frm_opened' => _nx_noop(
					'Form Opened <span class="count">(%s)</span>',
					'Form Opened <span class="count">(%s)</span>',
					'reminders',
					'customer-reviews-woocommerce'
				)
			);

			$link = admin_url( 'admin.php?page=cr-reviews-reminders' );
			$link = add_query_arg( 'tab', 'sent', $link );

			foreach ( $statuses as $status_id => $label ) {
				$current_link_attributes = '';
				if ( $status_id === $status ) {
					$current_link_attributes = ' class="current" aria-current="page"';
				}
				$link = add_query_arg( 'status', $status_id, $link );
				if ( $search ) {
					$link = add_query_arg( 's', $search, $link );
				}
				$label_link = sprintf(
					translate_nooped_plural( $label, $num_reminders->$status_id ),
					sprintf(
						'<span class="%s-count">%s</span>',
						$status_id,
						number_format_i18n( $num_reminders->$status_id )
					)
				);
				$views[ $status_id ] = "<a href='$link'$current_link_attributes>" . $label_link . '</a>';
			}
			return $views;
		}

		protected function count_reminders() {
			global $search;

			$reminders_count = array(
				'rmd_canceled' => 0,
				'rmd_error' => 0,
				'rmd_sent' => 0,
				'rmd_opened' => 0,
				'frm_opened' => 0,
				'rmd_all' => 0
			);

			$totals = CR_Reminders_Log::count_reminders( $search );

			foreach ( $totals as $row ) {
				switch ( $row['status'] ) {
					case 'canceled':
						$reminders_count['rmd_canceled'] = $row['total'];
						$reminders_count['rmd_all'] += $row['total'];
						break;
					case 'error':
						$reminders_count['rmd_error'] = $row['total'];
						$reminders_count['rmd_all'] += $row['total'];
						break;
					case 'sent':
						$reminders_count['rmd_sent'] = $row['total'];
						$reminders_count['rmd_all'] += $row['total'];
						break;
					case 'rmd_opened':
						$reminders_count['rmd_opened'] = $row['total'];
						$reminders_count['rmd_all'] += $row['total'];
						break;
					case 'frm_opened':
						$reminders_count['frm_opened'] = $row['total'];
						$reminders_count['rmd_all'] += $row['total'];
						break;
					default:
						break;
				}
			}

			$count_object = (object) $reminders_count;
			return $count_object;
		}

	}

endif;
