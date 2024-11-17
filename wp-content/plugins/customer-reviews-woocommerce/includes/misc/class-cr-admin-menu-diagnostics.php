<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Diagnostics_Admin_Menu' ) ):

	class CR_Diagnostics_Admin_Menu {

		/**
		* @var string URL to admin diagnostics page
		*/
		protected $page_url;

		/**
		* @var string The slug identifying this menu
		*/
		protected $menu_slug;

		/**
		* @var array The settings to display
		*/
		protected $settings;

		/**
		* @var array The array of deleted reviews
		*/
		private $deleted_reviews;

		public function __construct() {
			$this->menu_slug = 'cr-reviews-diagnostics';
			$this->deleted_reviews = null;

			$this->page_url = add_query_arg( array(
				'page' => $this->menu_slug
			), admin_url( 'admin.php' ) );

			$this->settings = array(
				array(
					'title' => __( 'Diagnostics Information', 'customer-reviews-woocommerce' ),
					'type' => 'title',
					'desc' => __( 'Diagnostic report about parameters of your website configuration that are important for Customer Reviews plugin. If there are any errors or warnings below, the plugin might not work properly.', 'customer-reviews-woocommerce' ),
					'id' => 'cr_diagnostics_title'
				),
				array(
					'name' => __( 'Diagnostics Information', 'customer-reviews-woocommerce' ),
					'type' => 'crdiag',
					'desc' => '',
					'id'   => 'cr_diagnostics_info'
				),
				array(
					'type' => 'sectionend',
					'id'   => 'cr_diagnostics_title'
				)
			);

			if( $this->is_this_page() ) {
				$this->get_deleted_reviews();
				if( $this->deleted_reviews && is_array( $this->deleted_reviews ) && 0 < count( $this->deleted_reviews ) ) {
					$this->settings[] = array(
						'title' => __( 'Hidden Reviews', 'customer-reviews-woocommerce' ),
						'type' => 'title',
						'desc' => __( 'Reviews that are hidden because products were deleted in WooCommerce.', 'customer-reviews-woocommerce' ),
						'id' => 'cr_diagnostics_delprod_title'
					);
					$this->settings[] = array(
						'name' => 'List of Hidden Reviews',
						'type' => 'crdiagdelprod',
						'desc' => '',
						'id'   => 'cr_diagnostics_delprod'
					);
					$this->settings[] = array(
						'type' => 'sectionend',
						'id'   => 'cr_diagnostics_delprod_title'
					);
				}
			}

			add_action( 'admin_enqueue_scripts', array( $this, 'include_scripts' ), 11 );
			add_action( 'admin_menu', array( $this, 'register_diagnostics_menu' ), 11 );
			add_action( 'woocommerce_admin_field_crdiag', array( $this, 'show_report' ) );
			add_action( 'woocommerce_admin_field_crdiagdelprod', array( $this, 'show_delprod' ) );
			add_action( 'wp_ajax_cr_check_duplicate_site_url', array( $this, 'cr_check_duplicate_site_url' ) );
		}

		public function register_diagnostics_menu() {
			add_submenu_page(
				'cr-reviews',
				__( 'Diagnostics', 'customer-reviews-woocommerce' ),
				__( 'Diagnostics', 'customer-reviews-woocommerce' ),
				'manage_options',
				$this->menu_slug,
				array( $this, 'display_diagnostics_admin_page' )
			);
		}

		public function display_diagnostics_admin_page() {
			echo '<div class="wrap">';
			echo '<h1 class="wp-heading-inline">' . esc_html( get_admin_page_title() ) . '</h1>';
			WC_Admin_Settings::output_fields( $this->settings );
			echo '</div>';
		}

		public function show_report( $value ) {
			$curl_version =  curl_version();
			$curl_version =   $curl_version["version"];

			$verified_reviews = get_option( 'ivole_verified_reviews', 'no' );

			if( 'yes' === $verified_reviews ) {
				// check the possibility to post reviews
				$test_secret_key = bin2hex( openssl_random_pseudo_bytes( 16 ) );

				update_option( 'ivole_test_secret_key', $test_secret_key, false );

				$test_data = array( 'test' => $test_secret_key );
				$body_data = json_encode( $test_data );

				$post_response = wp_safe_remote_post( get_rest_url( null, '/ivole/v1/review' ), array(
					'timeout'     => 10,
					'user-agent'  => 'CRivole',
					'httpversion' => '1.1',
					'body'        => $body_data
				) );

				$rest_API = false;
				$rest_error = '';
				if ( ! is_wp_error( $post_response ) && $post_response['response']['code'] === 200 ) {
					$rest_API = true;
				} else {
					if( ! is_wp_error( $post_response ) ) {
						if( array_key_exists( 'response', $post_response ) ) {
							if( array_key_exists( 'code', $post_response['response'] ) ) {
								$rest_error = __( 'Error code: ', 'customer-reviews-woocommerce' ) . $post_response['response']['code'];
							}
							if( array_key_exists( 'message', $post_response['response'] ) ) {
								if( strlen( $rest_error ) > 0 ) {
									$rest_error = $rest_error . '; ';
								}
								$rest_error = $rest_error . __( 'Error message: ', 'customer-reviews-woocommerce' ) . $post_response['response']['message'];
							}
						}
						if( array_key_exists( 'body', $post_response ) ) {
							if( strlen( $rest_error ) > 0 ) {
								$rest_error = $rest_error . '; ';
							}
							$rest_error = $rest_error . __( 'Details: ', 'customer-reviews-woocommerce' ) . $post_response['body'];
						}
						if( strlen( $rest_error ) > 0 ) {
							$rest_error = ' ' . $rest_error . '.';
						}
					} else {
						$rest_error = __( ' Error message: ', 'customer-reviews-woocommerce' ) . $post_response->get_error_message();
					}
				}
			}

			$is_diplicate_site = ivole_is_duplicate_site();
			?>
			<table class="wc_status_table widefat" cellspacing="0" id="status">
				<tbody>
					<tr>
						<td data-export-label="WP Version"><?php _e( 'WP Version:', 'customer-reviews-woocommerce' ); ?></td>
						<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'The version of WordPress installed on your site.', 'customer-reviews-woocommerce' ) ); ?></td>
						<td><?php echo get_bloginfo( 'version' ); ?></td>
					</tr>
					<tr>
						<td data-export-label="WC Version"><?php _e( 'WC Version:', 'customer-reviews-woocommerce' ); ?></td>
						<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'The version of WooCommerce installed on your site.', 'customer-reviews-woocommerce' ) ); ?></td>
						<td><?php echo $this->get_woo_version_number(); ?></td>
					</tr>
					<tr>
						<td data-export-label="cURL Version"><?php _e( 'cURL Version:', 'customer-reviews-woocommerce' ); ?></td>
						<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'The version of cURL library installed on your site.', 'customer-reviews-woocommerce' ) ); ?></td>
						<td><?php echo $curl_version; ?></td>
					</tr>
					<tr>
						<td data-export-label="WP Cron"><?php _e( 'WP Cron:', 'customer-reviews-woocommerce' ); ?></td>
						<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Displays whether or not WP Cron Jobs are enabled.', 'customer-reviews-woocommerce' ) ); ?></td>
						<td><?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) : ?>
							<mark class="no">&ndash;</mark>
						<?php else : ?>
							<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
						<?php endif; ?>
						</td>
					</tr>
					<?php if( 'yes' === $verified_reviews ) : ?>
						<tr>
							<td data-export-label="WP REST API"><?php _e( 'WP REST API:', 'customer-reviews-woocommerce' ); ?></td>
							<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Displays whether or not WP REST API is enabled.', 'customer-reviews-woocommerce' ) ); ?></td>
							<td><?php
							if ( $rest_API ) {
								echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
							} else {
								/* translators: please keep '%s' before 'support article' */
								echo '<mark class="error"><span class="dashicons dashicons-warning"></span>' .  sprintf( __( 'The plugin will not be able to receive reviews because REST API is disabled. Additional information can be found in this %ssupport article', 'customer-reviews-woocommerce' ), '<a href="https://cusrev.freshdesk.com/support/solutions/articles/43000054875-plugin-not-able-to-receive-reviews-because-wp-rest-api-is-disabled">' ) . '</a>.' . $rest_error . '</mark>';
								}
								?>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<td data-export-label="WC Slider"><?php _e( 'WC Slider:', 'customer-reviews-woocommerce' ); ?></td>
						<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Displays whether or not WooCommerce Slider library is enabled.', 'customer-reviews-woocommerce' ) . ' ' . __( 'This warning can be safely ignored, if your theme does not use the offical WooCommerce libraries.', 'customer-reviews-woocommerce' ) ); ?></td>
						<td><?php if ( current_theme_supports( 'wc-product-gallery-slider' ) ) : ?>
							<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
						<?php else :
							echo '<mark class="error"><span class="dashicons dashicons-warning"></span>' . '</mark>';
						endif; ?>
					</tr>
					<tr>
						<td data-export-label="WC Zoom"><?php _e( 'WC Zoom:', 'customer-reviews-woocommerce' ); ?></td>
						<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Displays whether or not WooCommerce Zoom library is enabled.', 'customer-reviews-woocommerce' ) . ' ' . __( 'This warning can be safely ignored, if your theme does not use the offical WooCommerce libraries.', 'customer-reviews-woocommerce' ) ); ?></td>
						<td><?php if ( current_theme_supports( 'wc-product-gallery-zoom' ) ) : ?>
							<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
						<?php else :
							echo '<mark class="error"><span class="dashicons dashicons-warning"></span>' . '</mark>';
						endif; ?>
					</tr>
					<tr>
						<td data-export-label="WC Lightbox"><?php _e( 'WC Lightbox:', 'customer-reviews-woocommerce' ); ?></td>
						<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Displays whether or not WooCommerce Lightbox library is enabled.', 'customer-reviews-woocommerce' ) . ' ' . __( 'This warning can be safely ignored, if your theme does not use the offical WooCommerce libraries.', 'customer-reviews-woocommerce' ) ); ?></td>
						<td><?php if ( current_theme_supports( 'wc-product-gallery-lightbox' ) ) : ?>
							<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
						<?php else :
							echo '<mark class="error"><span class="dashicons dashicons-warning"></span>' . '</mark>';
						endif; ?>
					</tr>
					<tr>
						<td data-export-label="XMLWriter"><?php _e( 'XMLWriter:', 'customer-reviews-woocommerce' ); ?></td>
						<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Displays whether or not XMLWriter extension is enabled. This extension is required to generate XML feeds with products and reviews. If the extension is disabled, please reach out to your hosting support and ask them to enable it.', 'customer-reviews-woocommerce' ) ); ?></td>
						<td><?php if ( class_exists( 'XMLWriter' ) ) : ?>
							<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
						<?php else :
							echo '<mark class="error"><span class="dashicons dashicons-warning"></span>' . '</mark>';
						endif; ?>
					</tr>
					<tr>
						<td data-export-label="Duplicate Site"><?php _e( 'Duplicate Site (disable automatic review reminders):', 'customer-reviews-woocommerce' ); ?></td>
						<?php if ( $is_diplicate_site ) : ?>
							<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'It looks like this site has moved or is a duplicate site. The plugin has disabled sending automatic review reminder emails on this site to prevent duplicate reminders from a staging or test environment.', 'customer-reviews-woocommerce' ) ); ?></td>
						<?php else: ?>
							<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'The plugin checks if this site has moved or is a duplicate site. If the check is positive, the plugin will disable sending automatic review reminder emails to prevent duplicate reminders from a staging or test environment.', 'customer-reviews-woocommerce' ) ); ?></td>
						<?php endif; ?>
						<td>
							<?php if ( $is_diplicate_site ) : ?>
								<span><?php _e( 'Yes', 'customer-reviews-woocommerce' ); ?></span>
								<button id="cr_check_duplicate_site_url" class="button button-primary" style="margin-left:30px" data-nonce="<?php echo wp_create_nonce( 'cr-not-duplicate' ); ?>"><?php _e( 'This is not a duplicate site', 'customer-reviews-woocommerce' ); ?></button>
								<span class="spinner" style="float:none"></span>
							<?php
							else :
								_e( 'No', 'customer-reviews-woocommerce' );
							endif; ?>
						</td>
					</tr>
					<tr>
						<td data-export-label="WooCommerce Reviews"><?php _e( 'WooCommerce Reviews:', 'customer-reviews-woocommerce' ); ?></td>
						<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'The plugin is based on the standard WooCommerce product reviews. Please make sure they are enabled in WooCommerce settings (go to WooCommerce > Settings, then click on the \'Products\' tab).', 'customer-reviews-woocommerce' ) ); ?></td>
						<td>
							<?php if ( 'yes' === get_option( 'woocommerce_enable_reviews' ) ) : ?>
								<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
							<?php
							else :
							?>
								<mark class="error"><span class="dashicons dashicons-warning"></span>
									<?php _e( 'Product ratings will not be diplayed because WooCommerce product reviews are disabled.', 'customer-reviews-woocommerce' ); ?>
								</mark>
							<?php
							endif;
							?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		}

		public function show_delprod( $value ) {
			?>
			<table class="wc_status_table widefat" cellspacing="0" id="delprod">
				<thead>
					<tr>
						<th><?php _e( 'Product', 'customer-reviews-woocommerce' ); ?></th>
						<th><?php _e( 'Review(s)', 'customer-reviews-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
						$prev_prod_id = -1;
						$first_line = true;
						foreach( $this->deleted_reviews as $review ) {
							if( $prev_prod_id !== $review['ID'] ) {
								if( !$first_line ) {
									echo '</td></tr>';
								} else {
									$first_line = false;
								}
								echo '<tr>';
								echo '<td><a href="' . get_edit_post_link( $review['ID'] ) . '">' . esc_html( $review['post_title'] ) . '</a><br>ID: ' . esc_html( $review['ID'] ) . '</td>';
								echo '<td><a href="' . get_edit_comment_link( $review['comment_ID'] ) . '">' . esc_html( $review['comment_ID'] ) . '</a>';
							} else {
								echo ', ' . '<a href="' . get_edit_comment_link( $review['comment_ID'] ) . '">' . esc_html( $review['comment_ID'] ) . '</a>';
							}
							$prev_prod_id = $review['ID'];
						}
						if( -1 !== $prev_prod_id ) {
							echo '</td></tr>';
						}
					?>
				</tbody>
			</table>
			<?php
		}

		private function get_woo_version_number() {
			// If get_plugins() isn't available, require it
			if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			// Create the plugins folder and file variables
			$plugin_folder = get_plugins( '/' . 'woocommerce' );
			$plugin_file = 'woocommerce.php';

			// If the plugin version number is set, return it
			if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
				return $plugin_folder[$plugin_file]['Version'];

			} else {
				// Otherwise return null
				return NULL;
			}
		}

		public function cr_check_duplicate_site_url() {
			if( check_ajax_referer( 'cr-not-duplicate', 'security', false ) ) {
				ivole_set_duplicate_site_url_lock();
				$is_duplicate = ivole_is_duplicate_site();
				$response = array(
					'result' => $is_duplicate ? __( 'Yes', 'customer-reviews-woocommerce' ) : __( 'No', 'customer-reviews-woocommerce' ),
					'is_duplicate' => $is_duplicate,
				);
				wp_send_json( $response, 200 );
			} else {
				wp_send_json( '', 403 );
			}
		}

		public function include_scripts() {
			if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === $this->menu_slug ) {
				wp_enqueue_script( 'cr-admin-settings', plugins_url('js/admin-settings.js', dirname( dirname( __FILE__ ) ) ), array(), Ivole::CR_VERSION, false );
			}
		}

		public function get_deleted_reviews() {
			global $wpdb;
			$deleted_reviews = $wpdb->get_results(
					"
				SELECT p.ID, c.comment_ID, p.post_title FROM $wpdb->commentmeta m
				LEFT JOIN $wpdb->comments c ON m.comment_id = c.comment_ID
				LEFT JOIN $wpdb->posts p ON c.comment_post_ID = p.ID
				WHERE m.meta_key = 'rating'
				AND p.post_status = 'trash'
				AND m.meta_value > 0
				ORDER BY p.ID
					",
				ARRAY_A
			);
			$this->deleted_reviews = $deleted_reviews;
		}

		public function is_this_page() {
			return ( isset( $_GET['page'] ) && $_GET['page'] === $this->menu_slug );
		}
	}

endif;
