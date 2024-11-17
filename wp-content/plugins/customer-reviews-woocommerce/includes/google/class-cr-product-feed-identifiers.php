<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Identifiers_Product_Feed' ) ):

	class CR_Identifiers_Product_Feed {

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

		public function __construct( $product_feed_menu ) {
			$this->product_feed_menu = $product_feed_menu;

			$this->tab = 'identifiers';

			add_filter( 'cr_productfeed_tabs', array( $this, 'register_tab' ) );
			add_action( 'cr_productfeed_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'cr_save_productfeed_' . $this->tab, array( $this, 'save' ) );
			add_action( 'woocommerce_admin_field_product_feed_identifiers', array( $this, 'display_product_feed_identifiers' ) );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'Product Identifiers', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			$this->init_settings();
			WC_Admin_Settings::output_fields( $this->settings );
		}

		public function save() {
			$this->init_settings();
			$product_fields = array();
			if ( ! empty( $_POST ) ) {
				if ( isset( $_POST['cr_identifier_google_pid'] ) ) {
					$product_fields['pid'] = $_POST['cr_identifier_google_pid'];
				}
				if( isset( $_POST['cr_identifier_google_gtin'] ) ) {
					$product_fields['gtin'] = $_POST['cr_identifier_google_gtin'];
				}
				if( isset( $_POST['cr_identifier_google_mpn'] ) ) {
					$product_fields['mpn'] = $_POST['cr_identifier_google_mpn'];
				}
				if( isset( $_POST['cr_identifier_google_brand'] ) ) {
					$product_fields['brand'] = $_POST['cr_identifier_google_brand'];
				}
			}
			$_POST['ivole_product_feed_identifiers'] = $product_fields;
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
					'title' => __( 'Product Identifiers', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => __( ' Enable additional fields to maintain product identifiers in WooCommerce products. Next, specify mapping of WooCommerce product fields to Google Shopping product identifiers.', 'customer-reviews-woocommerce' ),
					'id'    => 'cr_categories'
				),
				array(
					'id'       => 'ivole_product_feed_enable_gtin',
					'title'    => __( 'GTIN', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Add a GTIN field to WooCommerce products (on \'Inventory\' tab). GTIN refers to a Global Trade Item Number, a globally unique number used to identify trade items, products, or services that can be purchased. GTIN is also an umbrella term that refers to UPC, EAN, JAN, and ISBN.', 'customer-reviews-woocommerce' ),
					'default'  => 'no',
					'type'     => 'checkbox',
					'autoload' => false
				),
				array(
					'id'       => 'ivole_product_feed_enable_mpn',
					'title'    => __( 'MPN', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Add an MPN field to WooCommerce products (on \'Inventory\' tab). MPN refers to a Manufacturer Part Number, a number that uniquely identifies the product to its manufacturer.', 'customer-reviews-woocommerce' ),
					'default'  => 'no',
					'type'     => 'checkbox',
					'autoload' => false
				),
				array(
					'id'       => 'ivole_product_feed_enable_brand',
					'title'    => __( 'Brand', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Add a Brand field to WooCommerce products (on \'Inventory\' tab). This field refers to a brand of the product.', 'customer-reviews-woocommerce' ),
					'default'  => 'no',
					'type'     => 'checkbox',
					'autoload' => false
				),
				array(
					'id'       => 'ivole_product_feed_enable_identifier_exists',
					'title'    => __( 'identifier_exists', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Add an identifier_exists field to WooCommerce products (on \'Inventory\' tab). Use it to indicate whether or not the unique product identifiers and brand are available for a product.', 'customer-reviews-woocommerce' ),
					'default'  => 'no',
					'type'     => 'checkbox',
					'autoload' => false
				),
				array(
					'id'       => 'ivole_google_brand_static',
					'title'    => __( 'Brand (Static)', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'desc'     => __( 'Specify a default brand for all products in your store. If this field is non-empty, the plugin will use this brand for all products in XML feeds for Google Shopping.', 'customer-reviews-woocommerce' ),
					'desc_tip' => true,
					'default'  => '',
					'css'      => 'width: 500px;max-width:100%;',
					'autoload' => false
				),
				array(
					'id'       => 'ivole_product_feed_enable_id_str_dat',
					'title'    => __( 'Structured Data', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Add product identifiers to structured data markup on WooCommerce product pages. According to Google\'s requirements, product identifiers will also be added to the HTML shown to visitors.', 'customer-reviews-woocommerce' ),
					'default'  => 'no',
					'type'     => 'checkbox'
				),
				array(
					'id'       => 'ivole_product_feed_identifiers',
					'type'     => 'product_feed_identifiers'
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

		public function display_product_feed_identifiers( $option ) {
			if( !$option['value'] ) {
				$option['value'] = array(
					'pid'   => '',
					'gtin'  => '',
					'mpn'   => '',
					'brand' => ''
				);
			}
			$list_fields = $this->get_product_attributes();
			?>
			<tr valign="top">
				<td colspan="2" style="padding-left:0px;padding-right:0px;">
					<table class="cr-product-feed-categories widefat">
						<thead>
							<tr>
								<th class="cr-product-feed-categories-th">
									<?php
									esc_html_e( 'WooCommerce Product Field', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Select a product field that should be mapped', 'customer-reviews-woocommerce' ) );
									?>
								</th>
								<th class="cr-product-feed-categories-th">
									<?php
									esc_html_e( 'Google Shopping Identifier', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Product identifiers required by Google Shopping', 'customer-reviews-woocommerce' ) );
									?>
								</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="cr-product-feed-categories-td">
									<select class="cr-product-feed-identifiers-select" name="cr_identifier_google_pid">
										<option></option>
										<?php foreach ( $list_fields as $attribute_value => $attribute_name ): ?>
											<option value="<?php echo $attribute_value; ?>" <?php if ( $attribute_value == $option['value']['pid'] ) echo "selected"; ?>><?php echo $attribute_name; ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="cr-product-feed-categories-td">
									<?php echo __( 'Product ID', 'customer-reviews-woocommerce' ); ?>
								</td>
							</tr>
							<tr class="cr-alternate">
								<td class="cr-product-feed-categories-td">
									<select class="cr-product-feed-identifiers-select" name="cr_identifier_google_gtin">
										<option></option>
										<?php foreach ( $list_fields as $attribute_value => $attribute_name ): ?>
											<option value="<?php echo $attribute_value; ?>" <?php if ( $attribute_value == $option['value']['gtin'] ) echo "selected"; ?>><?php echo $attribute_name; ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="cr-product-feed-categories-td">
									<?php echo __( 'GTIN', 'customer-reviews-woocommerce' ); ?>
								</td>
							</tr>
							<tr>
								<td class="cr-product-feed-categories-td">
									<select class="cr-product-feed-identifiers-select" name="cr_identifier_google_mpn">
										<option></option>
										<?php foreach ( $list_fields as $attribute_value => $attribute_name ): ?>
											<option value="<?php echo $attribute_value; ?>" <?php if ( $attribute_value == $option['value']['mpn'] ) echo "selected"; ?>><?php echo $attribute_name; ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="cr-product-feed-categories-td">
									<?php echo __( 'MPN', 'customer-reviews-woocommerce' ); ?>
								</td>
							</tr>
							<tr class="cr-alternate">
								<td class="cr-product-feed-categories-td">
									<select class="cr-product-feed-identifiers-select" name="cr_identifier_google_brand">
										<option></option>
										<?php foreach ( $list_fields as $attribute_value => $attribute_name ): ?>
											<option value="<?php echo $attribute_value; ?>" <?php if ( $attribute_value == $option['value']['brand'] ) echo "selected"; ?>><?php echo $attribute_name; ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="cr-product-feed-categories-td">
									<?php echo __( 'Brand', 'customer-reviews-woocommerce' ); ?>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
			<?php
		}

		protected function get_product_attributes() {
			global $wpdb;

			$product_attributes = array(
				'product_id'   => __( 'Product ID', 'customer-reviews-woocommerce' ),
				'product_sku'  => __( 'Product SKU', 'customer-reviews-woocommerce' ),
				'product_name' => __( 'Product Name', 'customer-reviews-woocommerce' )
			);

			$product_attributes = array_reduce( wc_get_attribute_taxonomies(), function( $attributes, $taxonomy ) {
				$key = 'attribute_' . $taxonomy->attribute_name;
				$attributes[$key] = ucfirst( $taxonomy->attribute_label );

				return $attributes;
			}, $product_attributes );

			$meta_attributes = $wpdb->get_results(
				"SELECT meta.meta_id, meta.meta_key
				FROM {$wpdb->postmeta} AS meta, {$wpdb->posts} AS posts
				WHERE meta.post_id = posts.ID AND posts.post_type LIKE '%product%' AND (
					meta.meta_key NOT LIKE '\_%'
					OR meta.meta_key LIKE '\_woosea%'
					OR meta.meta_key LIKE '\_wpm%' OR meta.meta_key LIKE '\_cr_%' OR meta.meta_key LIKE '\_cpf_%'
					OR meta.meta_key LIKE '\_yoast%'
					OR meta.meta_key LIKE '\_alg_ean%'
					OR meta.meta_key LIKE '\_wpsso_product%'
					OR meta.meta_key LIKE '\_ts_%'
				)
				GROUP BY meta.meta_key",
				ARRAY_A
			);

			if ( is_array( $meta_attributes ) ) {
				$product_attributes = array_reduce( $meta_attributes, function( $attributes, $meta_attribute ) {
					// If the meta entry starts with attribute_, then consider it as an attribute
					if ( 'attribute_' === substr( $meta_attribute['meta_key'], 0, 10 ) ) {
						$key = $meta_attribute['meta_key'];
					} else {
						$key = 'meta_' . $meta_attribute['meta_key'];
					}
					$attributes[$key] = ucfirst( str_replace( '_', ' ', $meta_attribute['meta_key'] ) );
					return $attributes;
				}, $product_attributes );
			}
			$product_attributes['meta__cr_gtin'] = __( 'Product GTIN', 'customer-reviews-woocommerce' );
			$product_attributes['meta__cr_mpn'] = __( 'Product MPN', 'customer-reviews-woocommerce' );
			$product_attributes['meta__cr_brand'] = __( 'Product Brand', 'customer-reviews-woocommerce' );
			$product_attributes['meta__cr_material'] = __( 'Product Material', 'customer-reviews-woocommerce' );
			$product_attributes['meta__cr_multipack'] = __( 'Product Multipack', 'customer-reviews-woocommerce' );
			$product_attributes['meta__cr_bundle'] = __( 'Product Bundle', 'customer-reviews-woocommerce' );

			$product_attributes['tags_tags'] = __( 'Product Tag', 'customer-reviews-woocommerce' );

			$taxonomies_3rd = array( 'pwb-brand', 'yith_product_brand' );
			foreach ($taxonomies_3rd as $taxonomy_3rd) {
				$product_terms = get_terms( array(
					'taxonomy' => $taxonomy_3rd
				) );
				if( $product_terms && !is_wp_error( $product_terms ) ) {
					$product_attributes['terms_' . $taxonomy_3rd] = $taxonomy_3rd;
				}
			}

			natcasesort( $product_attributes );

			return $product_attributes;
		}
	}

endif;
