<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Product_Feed_Admin_Menu' ) ):

	class CR_Product_Feed_Admin_Menu {

		/**
		* @var string URL to admin diagnostics page
		*/
		protected $page_url;

		/**
		* @var string The slug identifying this menu
		*/
		protected $menu_slug;

		/**
		* @var string The slug of the currently displayed tab
		*/
		protected $current_tab = 'overview';

		public function __construct() {
			$this->menu_slug = 'cr-reviews-product-feed';

			$this->page_url = add_query_arg( array(
				'page' => $this->menu_slug
			), admin_url( 'admin.php' ) );

			if ( isset( $_GET['tab'] ) ) {
				$this->current_tab = $_GET['tab'];
			}

			add_action( 'admin_init', array( $this, 'save_settings' ) );
			add_action( 'admin_init', array( $this, 'check_cron' ) );
			add_action( 'admin_menu', array( $this, 'register_settings_menu' ), 11 );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_product_feed_css_js' ) );
		}

		public function check_cron() {
			if ( current_user_can( 'manage_options' ) ) {
				// XML Product Feed
				$cron_options = get_option( 'ivole_product_feed_cron', array('started' => false) );
				if( $cron_options['started'] ){
					$offset = ( $cron_options['offset'] < $cron_options['total'] ) ? $cron_options['offset'] : $cron_options['total'];
					/* translators: please keep %1$s, %2$s, and %3$s in the translation - they will be replaced with the counts of products */
					WC_Admin_Settings::add_message( sprintf( __( 'XML Product Feed for Google Shopping is being generated in background - products %1$s to %2$s out of %3$s (reload the page to see the latest progress)', 'customer-reviews-woocommerce' ), $cron_options['current'], $offset, $cron_options['total'] ) );
				}
				// XML Product Review Feed
				$review_cron_options = get_option(
					'ivole_product_reviews_feed_cron',
					array( 'started' => false )
				);
				if( $review_cron_options['started'] ){
					$review_offset = ( $review_cron_options['offset'] < $review_cron_options['total'] ) ? $review_cron_options['offset'] : $review_cron_options['total'];
					WC_Admin_Settings::add_message( sprintf( __( 'XML Product Review Feed for Google Shopping is being generated in background - reviews %1$s to %2$s out of %3$s (reload the page to see the latest progress)', 'customer-reviews-woocommerce' ), $review_cron_options['current'], $review_offset, $review_cron_options['total'] ) );
				}
				// Check that WP Cron is disabled
				if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
					if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'cr-reviews-product-feed' ) {
						if( 'yes' === get_option( 'ivole_product_feed', 'no' ) || 'yes' === get_option( 'ivole_google_generate_xml_feed', 'no' ) ) {
							WC_Admin_Settings::add_message( __( 'XML Feeds might not be created correctly because WP Cron is disabled', 'customer-reviews-woocommerce' ) );
						}
					}
				}
			}
		}

		public function register_settings_menu() {
			add_submenu_page(
				'cr-reviews',
				__( 'Integration with Google Services', 'customer-reviews-woocommerce' ),
				__( 'Google', 'customer-reviews-woocommerce' ),
				'manage_options',
				$this->menu_slug,
				array( $this, 'display_productfeed_admin_page' )
			);
		}

		public function display_productfeed_admin_page() {
			?>
			<div class="wrap ivole-new-settings woocommerce">
				<h1 class="wp-heading-inline" style="margin-bottom:8px;"><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<hr class="wp-header-end">
				<?php
				$tabs = apply_filters( 'cr_productfeed_tabs', array() );

				if ( is_array( $tabs ) && sizeof( $tabs ) > 1 ) {
					echo '<ul class="subsubsub">';

					$array_keys = array_keys( $tabs );
					$last = end( $array_keys );

					foreach ( $tabs as $tab => $label ) {
						echo '<li><a href="' . $this->page_url . '&tab=' . $tab . '" class="' . ( $this->current_tab === $tab ? 'current' : '' ) . '">' . $label . '</a> ' . ( $last === $tab ? '' : '|' ) . ' </li>';
					}

					echo '</ul><br class="clear" />';
				}
				?>
				<form action="" method="post" id="mainform" enctype="multipart/form-data">
					<?php
					WC_Admin_Settings::show_messages();

					do_action( 'cr_productfeed_display_' . $this->current_tab );
					?>
					<p class="submit">
						<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
							<button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
						<?php endif; ?>
						<?php wp_nonce_field( 'cr-productfeed' ); ?>
					</p>
				</div>
			</form>
			<?php
			update_option( 'ivole_activation_notice', 0 );
		}

		public function save_settings() {
			if ( $this->is_this_page() && ! empty( $_POST ) ) {
				check_admin_referer( 'cr-productfeed' );

				do_action( 'cr_save_productfeed_' . $this->current_tab );

				WC_Admin_Settings::add_message( __( 'Your settings have been saved.', 'woocommerce' ) );
			}
		}

		public function is_this_page() {
			return ( isset( $_GET['page'] ) && $_GET['page'] === $this->menu_slug );
		}

		public function get_current_tab() {
			return $this->current_tab;
		}

		public function load_product_feed_css_js( $hook ) {
			$reviews_screen_id = sanitize_title( __( 'Reviews', 'customer-reviews-woocommerce' ) . Ivole_Reviews_Admin_Menu::$screen_id_bubble );
			if( $reviews_screen_id . '_page_cr-reviews-product-feed' === $hook ) {
				wp_enqueue_style( 'cr-admin-css', plugins_url('css/admin.css', dirname( dirname( __FILE__ ) ) ), array(), Ivole::CR_VERSION );
				wp_enqueue_style( 'cr_select2_admin_css', plugins_url('css/select2.min.css', dirname( dirname( __FILE__ ) ) ) );
				wp_enqueue_script( 'cr_select2_admin_js', plugins_url('js/select2.min.js', dirname( dirname( __FILE__ ) ) ) );
				wp_register_script( 'ivole-admin-categories', plugins_url('js/admin-categories.js', dirname( dirname( __FILE__ ) ) ), array( 'jquery' ), false, false );

				wp_localize_script('ivole-admin-categories', 'CrProductFeedStrings', array(
					'select_category' => __( 'Select a category', 'customer-reviews-woocommerce' ),
					'select_field' => __( 'Select a field', 'customer-reviews-woocommerce' ),
				));

				wp_enqueue_script( 'ivole-admin-categories' );
			}
		}
	}

endif;
