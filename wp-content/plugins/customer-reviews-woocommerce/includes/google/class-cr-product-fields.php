<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Product_Fields' ) ) :

	class CR_Product_Fields {

		private $gtin = false;
		private $mpn = false;
		private $brand = false;
		private $identifier_exists = false;
		private $multipack = false;
		private $material = false;
		private $bundle = false;

		public function __construct() {
			$this->gtin = ( 'yes' === get_option( 'ivole_product_feed_enable_gtin', 'no' ) );
			$this->mpn = ( 'yes' === get_option( 'ivole_product_feed_enable_mpn', 'no' ) );
			$this->brand = ( 'yes' === get_option( 'ivole_product_feed_enable_brand', 'no' ) );
			$this->identifier_exists = ( 'yes' === get_option( 'ivole_product_feed_enable_identifier_exists', 'no' ) );
			$this->material = ( 'yes' === get_option( 'ivole_product_feed_enable_material', 'no' ) );
			$this->multipack = ( 'yes' === get_option( 'ivole_product_feed_enable_multipack', 'no' ) );
			$this->bundle = ( 'yes' === get_option( 'ivole_product_feed_enable_bundle', 'no' ) );

			if(
				$this->gtin || $this->mpn || $this->brand ||
				$this->identifier_exists || $this->multipack || $this->bundle ||
				$this->material
			) {
				add_action( 'woocommerce_product_options_sku', array( $this, 'display_fields' ) );
				add_action( 'woocommerce_product_options_general_product_data', array( $this, 'display_general_fields' ) );
				add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_fields' ) );
				add_action( 'woocommerce_variation_options_pricing', array( $this, 'display_fields_variation'), 10, 3 );
				add_action( 'woocommerce_variation_options', array( $this, 'display_fields_variation_options'), 10, 3 );
				add_action( 'woocommerce_admin_process_variation_object', array( $this, 'save_fields_variation' ), 10, 2 );
			}
		}

		public function display_fields() {
			global $product_object;
			if( $product_object ) {
				if( $this->gtin ) {
					woocommerce_wp_text_input(
						array(
							'id'          => '_cr_gtin',
							'value'       => $product_object->get_meta( '_cr_gtin', true, 'edit' ),
							'label'       => '<abbr title="' . esc_attr__( 'Global Trade Item Number', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'GTIN', 'customer-reviews-woocommerce' ) . '</abbr>',
							'desc_tip'    => true,
							'description' => __( 'GTIN refers to a Global Trade Item Number, a globally unique number used to identify trade items, products, or services that can be purchased. GTIN is also an umbrella term that refers to UPC, EAN, JAN, and ISBN.', 'customer-reviews-woocommerce' ),
						)
					);
				}
				if( $this->mpn ) {
					woocommerce_wp_text_input(
						array(
							'id'          => '_cr_mpn',
							'value'       => $product_object->get_meta( '_cr_mpn', true, 'edit' ),
							'label'       => '<abbr title="' . esc_attr__( 'Manufacturer Part Number', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'MPN', 'customer-reviews-woocommerce' ) . '</abbr>',
							'desc_tip'    => true,
							'description' => __( 'MPN refers to a Manufacturer Part Number, a number that uniquely identifies the product to its manufacturer.', 'customer-reviews-woocommerce' ),
						)
					);
				}
				if( $this->brand ) {
					woocommerce_wp_text_input(
						array(
							'id'          => '_cr_brand',
							'value'       => $product_object->get_meta( '_cr_brand', true, 'edit' ),
							'label'       => '<abbr title="' . esc_attr__( 'Brand', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'Brand', 'customer-reviews-woocommerce' ) . '</abbr>',
							'desc_tip'    => true,
							'description' => __( 'The brand of the product.', 'customer-reviews-woocommerce' ),
						)
					);
				}
				if( $this->identifier_exists ) {
					woocommerce_wp_checkbox(
						array(
							'id'          => '_cr_identifier_exists',
							'value'       => $product_object->get_meta( '_cr_identifier_exists', true, 'edit' ) ? 'yes' : 'no',
							'label'       => '<abbr title="' . esc_attr__( 'identifier_exists attribute for Google Shopping', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'identifier_exists', 'customer-reviews-woocommerce' ) . '</abbr>',
							'description' => __( 'Enable the checkbox to add "identifier_exists = no" in Google Shopping feed for this product.', 'customer-reviews-woocommerce' ),
						)
					);
				}
				if( $this->multipack ) {
					woocommerce_wp_text_input(
						array(
							'id'          => '_cr_multipack',
							'value'       => $product_object->get_meta( '_cr_multipack', true, 'edit' ),
							'label'       => '<abbr title="' . esc_attr__( 'Multipack attribute for Google Shopping', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'Multipack', 'customer-reviews-woocommerce' ) . '</abbr>',
							'desc_tip'    => true,
							'description' => __( 'If this product is a multipack, use the multipack attribute to indicate how many products you’ve grouped together.', 'customer-reviews-woocommerce' )
						)
					);
				}
				if( $this->bundle ) {
					$bundle_value = $product_object->get_meta( '_cr_bundle', true, 'edit' );
					if(
						'yes' !== $bundle_value &&
						'no' !== $bundle_value
					) {
						$bundle_value = '';
					}
					woocommerce_wp_select(
						array(
							'id'          => '_cr_bundle',
							'value'       => $bundle_value,
							'options'     => array(
								'yes' => __( 'Yes', 'customer-reviews-woocommerce' ),
								'no'  => __( 'No', 'customer-reviews-woocommerce' ),
								''    => __( 'Default', 'customer-reviews-woocommerce' )
							),
							'label'       => '<abbr title="' . esc_attr__( 'is_bundle attribute for Google Shopping', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'is_bundle', 'customer-reviews-woocommerce' ) . '</abbr>',
							'desc_tip'    => true,
							'description' => __( 'If this product is a bundle, use the is_bundle attribute to flag it as bundle in the XML Product Feed for Google Shopping.', 'customer-reviews-woocommerce' )
						)
					);
				}
			}
		}

		public function display_general_fields() {
			global $product_object;
			if ( $product_object ) {
				if( $this->material ) {

					echo '<div class="options_group">';

					woocommerce_wp_text_input(
						array(
							'id'          => '_cr_material',
							'value'       => $product_object->get_meta( '_cr_material', true, 'edit' ),
							'label'       => '<abbr title="' . esc_attr__( 'Material attribute for Google Shopping', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'Material', 'customer-reviews-woocommerce' ) . '</abbr>',
							'desc_tip'    => true,
							'description' => __( 'Use the material attribute to describe the main fabric or material that your product is made of.', 'customer-reviews-woocommerce' )
						)
					);

					echo '</div>';
				}
			}
		}

		public function save_fields( $product ) {
			if( $this->gtin ) {
				$product->update_meta_data( '_cr_gtin', isset( $_POST['_cr_gtin'] ) ? wc_clean( wp_unslash( $_POST['_cr_gtin'] ) ) : null );
			}
			if( $this->mpn ) {
				$product->update_meta_data( '_cr_mpn', isset( $_POST['_cr_mpn'] ) ? wc_clean( wp_unslash( $_POST['_cr_mpn'] ) ) : null );
			}
			if( $this->brand ) {
				$product->update_meta_data( '_cr_brand', isset( $_POST['_cr_brand'] ) ? wc_clean( wp_unslash( $_POST['_cr_brand'] ) ) : null );
			}
			if( $this->identifier_exists ) {
				$product->update_meta_data( '_cr_identifier_exists', ! empty( $_POST['_cr_identifier_exists'] ) );
			}
			if( $this->material ) {
				$product->update_meta_data( '_cr_material', isset( $_POST['_cr_material'] ) ? wc_clean( wp_unslash( $_POST['_cr_material'] ) ) : null );
			}
			if( $this->multipack ) {
				$product->update_meta_data( '_cr_multipack', isset( $_POST['_cr_multipack'] ) ? intval( wc_clean( wp_unslash( $_POST['_cr_multipack'] ) ) ) : null );
			}
			if( $this->bundle ) {
				$product->update_meta_data( '_cr_bundle', ( isset( $_POST['_cr_bundle'] ) && $_POST['_cr_bundle'] ) ? wc_clean( wp_unslash( $_POST['_cr_bundle'] ) ) : null );
			}
		}

		public function display_fields_variation( $loop, $variation_data, $variation ) {
			$variation_object = wc_get_product( $variation->ID );
			$css_class = 'form-row-first';
			if( $this->gtin ) {
				woocommerce_wp_text_input(
					array(
						'id'          => "_cr_gtin_var{$loop}",
						'name'        => "_cr_gtin_var[{$loop}]",
						'value'       => $variation_object->get_meta( '_cr_gtin', true, 'edit' ),
						'label'       => '<abbr title="' . esc_attr__( 'Global Trade Item Number', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'GTIN', 'customer-reviews-woocommerce' ) . '</abbr>',
						'desc_tip'    => true,
						'description' => __( 'GTIN refers to a Global Trade Item Number, a globally unique number used to identify trade items, products, or services that can be purchased. GTIN is also an umbrella term that refers to UPC, EAN, JAN, and ISBN.', 'customer-reviews-woocommerce' ),
						'wrapper_class' => 'form-row ' . $css_class
					)
				);
				$css_class = 'form-row-last';
			}
			if( $this->mpn ) {
				woocommerce_wp_text_input(
					array(
						'id'          => "_cr_mpn_var{$loop}",
						'name'        => "_cr_mpn_var[{$loop}]",
						'value'       => $variation_object->get_meta( '_cr_mpn', true, 'edit' ),
						'label'       => '<abbr title="' . esc_attr__( 'Manufacturer Part Number', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'MPN', 'customer-reviews-woocommerce' ) . '</abbr>',
						'desc_tip'    => true,
						'description' => __( 'MPN refers to a Manufacturer Part Number, a number that uniquely identifies the product to its manufacturer.', 'customer-reviews-woocommerce' ),
						'wrapper_class' => 'form-row ' . $css_class
					)
				);
				if( 'form-row-last' === $css_class ) {
					$css_class = 'form-row-first';
				} else {
					$css_class = 'form-row-last';
				}
			}
			if( $this->brand ) {
				woocommerce_wp_text_input(
					array(
						'id'          => "_cr_brand_var{$loop}",
						'name'        => "_cr_brand_var[{$loop}]",
						'value'       => $variation_object->get_meta( '_cr_brand', true, 'edit' ),
						'label'       => '<abbr title="' . esc_attr__( 'Brand', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'Brand', 'customer-reviews-woocommerce' ) . '</abbr>',
						'desc_tip'    => true,
						'description' => __( 'The brand of the product.', 'customer-reviews-woocommerce' ),
						'wrapper_class' => 'form-row ' . $css_class
					)
				);
				if( 'form-row-last' === $css_class ) {
					$css_class = 'form-row-first';
				} else {
					$css_class = 'form-row-last';
				}
			}
			if( $this->multipack ) {
				woocommerce_wp_text_input(
					array(
						'id'          => "_cr_multipack_var{$loop}",
						'name'        => "_cr_multipack_var[{$loop}]",
						'value'       => $variation_object->get_meta( '_cr_multipack', true, 'edit' ),
						'label'       => '<abbr title="' . esc_attr__( 'Multipack attribute for Google Shopping', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'Multipack', 'customer-reviews-woocommerce' ) . '</abbr>',
						'desc_tip'    => true,
						'description' => __( 'If this product is a multipack, then use the multipack attribute to indicate how many products you’ve grouped together.', 'customer-reviews-woocommerce' ),
						'wrapper_class' => 'form-row ' . $css_class
					)
				);
				if( 'form-row-last' === $css_class ) {
					$css_class = 'form-row-first';
				} else {
					$css_class = 'form-row-last';
				}
			}
			if( $this->bundle ) {
				$bundle_var_value = $variation_object->get_meta( '_cr_bundle', true, 'edit' );
				if(
					'yes' !== $bundle_var_value &&
					'no' !== $bundle_var_value
				) {
					$bundle_var_value = '';
				}
				woocommerce_wp_select(
					array(
						'id'          => "_cr_bundle_var{$loop}",
						'name'        => "_cr_bundle_var[{$loop}]",
						'value'       => $bundle_var_value,
						'options'     => array(
							'yes' => __( 'Yes', 'customer-reviews-woocommerce' ),
							'no'  => __( 'No', 'customer-reviews-woocommerce' ),
							''    => __( 'Default', 'customer-reviews-woocommerce' )
						),
						'label'       => '<abbr title="' . esc_attr__( 'is_bundle attribute for Google Shopping', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'is_bundle', 'customer-reviews-woocommerce' ) . '</abbr>',
						'desc_tip'    => true,
						'description' => __( 'If this product is a bundle, use the is_bundle attribute to flag it as bundle in the XML Product Feed for Google Shopping.', 'customer-reviews-woocommerce' ),
						'wrapper_class' => 'form-row ' . $css_class
					)
				);
				if( 'form-row-last' === $css_class ) {
					$css_class = 'form-row-first';
				} else {
					$css_class = 'form-row-last';
				}
			}
			if( $this->material ) {
				woocommerce_wp_text_input(
					array(
						'id'          => "_cr_material_var{$loop}",
						'name'        => "_cr_material_var[{$loop}]",
						'value'       => $variation_object->get_meta( '_cr_material', true, 'edit' ),
						'label'       => '<abbr title="' . esc_attr__( 'Material attribute for Google Shopping', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'Material', 'customer-reviews-woocommerce' ) . '</abbr>',
						'desc_tip'    => true,
						'description' => __( 'Use the material attribute to describe the main fabric or material that your product is made of.', 'customer-reviews-woocommerce' ),
						'wrapper_class' => 'form-row ' . $css_class
					)
				);
			}
		}
		public function display_fields_variation_options( $loop, $variation_data, $variation ) {
			$variation_object = wc_get_product( $variation->ID );
			if( $this->identifier_exists ) {
				?>
				<label class="tips" data-tip="<?php esc_attr_e( 'Enable the option to add "identifier_exists = no" in Google Shopping feed for this variation.', 'customer-reviews-woocommerce' ); ?>">
					<?php esc_html_e( 'identifier_exists', 'customer-reviews-woocommerce' ); ?>
					<input type="checkbox" class="checkbox cr_variable_identifier_exists" name="_cr_identifier_exists_var[<?php echo esc_attr( $loop ); ?>]" <?php checked( $variation_object->get_meta( '_cr_identifier_exists', true, 'edit' ), true ); ?> />
				</label>
				<?php
			}
		}
		public function save_fields_variation( $variation, $i ) {
			if( $this->gtin ) {
				$variation->update_meta_data( '_cr_gtin', isset( $_POST['_cr_gtin_var'][$i] ) ? wc_clean( wp_unslash( $_POST['_cr_gtin_var'][$i] ) ) : null );
			}
			if( $this->mpn ) {
				$variation->update_meta_data( '_cr_mpn', isset( $_POST['_cr_mpn_var'][$i] ) ? wc_clean( wp_unslash( $_POST['_cr_mpn_var'][$i] ) ) : null );
			}
			if( $this->brand ) {
				$variation->update_meta_data( '_cr_brand', isset( $_POST['_cr_brand_var'][$i] ) ? wc_clean( wp_unslash( $_POST['_cr_brand_var'][$i] ) ) : null );
			}
			if( $this->identifier_exists ) {
				$variation->update_meta_data( '_cr_identifier_exists', ! empty( $_POST['_cr_identifier_exists_var'][$i] ) );
			}
			if( $this->multipack ) {
				$variation->update_meta_data( '_cr_multipack', isset( $_POST['_cr_multipack_var'][$i] ) ? intval( wc_clean( wp_unslash( $_POST['_cr_multipack_var'][$i] ) ) ) : null );
			}
			if( $this->bundle ) {
				$variation->update_meta_data( '_cr_bundle', ( isset( $_POST['_cr_bundle_var'][$i] ) && $_POST['_cr_bundle_var'][$i] ) ? wc_clean( wp_unslash( $_POST['_cr_bundle_var'][$i] ) ) : null );
			}
			if( $this->material ) {
				$variation->update_meta_data( '_cr_material', isset( $_POST['_cr_material_var'][$i] ) ? wc_clean( wp_unslash( $_POST['_cr_material_var'][$i] ) ) : null );
			}
		}
	}

endif;
