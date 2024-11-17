<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_StructuredData' ) ) :

	class CR_StructuredData {

		private $identifiers;
		private $static_brand;

		public function __construct() {
			$this->identifiers = get_option( 'ivole_product_feed_identifiers', array(
				'pid'   => '',
				'gtin'  => '',
				'mpn'   => '',
				'brand' => ''
			) );
			if( is_array( $this->identifiers ) && 'yes' === get_option( 'ivole_product_feed_enable_id_str_dat', 'no' ) ) {
				if( ( isset( $this->identifiers['gtin'] ) && $this->identifiers['gtin'] )
					|| ( isset( $this->identifiers['mpn'] ) && $this->identifiers['mpn'] )
					|| (isset( $this->identifiers['brand'] ) && $this->identifiers['brand'] ) ) {

					$this->static_brand = trim( get_option( 'ivole_google_brand_static', '' ) );
					add_filter( 'woocommerce_structured_data_product', array( $this, 'filter_woocommerce_structured_data_product' ), 10, 2 );
					add_action( 'woocommerce_product_meta_end', array( $this, 'action_woocommerce_structured_data_review' ) );
					add_filter( 'woocommerce_available_variation', array( $this, 'filter_woocommerce_available_variation'), 10, 3 );
				}
			}
			if( 'yes' == get_option( 'ivole_attach_image', 'no' ) ) {
				if( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '3.0.0' ) >= 0 ) {
					add_filter( 'woocommerce_structured_data_review', array( $this, 'filter_woocommerce_structured_data_review' ), 10, 2 );
				}
			}
		}

		public function filter_woocommerce_structured_data_review( $markup, $comment ) {
			$pics = get_comment_meta( $comment->comment_ID, 'ivole_review_image' );
			$pics_n = count( $pics );
			if( $pics_n > 0 ) {
				//error_log( print_r( $comment, true ) );
				$markup['associatedMedia']  = array();
				for( $i = 0; $i < $pics_n; $i ++) {
					$markup['associatedMedia'][]  = array(
						'@type' => 'ImageObject',
						'name' => sprintf( __( 'Image #%1$d from ', 'customer-reviews-woocommerce' ), $i + 1 ) . $comment->comment_author,
						'contentUrl' => $pics[$i]['url']
					);
				}
			}
			return $markup;
		}

		public function filter_woocommerce_structured_data_product( $markup, $product ) {
			if( isset( $this->identifiers['gtin'] ) ) {
				$gtin = CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['gtin'], $product );
				$gtin_lenth = mb_strlen( $gtin );
				switch( $gtin_lenth ) {
					case 8:
						$markup['gtin8'] = $gtin;
						break;
					case 12:
						$markup['gtin12'] = $gtin;
						break;
					case 13:
						$markup['gtin13'] = $gtin;
						break;
					case 14:
						$markup['gtin14'] = $gtin;
						break;
					default:
						$markup['gtin'] = $gtin;
						break;
				}
			}
			if( isset( $this->identifiers['mpn'] ) ) {
				$mpn = CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['mpn'], $product );
				if( $mpn ) {
					$markup['mpn'] = $mpn;
				}
			}
			if( isset( $this->identifiers['brand'] ) ) {
				$brand = CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['brand'], $product );
				if( !$brand ) {
					$brand = $this->static_brand;
				}
				if( $brand ) {
					$markup['brand'] = array(
						'@type' => 'Brand',
						'name' => $brand
					);
				}
			}

			return $markup;
		}

		public function action_woocommerce_structured_data_review() {
			global $product;
			$space = apply_filters( 'cr_productids_separator', '<br>' );
			if( isset( $this->identifiers['gtin'] ) ) {
				$gtin = CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['gtin'], $product );
				if( !$gtin ) {
					// if variable product, check if any variation has gtin
					if( $product->is_type( 'variable' ) ) {
						$available_variations = wc_get_products( array(
							'parent' => $product->get_id(),
							'status' => 'publish',
							'type' => 'variation',
							'limit' => -1
						) );
						foreach ( $available_variations as $variation )
						{
							if( CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['gtin'], $variation ) ) {
								$gtin = __( 'N/A', 'woocommerce' );
								break;
							}
						}
					}
				}
				if( $gtin ) {
					echo $space . '<span class="cr_gtin" data-o_content="' . $gtin . '">' . __( 'GTIN: ', 'customer-reviews-woocommerce' ) .
						'<span class="cr_gtin_val">' . $gtin . '</span></span>';
				}
			}
			if( isset( $this->identifiers['mpn'] ) ) {
				$mpn = CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['mpn'], $product );
				if( !$mpn ) {
					// if variable product, check if any variation has mpn
					if( $product->is_type( 'variable' ) ) {
						$available_variations = wc_get_products( array(
							'parent' => $product->get_id(),
							'status' => 'publish',
							'type' => 'variation',
							'limit' => -1
						) );
						foreach ( $available_variations as $variation )
						{
							if( CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['mpn'], $variation ) ) {
								$mpn = __( 'N/A', 'woocommerce' );
								break;
							}
						}
					}
				}
				if( $mpn ) {
					echo $space . '<span class="cr_mpn" data-o_content="' . $mpn . '">' . __( 'MPN: ', 'customer-reviews-woocommerce' ) .
						'<span class="cr_mpn_val">' . $mpn . '</span></span>';
				}
			}
			if( isset( $this->identifiers['brand'] ) ) {
				$brand = CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['brand'], $product );
				if( !$brand ) {
					$brand = $this->static_brand;
				}
				if( $brand ) {
					echo $space . '<span class="cr_brand" data-o_content="' . $brand . '">' . __( 'Brand: ', 'customer-reviews-woocommerce' ) .
						'<span class="cr_brand_val">' . $brand . '</span></span>';
				}
			}
		}

		/**
		* @var $variations array
		* @var $product WC_Product_Variable
		* @var $variation WC_Product_Variation
		*
		* @return array
		*/
		public function filter_woocommerce_available_variation( $variations, $product, $variation ) {
			if( isset( $this->identifiers['gtin'] ) ) {
				$gtin = CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['gtin'], $variation );
				if( $gtin ) {
					$variations['_cr_gtin'] = $gtin;
				} else {
					$gtin = CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['gtin'], $product );
					if( $gtin ) {
						$variations['_cr_gtin'] = $gtin;
					}
				}
			}
			if( isset( $this->identifiers['mpn'] ) ) {
				$mpn = CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['mpn'], $variation );
				if( $mpn ) {
					$variations['_cr_mpn'] = $mpn;
				} else {
					$mpn = CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['mpn'], $product );
					if( $mpn ) {
						$variations['_cr_mpn'] = $mpn;
					}
				}
			}
			if( isset( $this->identifiers['brand'] ) ) {
				$brand = CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['brand'], $variation );
				if( $brand ) {
					$variations['_cr_brand'] = $brand;
				} else {
					$brand = CR_Google_Shopping_Prod_Feed::get_field( $this->identifiers['brand'], $product );
					if( $brand ) {
						$variations['_cr_brand'] = $brand;
					} else {
						$brand = $this->static_brand;
						if( $brand ) {
							$variations['_cr_brand'] = $brand;
						}
					}
				}
			}
			return $variations;
		}

	}

endif;
