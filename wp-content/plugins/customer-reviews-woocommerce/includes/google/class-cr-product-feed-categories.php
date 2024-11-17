<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Categories_Product_Feed' ) ):

	class CR_Categories_Product_Feed {

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

		protected $alternate = false;
		protected $google_categories;
		protected $google_categories_path = 'misc/taxonomy-with-ids.en-US.txt';

		public function __construct( $product_feed_menu ) {
			$this->product_feed_menu = $product_feed_menu;

			// check shop's language and pick appropriate file name
			$taxonomy_languages = array( 'cs-CZ', 'da-DK', 'de-DE', 'en-GB', 'es-ES', 'fr-FR', 'id-ID',
			'it-IT', 'nl-NL', 'no-NO', 'pl-PL', 'pt-BR', 'ru-RU', 'sv-SE', 'tr-TR', 'uk-UA', 'vi-VN' );
			$blog_language = get_bloginfo( 'language', 'display' );
			switch( $blog_language ) {
				case 'cs':
				$blog_language = 'cs-CZ';
				break;
				case 'tr':
				$blog_language = 'tr-TR';
				break;
				case 'nl':
				$blog_language = 'nl-NL';
				break;
				case 'nb-NO':
				$blog_language = 'no-NO';
				break;
				case 'uk':
				$blog_language = 'uk-UA';
				break;
				default:
				break;
			}
			if( in_array( $blog_language, $taxonomy_languages ) ) {
				$this->google_categories_path = 'misc/taxonomy-with-ids.' . $blog_language . '.txt';
			} else {
				$this->google_categories_path = 'misc/taxonomy-with-ids.en-US.txt';
			}

			$this->tab = 'categories';

			add_filter( 'cr_productfeed_tabs', array( $this, 'register_tab' ) );
			add_action( 'cr_productfeed_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'cr_save_productfeed_' . $this->tab, array( $this, 'save' ) );
			add_action( 'woocommerce_admin_field_product_feed_categories', array( $this, 'display_product_feed_categories' ) );
			add_action( 'wp_ajax_cr_google_categories', array( $this, 'cr_google_categories_ajax' ) );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'Categories and Products', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			$this->init_settings();
			WC_Admin_Settings::output_fields( $this->settings );
		}

		public function save() {
			$this->init_settings();

			$selecte_key_const = 'cr_category_google_';
			$categories_mapping = array();
			$categories_exclude = array();
			if ( ! empty( $_POST ) ) {
				foreach ($_POST as $key => $value) {
					$tmp_pos = stripos( $key, $selecte_key_const );
					if( $tmp_pos !== false ) {
						$tmp_category = substr( $key, $tmp_pos + strlen( $selecte_key_const ) );
						$categories_mapping[$tmp_category] = $value;
					}
				}
				if( isset( $_POST['cr_category_exclude'] ) && is_array( $_POST['cr_category_exclude'] ) ) {
					foreach ($_POST['cr_category_exclude'] as $key => $value) {
						$categories_exclude[] = $value;
					}
				}
			}
			$_POST['ivole_product_feed_categories'] = $categories_mapping;
			$_POST['ivole_product_feed_categories_exclude'] = $categories_exclude;
			if( isset( $_POST['ivole_excl_product_ids'] ) && strlen( $_POST['ivole_excl_product_ids'] ) > 0 ) {
				$_POST['ivole_excl_product_ids'] = implode( ',', array_filter( array_map( 'intval', explode( ',', trim( $_POST['ivole_excl_product_ids'] ) ) ) ) );
			}
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
			$this->settings = array(
				array(
					'title' => __( 'Exclude Products', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Provide a list of WooCommerce products that should be excluded from XML Product Feed. It should be a comma-separated list of product IDs. If all products should be added to XML Product Feed, keep this field empty.', 'customer-reviews-woocommerce' ),
					'id'    => 'cr_excl_products'
				),
				array(
					'title'    => __( 'Product IDs', 'customer-reviews-woocommerce' ),
					'type'     => 'textarea',
					'desc'     => __( 'A comma-separated list of product IDs that should be excluded from XML Product Feed. Product ID is a number that can found in the URL of the page to edit the product.', 'customer-reviews-woocommerce' ),
					'default'  => '',
					'id'       => 'ivole_excl_product_ids',
					'css'      => 'height:5em;',
					'class'    => 'cr-admin-settings-wide-text',
					'desc_tip' => true
				),
				array(
					'title'   => __( 'Exclude unavailable products', 'customer-reviews-woocommerce' ),
					'desc' => __( 'Exclude out-of-stock products from both XML Product and XML Product Review feeds. It is recommended to keep this option disabled because Google determines product availability based on the \'availability\' attribute and does not require removal of out-of-stock products from the XML feeds.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_excl_out_of_stock',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'type' => 'sectionend',
					'id'   => 'cr_excl_products'
				),
				array(
					'title' => __( 'Product Categories', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => sprintf( __( 'Specify mapping of WooCommerce product categories to Google Shopping product categories. The full list of Google Shopping product categories can be found %shere', 'customer-reviews-woocommerce' ), '<a href="' . plugins_url( $this->google_categories_path, dirname( dirname( __FILE__ ) ) ) . '" target="_blank">' ) .
					'</a><img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png" class="cr-product-feed-categories-ext-icon">.',
					'id'    => 'cr_categories'
				),
				array(
					'id'       => 'ivole_product_feed_categories_exclude',
					'type'     => 'product_feed_categories_exclude'
				),
				array(
					'id'       => 'ivole_product_feed_categories',
					'type'     => 'product_feed_categories'
				),
				array(
					'type' => 'sectionend',
					'id'   => 'cr_categories'
				)
			);
		}

		public function is_this_tab() {
			return $this->product_feed_menu->is_this_page() && ( $this->product_feed_menu->get_current_tab() === $this->tab );
		}

		public function display_product_feed_categories( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$args_cat = array(
				'taxonomy' => 'product_cat',
				'hide_empty' => false
			);
			$categories = get_categories( $args_cat );
			$categories_count = 0;
			if( $categories && is_array( $categories ) ) {
				$categories_count = count( $categories );
			}
			if( $categories_count > 0 ) {
				$top_level_elements = array();
				$children_elements  = array();
				foreach ( $categories as $e ) {
					if ( $e->parent > 0 ) {
						$children_elements[ $e->parent ][] = $e;
					} else {
						$top_level_elements[] = $e;
					}
				}
				$categories_mapping = get_option( 'ivole_product_feed_categories', array() );
				$categories_exclude = get_option( 'ivole_product_feed_categories_exclude', array() );
				$this->google_categories = file( plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . $this->google_categories_path, FILE_IGNORE_NEW_LINES );
				if( !$this->google_categories ) {
					$this->google_categories = array();
				}
				array_splice( $this->google_categories, 0, 1 );
				?>
				<tr valign="top">
					<td colspan="2" style="padding-left:0px;padding-right:0px;">
						<table class="cr-product-feed-categories widefat">
							<thead>
								<tr>
									<th class="cr-product-feed-categories-th">
										<?php
										esc_html_e( 'Product Category', 'customer-reviews-woocommerce' );
										echo CR_Admin::ivole_wc_help_tip( __( 'Product category in WooCommerce', 'customer-reviews-woocommerce' ) );
										?>
									</th>
									<th class="cr-product-feed-categories-th" style="width:150px;">
										<?php
										esc_html_e( 'Product Count', 'customer-reviews-woocommerce' );
										echo CR_Admin::ivole_wc_help_tip( __( 'Number of products in a category', 'customer-reviews-woocommerce' ) );
										?>
									</th>
									<th class="cr-product-feed-categories-th" style="width:100px;">
										<?php
										esc_html_e( 'Exclude', 'customer-reviews-woocommerce' );
										echo CR_Admin::ivole_wc_help_tip( __( 'Exclude a category of products from the Product Feed', 'customer-reviews-woocommerce' ) );
										?>
									</th>
									<th class="cr-product-feed-categories-th" style="width:300px;">
										<?php
										esc_html_e( 'Google Shopping Category', 'customer-reviews-woocommerce' );
										echo CR_Admin::ivole_wc_help_tip( __( 'Product category from Google Shopping product taxonomy', 'customer-reviews-woocommerce' ) );
										?>
									</th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $top_level_elements as $e ) {
									$this->display_category( $e, $children_elements, 0, $categories_mapping, $categories_exclude );
								}
								if ( count( $children_elements ) > 0 ) {
									$empty_array = array();
									foreach ( $children_elements as $orphans ) {
										foreach ( $orphans as $op ) {
											$this->display_category( $op, $empty_array, 0, $categories_mapping, $categories_exclude );
										}
									}
								}
								?>
							</tbody>
						</table>
					</td>
				</tr>
				<?php
			} else {
				echo '<div style="background:#FF4136;padding:10px;20px;font-weight:bold;margin-top:30px;margin-bottom:30px;">';
				_e( 'No product categories found', 'customer-reviews-woocommerce' );
				echo '</div>';
			}
		}

		public function display_category( $category, &$children_elements, $depth, $cat_mapping, $cat_exclude ) {
			$id = $category->term_id;
			if( $this->alternate ) {
				$css_class = 'class="cr-alternate"';
			} else {
				$css_class = 'class=""';
			}
			$this->alternate = ! $this->alternate;
			?>
			<tr <?php echo $css_class; ?>>
				<td class="cr-product-feed-categories-td">
					<a href="<?php echo admin_url( 'edit.php?post_type=product&product_cat=' . $category->slug ) ?>" target="_blank">
						<?php echo str_repeat( '&mdash; ', $depth ) . esc_html( $category->name ); ?>
					</a>
					<img src="<?php echo untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png'; ?>"
					class="cr-product-feed-categories-ext-icon">
				</td>
				<td class="cr-product-feed-categories-td"><?php echo esc_html( $category->count ); ?></td>
				<?php
				$exclude_checked = '';
				foreach ($cat_exclude as $key => $value) {
					if( $id == $value ) {
						$exclude_checked = ' checked="checked"';
						break;
					}
				}
				?>
				<td class="cr-product-feed-categories-td">
					<input type="checkbox" name="cr_category_exclude[]" value="<?php echo $id . '""' . $exclude_checked; ?>>
				</td>
				<?php
				if( isset( $cat_mapping[$id] ) && $cat_mapping[$id] ) {
					$temp_cat_id = $cat_mapping[$id];
					$temp_cat = array_filter( $this->google_categories, function( $category ) use( $temp_cat_id ) {
						$position = stripos( $category, $temp_cat_id . ' -' );
						return ( $position !== false && $position === 0 );
					} );
					if( count( $temp_cat ) > 0 ) {
						$temp_cat = array_values( $temp_cat );
						$saved_cat = explode( ' - ', $temp_cat[0] );
					} else {
						$saved_cat = array( '', '' );
					}
					?>
					<td class="cr-product-feed-categories-td">
						<select class="cr-product-feed-categories-select" name="cr_category_google_<?php echo $id; ?>">
							<option value="<?php echo $saved_cat[0]; ?>"><?php echo $saved_cat[1]; ?></option>
						</select>
					</td>
					<?php
				} else {
					?>
					<td class="cr-product-feed-categories-td">
						<select class="cr-product-feed-categories-select" name="cr_category_google_<?php echo $id; ?>">
							<option></option>
						</select>
					</td>
					<?php
				}
				?>
			</tr>
			<?php
			if ( 100 > $depth + 1 && isset( $children_elements[ $id ] ) ) {
				foreach ( $children_elements[ $id ] as $child ) {
					$this->display_category( $child, $children_elements, $depth + 1, $cat_mapping, $cat_exclude );
				}
				unset( $children_elements[ $id ] );
			}
		}

		public function cr_google_categories_ajax() {
			$q = strval( $_GET['q'] );
			$temp = file( plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . $this->google_categories_path, FILE_IGNORE_NEW_LINES );
			array_splice( $temp, 0, 1 );
			$temp = array_filter( $temp, function( $category ) use( $q ) {
				return stripos( $category, $q ) !== false;
			} );
			$search_result = array_map(
				function( $line ) {
					$tmp = explode( ' - ', $line );
					return array(
						'id' => $tmp[0],
						'text' => $tmp[1]
					);
				},
				$temp
			);
			$json_search_result = json_encode( array_values( $search_result ) );
			wp_send_json( $json_search_result );
		}
	}

endif;
