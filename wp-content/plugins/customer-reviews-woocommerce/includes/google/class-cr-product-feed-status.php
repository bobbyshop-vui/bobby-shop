<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Status_Product_Feed' ) ):

	class CR_Status_Product_Feed {

		/**
		* @var CR_Product_Feed_Admin_Menu The instance of the admin menu
		*/
		protected $product_feed_menu;

		/**
		* @var string The slug of this tab
		*/
		protected $tab;

		/**
		* @var array The fields for this tab
		*/
		protected $settings;

		public function __construct( $product_feed_menu ) {
			$this->product_feed_menu = $product_feed_menu;

			$this->tab = 'overview';

			add_filter( 'cr_productfeed_tabs', array( $this, 'register_tab' ) );
			add_action( 'cr_productfeed_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'cr_save_productfeed_' . $this->tab, array( $this, 'save' ) );

			add_action( 'woocommerce_admin_field_feed_file_url', array( $this, 'display_feed_file_url' ) );
			add_action( 'woocommerce_admin_field_product_feed_file_url', array( $this, 'display_product_feed_file_url' ) );
			add_action( 'woocommerce_admin_field_product_feed_status', array( $this, 'display_product_feed_status' ) );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'Overview', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			$this->init_settings();
			WC_Admin_Settings::output_fields( $this->settings );
		}

		public function save() {
			$this->init_settings();
			WC_Admin_Settings::save_fields( $this->settings );

			$feed = new CR_Google_Shopping_Prod_Feed();
			if ( $feed->is_enabled() ) {
				$feed->activate();
			} else {
				$feed->deactivate();
			}

			$feed_reviews = new CR_Google_Shopping_Feed();
			if ( $feed_reviews->is_enabled() ) {
				$feed_reviews->activate();
			} else {
				$feed_reviews->deactivate();
			}
		}

		protected function init_settings() {
			$overview_desc = '<b>' . __( 'Review Stars in Google Search Organic Listings', 'customer-reviews-woocommerce' ) . '</b><br><br>';
			$overview_desc .= __( 'The standard WooCommerce functionality already includes structured data markup to display your product reviews effectively within organic search results. This plugin extends the standard functionality and adds some extra markup to help search engines properly crawl your shop.', 'customer-reviews-woocommerce' );
			$overview_desc .= sprintf(
				__( 'It is important to understand that having a valid structured data markup in place makes your website eligible for organic stars in Google but it doesn\'t guarantee that they will be shown. You can test the rich snippets using <a href="%1$s">Google’s Rich Results Test</a>.', 'customer-reviews-woocommerce' ),
				'https://search.google.com/test/rich-results'
			);
			$overview_desc .=	'<br><br>';
			$overview_desc .=	__( 'Google now expects product identifiers (GTIN, MPN, Brand) to be populated in the structured data markup. Our plugin offers an option to add fields for product identifiers to WooCommerce products.', 'customer-reviews-woocommerce' ) . '<br><br>';
			$overview_desc .= '<b>' . __( 'Review Stars in Google Shopping', 'customer-reviews-woocommerce' ) . '</b><br><br>';
			$overview_desc .= sprintf(
				__( 'Google Shopping is a service that allows merchants to list their products by uploading a product feed in the <a href="%s">Merchant Center</a>. XML Product Feed is necessary to submit products to Google Shopping. XML Product Review Feed is necessary to show stars for products in Google Shopping search results. The feeds should be maintained in Google Merchant Center account.', 'customer-reviews-woocommerce' ),
				'https://merchants.google.com/'
			);
			$this->settings = array(
				array(
					'title' => __( 'Overview', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => $overview_desc,
					'id'    => 'cr_status'
				),
				array(
					'title'   => __( 'Product Feed', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Generate XML Product Feed', 'customer-reviews-woocommerce' ),
					'desc_tip' => __( 'When active, an XML file with products for Google Shopping will be created immediately after saving settings and then updated according to the Refresh Frequency setting.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_product_feed',
					'default' => 'no',
					'type'    => 'checkbox',
					'autoload' => false
				),
				array(
					'id'       => 'ivole_product_feed_variations',
					'title'    => __( 'Product Variants', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Include product variants in the XML product feed', 'customer-reviews-woocommerce' ),
					'desc_tip' => __( 'If you sell variable products, enable this option to include product variations in the XML product feed.', 'customer-reviews-woocommerce' ),
					'default'  => 'no',
					'type'     => 'checkbox',
					'autoload' => false
				),
				array(
					'id'       => 'ivole_product_feed_file_url',
					'title'    => __( 'Product Feed URL', 'customer-reviews-woocommerce' ),
					'type'     => 'product_feed_file_url',
					'desc'     => __( 'URL of the file with the product feed that should be maintained in Google Merchant Center.', 'customer-reviews-woocommerce' ),
					'desc_tip' => true,
					'css'      => 'width: 500px;max-width:100%;',
					'autoload' => false
				),
				array(
					'id'       => 'ivole_google_generate_xml_feed',
					'title'    => __( 'Product Review Feed', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Generate XML Product Review Feed for Google Shopping', 'customer-reviews-woocommerce' ),
					'desc_tip' => __( 'When active, an XML file with product reviews for Google Shopping will be created immediately after saving settings and then updated according to the Refresh Frequency setting.', 'customer-reviews-woocommerce' ),
					'default'  => 'no',
					'type'     => 'checkbox',
					'autoload' => false
				),
				array(
					'id'       => 'ivole_feed_file_url',
					'title'    => __( 'Product Review Feed URL', 'customer-reviews-woocommerce' ),
					'type'     => 'feed_file_url',
					'desc'     => __( 'URL of the file with the product reviews feed that should be maintained in Google Merchant Center.', 'customer-reviews-woocommerce' ),
					'desc_tip' => true,
					'autoload' => false,
					'css'      => 'width: 500px;max-width:100%;'
				),
				array(
					'title'    => __( 'Refresh Frequency', 'customer-reviews-woocommerce' ),
					'type'     => 'number',
					'desc'     => __( 'XML feeds with products and product reviews will be refreshed every N days. N is a refresh frequency that needs to be set in this field.', 'customer-reviews-woocommerce' ),
					'default'  => 1,
					'id'       => 'ivole_feed_refresh',
					'desc_tip' => true,
					'autoload' => false
				),
				array(
					'id'       => 'ivole_product_feed_status',
					'title'    => __( 'Status', 'customer-reviews-woocommerce' ),
					'is_option' => false,
					'type'     => 'product_feed_status',
					'desc'     => __( 'Information about the product categories and product identifiers.', 'customer-reviews-woocommerce' ),
					'desc_tip' => true
				),
				array(
					'type' => 'sectionend',
					'id'   => 'cr_status'
				)
			);
		}

		public function is_this_tab() {
			return $this->product_feed_menu->is_this_page() && ( $this->product_feed_menu->get_current_tab() === $this->tab );
		}

		public function display_product_feed_file_url( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$upload_url = wp_upload_dir();
			if( !$value['value'] ) {
				$value['value'] = '/cr/' . apply_filters( 'cr_gs_product_feed_file', 'product_feed_' . uniqid() . '.xml' );
			}
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<input
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					readonly
					value="<?php echo $upload_url['baseurl'] . $value['value']; ?>"
					/>
					<input
					type="hidden"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					name="<?php echo esc_attr( $value['id'] ); ?>"
					value="<?php echo $value['value']; ?>"
					/>
				</td>
			</tr>
			<?php
		}

		public function display_feed_file_url( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$upload_url = wp_upload_dir();
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					readonly
					value="<?php echo $upload_url['baseurl'] . '/cr/' . apply_filters( 'cr_gs_product_reviews_feed_file', 'product_reviews.xml' ); ?>"
					/>
				</td>
			</tr>
			<?php
		}

		public function display_product_feed_status( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$product_status = apply_filters( 'cr_gs_product_feed_product_status', array( 'publish' ) );

			// count categories
			$args_cat = array(
				'taxonomy' => 'product_cat',
				'hide_empty' => false
			);
			$categories = get_categories( $args_cat );
			$cat_total = 0;
			$cat_exclude = 0;
			$cat_mapped = 0;
			if( $categories && is_array( $categories ) ) {
				$cat_total = count( $categories );
			}
			$categories_mapping = get_option( 'ivole_product_feed_categories', array() );
			$categories_exclude = get_option( 'ivole_product_feed_categories_exclude', array() );
			$categories_included = array();
			foreach ($categories as $cat) {
				if( in_array( strval( $cat->term_id ), $categories_exclude, true ) ) {
					$cat_exclude++;
					continue;
				}
				$categories_included[] = $cat->slug;
				if( isset( $categories_mapping[$cat->term_id] ) && $categories_mapping[$cat->term_id] > 0 ) {
					$cat_mapped++;
					continue;
				}
			}
			$cat_total = $cat_total - $cat_exclude;

			// count product identifiers
			$inde_total = 0;
			$inde_mapped = 0;
			$identifiers = get_option( 'ivole_product_feed_identifiers', array(
				'pid'   => '',
				'gtin'  => '',
				'mpn'   => '',
				'brand' => ''
			) );
			$inde_total = count( $identifiers );
			$inde_mapped = count( array_filter( $identifiers ) );

			// count product identifiers (reviews)
			$indr_total = 0;
			$indr_mapped = 0;
			$identifiers_reviews = get_option( 'ivole_google_field_map', array(
				'gtin'  => '',
				'mpn'   => '',
				'sku'   => '',
				'brand' => ''
			) );
			$indr_total = count( $identifiers_reviews );
			$indr_mapped = count( array_filter( $identifiers_reviews ) );

			// excluded products
			$excl_products = get_option( 'ivole_excl_product_ids', array() );
			if( !is_array( $excl_products ) ) {
				$excl_products = array_filter( array_map( 'intval', explode( ',', trim( $excl_products ) ) ) );
			}

			// count products
			$prod_total = 0;
			$prod_incl = 0;
			$args = array(
				'status' => $product_status,
				'limit' => 1,
				'return' => 'ids',
				'paginate' => true
			);
			$products = wc_get_products( $args );
			if( $products && is_object( $products ) ) {
				$prod_total = $products->total;
			}
			if( 0 < count( $categories_included ) ) {
				$args = array(
					'status' => $product_status,
					'limit' => 1,
					'category' => $categories_included,
					'exclude' => $excl_products,
					'paginate' => true
				);
				$products = wc_get_products( $args );
				if( $products && is_object( $products ) ) {
					$prod_incl = $products->total;
				}
			} else {
				$prod_incl = 0;
			}

			// count variations
			$var_total = 0;
			$var_incl = 0;
			$args = array(
				'status' => $product_status,
				'type' => 'variation',
				'limit' => 1,
				'paginate' => true
			);
			$variations = wc_get_products( $args );
			if( $variations && is_object( $variations ) ) {
				$var_total = $variations->total;
			}
			if( 0 < count( $categories_included ) ) {
				$args = array(
					'status' => $product_status,
					'type' => 'variation',
					'limit' => 1,
					'exclude' => $excl_products,
					'paginate' => true
				);
				$variations = wc_get_products( $args );
				if( $variations && is_object( $variations ) ) {
					$var_incl = $variations->total;
				}
			} else {
				$var_incl = 0;
			}

			// count reviews
			global $wpdb;
			$reviews_total = array( '1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0, 'all' => 0 );
			//WPML integration
			//fetch IDs of reviews (cannot use get_comments due to WPML that adds a hook to filter comments by language)
			if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
				$query_count = "SELECT commsm.meta_value AS rating, COUNT(comms.comment_ID) AS count FROM $wpdb->comments comms " .
				"INNER JOIN $wpdb->posts psts ON comms.comment_post_ID = psts.ID " .
				"INNER JOIN $wpdb->commentmeta commsm ON comms.comment_ID = commsm.comment_id " .
				"WHERE comms.comment_approved = '1' AND psts.post_type = 'product' AND commsm.meta_key = 'rating' " .
				"GROUP BY commsm.meta_value";
				$reviews_counts = $wpdb->get_results( $query_count, OBJECT );
				foreach( $reviews_counts as $reviews_count ) {
					if( 0 < $reviews_count->count ) {
						$reviews_total[strval($reviews_count->rating)] = $reviews_count->count;
						$reviews_total['all'] += $reviews_count->count;
					}
				}
			} else {
				$args = array(
					'post_type' => 'product',
					'status'    => 'approve',
					'meta_key'	=> 'rating',
					'update_comment_meta_cache' => true,
					'update_comment_post_cache' => true,
					'count' => true
				);
				$reviews_total['all'] = get_comments( $args );
				for( $i=1; $i<6; $i++ ){
					$args['meta_value'] = $i;
					$reviews_total[strval($i)] = get_comments( $args );
				}
			}

			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<table class="wc_status_table widefat cr-product-feed-status" cellspacing="0" id="status">
						<tbody>
							<tr>
								<td><?php printf( __( '<strong>%1d / %2d</strong> product categories mapped, <strong>%3d</strong> excluded', 'customer-reviews-woocommerce' ), $cat_mapped, $cat_total, $cat_exclude ); ?></td>
								<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'WooCommerce product categories should be mapped to Google categories on "Categories" tab.', 'customer-reviews-woocommerce' ) ); ?></td>
								<td>
									<?php if ( $cat_mapped == $cat_total ) : ?>
										<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
									<?php else : ?>
										<mark class="no"><span class="dashicons dashicons-warning"></span></mark>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td><?php printf( __( '<strong>%1d / %2d</strong> product identifiers mapped', 'customer-reviews-woocommerce' ), $inde_mapped, $inde_total ); ?></td>
								<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'WooCommerce product fields should be mapped to Google product identifiers on "Product Identifiers" tab.', 'customer-reviews-woocommerce' ) ); ?></td>
								<td>
									<?php if ( $inde_mapped == $inde_total ) : ?>
										<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
									<?php else : ?>
										<mark class="no"><span class="dashicons dashicons-warning"></span></mark>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td><?php printf( __( '<strong>%1d / %2d</strong> product identifiers (reviews) mapped', 'customer-reviews-woocommerce' ), $indr_mapped, $indr_total ); ?></td>
								<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'WooCommerce product fields should be mapped to Google product identifiers on "Reviews" tab.', 'customer-reviews-woocommerce' ) ); ?></td>
								<td>
									<?php if ( $indr_mapped == $indr_total ) : ?>
										<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
									<?php else : ?>
										<mark class="no"><span class="dashicons dashicons-warning"></span></mark>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php printf( _n( '<strong>%1d</strong> product, <strong>%2d</strong> excluded', '<strong>%1d</strong> products, <strong>%2d</strong> excluded', $prod_total, 'customer-reviews-woocommerce' ), $prod_total, $prod_total - $prod_incl ); ?>
								</td>
								<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Review what products and product categories are excluded on the \'Categories and Products\' tab.', 'customer-reviews-woocommerce' ) ); ?></td>
								<td>
									<?php if ( 0 < $prod_incl ) : ?>
										<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
									<?php else : ?>
										<mark class="no"><span class="dashicons dashicons-warning"></span></mark>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php printf( _n( '<strong>%1d</strong> product variation, <strong>%2d</strong> excluded', '<strong>%1d</strong> product variations, <strong>%2d</strong> excluded', $var_total, 'customer-reviews-woocommerce' ), $var_total, $var_total - $var_incl ); ?>
								</td>
								<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Review what products and product categories are excluded on the \'Categories and Products\' tab.', 'customer-reviews-woocommerce' ) ); ?></td>
								<td>
									<?php if ( 0 < $var_incl ) : ?>
										<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
									<?php else : ?>
										<mark class="no"><span class="dashicons dashicons-warning"></span></mark>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php printf( _n( '<strong>%1d</strong> product review (all ratings)', '<strong>%1d</strong> product reviews (all ratings)', $reviews_total['all'], 'customer-reviews-woocommerce' ), $reviews_total['all'] ); ?>
								</td>
								<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Additional settings related to the XML Reviews feed are on the \'Reviews\' tab.', 'customer-reviews-woocommerce' ) ); ?></td>
								<td>
									<?php if ( 0 < $reviews_total['all'] ) : ?>
										<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
									<?php else : ?>
										<mark class="no"><span class="dashicons dashicons-warning"></span></mark>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td>
									&#8594; <?php printf( _n( '<strong>%1d</strong> product review (5 stars)', '<strong>%1d</strong> product reviews (5 stars)', $reviews_total['5'], 'customer-reviews-woocommerce' ), $reviews_total['5'] ); ?>
								</td>
								<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Additional settings related to the XML Reviews feed are on the \'Reviews\' tab.', 'customer-reviews-woocommerce' ) ); ?></td>
								<td>
									<?php if ( 0 < $reviews_total['all'] ) : ?>
										<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
									<?php else : ?>
										<mark class="no"><span class="dashicons dashicons-warning"></span></mark>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td>
									&#8594; <?php printf( _n( '<strong>%1d</strong> product review (4 stars)', '<strong>%1d</strong> product reviews (4 stars)', $reviews_total['4'], 'customer-reviews-woocommerce' ), $reviews_total['4'] ); ?>
								</td>
								<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Additional settings related to the XML Reviews feed are on the \'Reviews\' tab.', 'customer-reviews-woocommerce' ) ); ?></td>
								<td>
									<?php if ( 0 < $reviews_total['all'] ) : ?>
										<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
									<?php else : ?>
										<mark class="no"><span class="dashicons dashicons-warning"></span></mark>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td>
									&#8594; <?php printf( _n( '<strong>%1d</strong> product review (3 stars)', '<strong>%1d</strong> product reviews (3 stars)', $reviews_total['3'], 'customer-reviews-woocommerce' ), $reviews_total['3'] ); ?>
								</td>
								<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Additional settings related to the XML Reviews feed are on the \'Reviews\' tab.', 'customer-reviews-woocommerce' ) ); ?></td>
								<td>
									<?php if ( 0 < $reviews_total['all'] ) : ?>
										<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
									<?php else : ?>
										<mark class="no"><span class="dashicons dashicons-warning"></span></mark>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td>
									&#8594; <?php printf( _n( '<strong>%1d</strong> product review (2 stars)', '<strong>%1d</strong> product reviews (2 stars)', $reviews_total['2'], 'customer-reviews-woocommerce' ), $reviews_total['2'] ); ?>
								</td>
								<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Additional settings related to the XML Reviews feed are on the \'Reviews\' tab.', 'customer-reviews-woocommerce' ) ); ?></td>
								<td>
									<?php if ( 0 < $reviews_total['all'] ) : ?>
										<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
									<?php else : ?>
										<mark class="no"><span class="dashicons dashicons-warning"></span></mark>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td>
									&#8594; <?php printf( _n( '<strong>%1d</strong> product review (1 star)', '<strong>%1d</strong> product reviews (1 star)', $reviews_total['1'], 'customer-reviews-woocommerce' ), $reviews_total['1'] ); ?>
								</td>
								<td class="help"><?php echo CR_Admin::ivole_wc_help_tip( __( 'Additional settings related to the XML Reviews feed are on the \'Reviews\' tab.', 'customer-reviews-woocommerce' ) ); ?></td>
								<td>
									<?php if ( 0 < $reviews_total['all'] ) : ?>
										<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
									<?php else : ?>
										<mark class="no"><span class="dashicons dashicons-warning"></span></mark>
									<?php endif; ?>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
			<?php
		}
	}

endif;
