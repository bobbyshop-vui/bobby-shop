<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Reviews_Grid' ) ) {

	/**
	* Class for reviews grid shortcode and block.
	*/
	final class CR_Reviews_Grid {

		private static $sort_order_by;
		private static $sort_order;
		private $min_chars;
		private $attributes;
		private $ivrating = 'ivrating';

		/**
		* Constructor.
		*
		* @since 3.61
		*/
		public function __construct() {
			$this->register_shortcode();
			add_action( 'init', array( 'CR_Reviews_Grid', 'cr_register_blocks_script' ) );
			add_action( 'init', array( $this, 'register_block' ) );
			add_action( 'enqueue_block_assets', array( 'CR_Reviews_Grid', 'cr_enqueue_block_scripts' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'maybe_print_wc_settings' ) );
			add_action( 'wp_ajax_ivole_fetch_product_categories', array( $this, 'fetch_product_categories' ) );
			add_action( 'wp_ajax_ivole_fetch_products', array( $this, 'fetch_products' ) );
			add_action( 'wp_ajax_cr_fetch_product_tags', array( $this, 'fetch_product_tags' ) );
			add_action( 'wp_ajax_ivole_show_more_grid_reviews', array( $this, 'show_more_reviews' ) );
			add_action( 'wp_ajax_nopriv_ivole_show_more_grid_reviews', array( $this, 'show_more_reviews' ) );
			if ( class_exists( 'WP_Block_Editor_Context' ) ) {
				add_filter( 'block_editor_settings_all', array( $this, 'add_block_editor_settings' ), 10, 2 );
			} else {
				add_filter( 'block_editor_settings', array( $this, 'add_block_editor_settings' ), 10, 2 );
			}
		}

		/**
		* Add the cusrev_reviews_grid shortcode.
		*
		* @since 3.61
		*/
		public function register_shortcode() {
			add_shortcode( 'cusrev_reviews_grid', array( $this, 'render_reviews_grid_shortcode' ) );
		}

		/**
		* Register the reviews-grid.
		*
		* @since 3.61
		*/
		public function register_block() {
			// Only register the block if the WP is at least 5.0, or gutenberg is installed.
			if ( function_exists( 'register_block_type' ) ) {
				register_block_type(
					dirname( dirname( dirname( __FILE__ ) ) ) . '/blocks/build/reviews-grid',
					array(
						'render_callback' => array( $this, 'render_reviews_grid' )
					)
				);
			}
		}

		/**
		* Returns the review grid markup.
		*
		* @since 3.61
		*
		* @param array $attributes Block attributes.
		*
		* @return string
		*/
		public function render_reviews_grid( $attributes ) {
			wp_enqueue_script( 'cr-colcade' );
			$this->attributes = $attributes;

			if( !isset( $attributes['count_total'] ) ) {
				$attributes['count_total'] = 0;
			}
			// count_total is used for "Show more" button
			// count_total is the combined maximum number of reviews that we would like to display after click on "Show more"
			if( 0 < $attributes['count_total'] ) {
				if( 0 < $attributes['count'] ) {
					$attributes['count'] = $attributes['count_total'];
				}
				if( 0 < $attributes['count_shop_reviews'] ) {
					$attributes['count_shop_reviews'] = $attributes['count_total'];
				}
			}
			$max_reviews = $attributes['count'];
			$order_by = $attributes['sort_by'] === 'date' ? 'comment_date_gmt' : 'rating';
			if( 'rating' === $attributes['sort_by'] ) {
				$order_by = 'rating';
			} else if ( 'media' === $attributes['sort_by'] ) {
				$order_by = 'media';
			} else {
				$order_by = 'comment_date_gmt';
			}
			$order = $attributes['sort'];
			$inactive_products = $attributes['inactive_products'];
			$avatars = 'initials';
			if( isset( $attributes['avatars'] ) ) {
				if( 'false' === $attributes['avatars'] || false == $attributes['avatars'] ) {
					$avatars = false;
				} elseif( 'standard' === $attributes['avatars'] ) {
					$avatars = 'standard';
				}
			}
			$show_more = $attributes['show_more'];
			$max_shop_reviews = 0;
			$show_products = $attributes['show_products'];
			$product_links = $attributes['product_links'];
			if( isset( $attributes['comment__not_in'] ) && is_array( $attributes['comment__not_in'] ) ) {
				$comment__not_in = $attributes['comment__not_in'];
			} else {
				$comment__not_in = array();
			}

			$post_ids = $attributes['products'];
			// add products if product tags are selected
			if( !empty( $attributes['product_tags'] ) ) {
				$tagged_products = CR_Reviews_Slider::cr_products_by_tags( $attributes['product_tags'] );
				$post_ids = array_merge( $post_ids, $tagged_products );
			}
			if ( count( $attributes['categories'] ) > 0 ) {
				$post_ids = get_posts(
					array(
						'post_type' => 'product',
						'posts_per_page' => -1,
						'fields' => 'ids',
						'post__in' => $attributes['products'],
						'tax_query' => array(
							array(
								'taxonomy' => 'product_cat',
								'field'    => 'term_id',
								'terms'    => $attributes['categories']
							),
						)
					)
				);
			}

			$comment_in = array();
			// tag names in the shortcode scenario
			if ( isset( $attributes['tags'] ) && $attributes['tags'] ) {
				$tags = array();
				foreach ( $attributes['tags'] as $tag_name ) {
					if ( $tag_name ) {
						$tag = get_term_by( 'name', $tag_name, 'cr_tag' );
						if ( $tag && $tag instanceof WP_Term ) {
							$tags[] = $tag->term_id;
						}
					}
				}
				if ( $tags ) {
					$comment_in = get_objects_in_term( $tags, 'cr_tag' );
					if ( ! is_wp_error( $comment_in ) && $comment_in ) {
						$comment_in = array_map( 'intval', $comment_in );
					} else {
						$comment_in = array();
					}
				}
			}
			// tag ids in the block scenario
			if (
				! $comment_in &&
				isset( $attributes['tag_ids'] ) &&
				$attributes['tag_ids']
			) {
				$comment_in = get_objects_in_term( $attributes['tag_ids'], 'cr_tag' );
				if ( ! is_wp_error( $comment_in ) && $comment_in ) {
					$comment_in = array_map( 'intval', $comment_in );
				} else {
					$comment_in = array();
				}
			}

			$args = array(
				'status'      => 'approve',
				'post_type'   => 'product',
				'meta_key'    => 'rating',
				'orderby'     => $order_by,
				'post__in'    => $post_ids,
				'comment__in' => $comment_in,
				'comment__not_in' => $comment__not_in
			);

			if( 'media' === $order_by ) {
				$args['meta_query'] = array(
					array(
						'relation' => 'OR',
						'cr_order_by_media_ne' => array(
							'key' => 'ivole_media_count',
							'type' => 'NUMERIC',
							'compare' => 'NOT EXISTS'
						),
						'cr_order_by_media_e' => array(
							'key' => 'ivole_media_count',
							'type' => 'NUMERIC',
							'compare' => 'EXISTS'
						)
					)
				);

				$args['orderby'] = array(
					'cr_order_by_media_ne',
					'comment_date_gmt'
				);
			}

			if( !$inactive_products ) {
				$args['post_status'] = 'publish';
			}

			if( get_query_var( $this->ivrating ) ) {
				$rating = intval( get_query_var( $this->ivrating ) );
				if( $rating > 0 && $rating <= 5 ) {
					$args['meta_query']['relation'] = 'AND';
					$args['meta_query'][] = array(
						'key' => 'rating',
						'value'   => $rating,
						'compare' => '=',
						'type'    => 'numeric'
					);
				}
			}

			if ( function_exists( 'pll_current_language' ) ) {
				// Polylang compatibility
				$args['lang'] = '';
			} elseif ( has_filter( 'wpml_current_language' ) ) {
				// WPML compatibility
				global $sitepress;
				if ( $sitepress ) {
					remove_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ), 10, 2 );
				}
			}

			$reviews = [];
			// Query needs to be modified if min_chars constraints are set
			if ( ! empty( $attributes['min_chars'] ) ) {
				$this->min_chars = $attributes['min_chars'];
				add_filter( 'comments_clauses', array( $this, 'min_chars_comments_clauses' ) );
			}
			if( 'RAND' === $order ) {
				$all_product_reviews = get_comments( $args );
				$count_all_product_reviews = count( $all_product_reviews );
				if( 0 < $count_all_product_reviews ) {
					$max_reviews = ( $count_all_product_reviews < $max_reviews ) ? $count_all_product_reviews : $max_reviews;
					$random_keys = array_rand( $all_product_reviews, $max_reviews );
					if( is_array( $random_keys ) ) {
						for( $i = 0; $i < $max_reviews; $i++ ) {
							$reviews[] = $all_product_reviews[$random_keys[$i]];
						}
					} else {
						$reviews[] = $all_product_reviews[$random_keys];
					}
				}
			} else {
				if( 0 < $max_reviews ) {
					$args['order'] = $order;
					$args['number'] = $max_reviews;
					$reviews = get_comments( $args );
				}
			}

			$shop_page_id = wc_get_page_id( 'shop' );
			if( true === $attributes['shop_reviews'] ) {
				$max_shop_reviews = $attributes['count_shop_reviews'];
				if( $shop_page_id > 0 && $max_shop_reviews > 0 ) {
					$args_s = array(
						'status'      => 'approve',
						'post_status' => 'publish',
						'post_id'			=> $shop_page_id,
						'meta_key'    => 'rating',
						'orderby'     => $order_by,
						'comment__in' => $comment_in,
						'comment__not_in' => $comment__not_in
					);

					if( get_query_var( $this->ivrating ) ) {
						$rating = intval( get_query_var( $this->ivrating ) );
						if( $rating > 0 && $rating <= 5 ) {
							$args_s['meta_query'][] = array(
								'key' => 'rating',
								'value'   => $rating,
								'compare' => '=',
								'type'    => 'numeric'
							);
						}
					}

					$shop_reviews = [];
					if( 'RAND' === $order ) {
						$all_shop_reviews = get_comments( $args_s );
						$count_all_shop_reviews = count( $all_shop_reviews );
						if( 0 < $count_all_shop_reviews ) {
							$max_shop_reviews = ( $count_all_shop_reviews < $max_shop_reviews ) ? $count_all_shop_reviews : $max_shop_reviews;
							$random_keys = array_rand( $all_shop_reviews, $max_shop_reviews );
							if( is_array( $random_keys ) ) {
								for( $i = 0; $i < $max_shop_reviews; $i++ ) {
									$shop_reviews[] = $all_shop_reviews[$random_keys[$i]];
								}
							} else {
								$shop_reviews[] = $all_shop_reviews[$random_keys];
							}
						}
					} else {
						if( 0 < $max_shop_reviews ) {
							$args_s['order'] = $order;
							$args_s['number'] = $max_shop_reviews;
							$shop_reviews = get_comments( $args_s );
						}
					}

					if( is_array( $reviews ) && is_array( $shop_reviews ) ) {
						$reviews = array_merge( $reviews, $shop_reviews );
						CR_Reviews_Grid::$sort_order_by = $order_by;
						CR_Reviews_Grid::$sort_order = $order;
						usort( $reviews, array( "CR_Reviews_Grid", "compare_dates_sort" ) );
					}
				}
			}
			remove_filter( 'comments_clauses', array( $this, 'min_chars_comments_clauses' ) );

			// WPML compatibility
			if( has_filter( 'wpml_current_language' ) && ! function_exists( 'pll_current_language' ) ) {
				global $sitepress;
				if ( $sitepress ) {
					add_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ), 10, 2 );
				}
			}

			$num_reviews = count( $reviews );

			// make sure that we do not return more reviews than necessary
			if( 0 < $attributes['count_total'] ) {
				if( $num_reviews > $attributes['count_total'] ) {
					$reviews_temp = array();
					while( count( $reviews_temp ) < $attributes['count_total'] ) {
						$randomKey = mt_rand( 0, $num_reviews-1 );
						$reviews_temp[] = $reviews[$randomKey];
					}
					$reviews = $reviews_temp;
				}
			}

			$cr_verified_label = get_option( 'ivole_verified_owner', '' );
			if( $cr_verified_label ) {
				if ( function_exists( 'pll__' ) ) {
					$verified_text = esc_html( pll__( $cr_verified_label ) );
				} else {
					$verified_text = esc_html( $cr_verified_label );
				}
			} else {
				$verified_text = esc_html__( 'Verified owner', 'customer-reviews-woocommerce' );
			}

			$badge_link = 'https://www.cusrev.com/reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ) . '/p/p-%s/r-%s';
			$badge = '<p class="ivole-verified-badge"><img src="' . plugins_url( '/img/shield-20.png', dirname( dirname( __FILE__ ) ) ) . '" alt="' . __( 'Verified review', 'customer-reviews-woocommerce' ) . '" class="ivole-verified-badge-icon">';
			$badge .= '<span class="ivole-verified-badge-text">';
			$badge .= __( 'Verified review', 'customer-reviews-woocommerce' );
			$badge .= ' - <a href="' . $badge_link . '" title="" target="_blank" rel="nofollow noopener">' . __( 'view original', 'customer-reviews-woocommerce' ) . '</a>';
			$badge .= '</span></p>';

			$badge_link_sr = 'https://www.cusrev.com/reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ) . '/s/r-%s';
			$badge_sr = '<p class="ivole-verified-badge"><img src="' . plugins_url( '/img/shield-20.png', dirname( dirname( __FILE__ ) ) ) . '" alt="' . __( 'Verified review', 'customer-reviews-woocommerce' ) . '" class="ivole-verified-badge-icon">';
			$badge_sr .= '<span class="ivole-verified-badge-text">';
			$badge_sr .= __( 'Verified review', 'customer-reviews-woocommerce' );
			$badge_sr .= ' - <a href="' . $badge_link_sr . '" title="" target="_blank" rel="nofollow noopener">' . __( 'view original', 'customer-reviews-woocommerce' ) . '</a>';
			$badge_sr .= '</span></p>';

			$section_style = "border-color:" . $attributes['color_ex_brdr'] . ";";
			if ( ! empty( $attributes['color_ex_bcrd'] ) ) {
				$section_style .= "background-color:" . $attributes['color_ex_bcrd'] . ";";
			}
			$card_style = "border-color:" . $attributes['color_brdr'] . ";";
			$card_style .= "background-color:" . $attributes['color_bcrd'] . ";";
			$product_style = "background-color:" . $attributes['color_pr_bcrd'] . ";";
			$stars_style = "color:" . $attributes['color_stars'] . ";";

			$id = uniqid( 'cr-reviews-grid-' );

			// display credits
			$cr_credits_line = '';
			if ('yes' !== get_option('ivole_reviews_nobranding', 'yes')) {
				$cr_credits_line = '<div class="cr-credits-div">';
				$cr_credits_line .= '<span>Powered by</span><a href="https://wordpress.org/plugins/customer-reviews-woocommerce/" target="_blank" alt="Customer Reviews for WooCommerce" title="Customer Reviews for WooCommerce"><img src="' . plugins_url( '/img/logo-vs.svg', dirname( dirname( __FILE__ ) ) ) . '" alt="CusRev"></a>';
				$cr_credits_line .= '</div>';
			}

			// add review form
			$review_form = '';
			if ( $attributes['add_review'] ) {
				$review_form = CR_All_Reviews::show_add_review_form( $attributes['add_review'] );
			}

			// display a summary bar
			$summary_bar = '';
			if ( $attributes['show_summary_bar'] || $attributes['add_review'] ) {
				if( !empty($args_s) ) $summary_bar = $this->show_summary_table( $args, $args_s );
				else $summary_bar = $this->show_summary_table( $args );
			}

			// display incentivized badges
			$incentivized_setting = CR_Review_Discount_Settings::get_incentivized_badge_setting();
			$incentivized_label = '';
			if (
				$incentivized_setting &&
				is_array( $incentivized_setting ) &&
				isset( $incentivized_setting['bdg'] ) &&
				isset( $incentivized_setting['lbl'] ) &&
				'yes' === $incentivized_setting['bdg']
			) {
				$incentivized_label = $incentivized_setting['lbl'];
			}

			$template = wc_locate_template(
				'reviews-grid.php',
				'customer-reviews-woocommerce',
				dirname( dirname( dirname( __FILE__ ) ) ) . '/templates/'
			);

			if( 'initials' === $attributes['avatars'] ) {
				add_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ), 10, 5 );
			}
			ob_start();
			include( $template );
			if( 'initials' === $attributes['avatars'] ) {
				remove_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ) );
			}

			return ob_get_clean();
		}

		public function render_reviews_grid_shortcode( $attributes ) {
			$shortcode_enabled = get_option( 'ivole_reviews_shortcode', 'no' );
			if( $shortcode_enabled === 'no' ) {
				return;
			} else {
				// Convert shortcode attributes to block attributes
				$attributes = shortcode_atts( array(
					'count' => 3,
					'show_products' => true,
					'product_links' => true,
					'sort_by' => 'date',
					'sort' => 'DESC',
					'categories' => array(),
					'products' => 'current',
					'color_ex_brdr' => '#ebebeb',
					'color_brdr' => '#ebebeb',
					'color_ex_bcrd' => '',
					'color_bcrd' => '#ffffff',
					'color_pr_bcrd' => '#f4f4f4',
					'color_stars' => '#FFD707',
					'shop_reviews' => 'false',
					'count_shop_reviews' => 1,
					'inactive_products' => 'false',
					'avatars' => 'initials',
					'show_more' => 0,
					'count_total' => 0,
					'product_tags' => [],
					'tags' => [],
					'min_chars' => 0,
					'show_summary_bar' => 'false',
					'add_review' => 'false',
					'comment__not_in' => []
				), $attributes, 'cusrev_reviews_grid' );

				$attributes['count'] = absint( $attributes['count'] );
				$attributes['show_products'] = ( $attributes['show_products'] !== 'false' && boolval( $attributes['count'] ) );
				$attributes['product_links'] = ( $attributes['product_links'] !== 'false' );
				$attributes['shop_reviews'] = ( $attributes['shop_reviews'] !== 'false' && boolval( $attributes['count_shop_reviews'] ) );
				$attributes['count_shop_reviews'] = absint( $attributes['count_shop_reviews'] );
				$attributes['inactive_products'] = ( $attributes['inactive_products'] === 'true' );
				$attributes['show_more'] = absint( $attributes['show_more'] );
				$attributes['count_total'] = absint( $attributes['count_total'] );
				$attributes['min_chars'] = intval( $attributes['min_chars'] );
				$attributes['show_summary_bar'] = ( $attributes['show_summary_bar'] === 'true' );
				if( $attributes['min_chars'] < 0 ) {
					$attributes['min_chars'] = 0;
				}

				if ( ! is_array( $attributes['categories'] ) ) {
					$attributes['categories'] = array_filter( array_map( 'trim', explode( ',', $attributes['categories'] ) ) );
				}

				if (
					is_string( $attributes['products'] ) &&
					'current' === trim( strtolower( $attributes['products'] ) )
				) {
					if ( is_product() ) {
						$product = wc_get_product();
						if ( is_object( $product ) ) {
							$id = $product->get_id();
							$attributes['products'] = array( $id );
						} else {
							$attributes['products'] = array();
						}
					} else {
						$attributes['products'] = array();
					}
				} elseif ( ! is_array( $attributes['products'] ) ) {
					$products = str_replace( ' ', '', $attributes['products'] );
					$products = explode( ',', $products );
					$products = array_filter( $products, 'is_numeric' );
					$products = array_map( 'intval', $products );

					$attributes['products'] = $products;
				} else {
					$attributes['products'] = array_map( 'intval', $attributes['products'] );
				}

				if(
					! empty( $attributes['product_tags'] ) &&
			 		! is_array( $attributes['product_tags'] )
				) {
					$attributes['product_tags'] = array_filter( array_map( 'trim', explode( ',', $attributes['product_tags'] ) ) );
				}

				if(
					! empty( $attributes['tags'] ) &&
			 		! is_array( $attributes['tags'] )
				) {
					$attributes['tags'] = array_filter( array_map( 'trim', explode( ',', $attributes['tags'] ) ) );
				}

				if ( 'true' === $attributes['add_review'] ) {
					$product_id = CR_All_Reviews::is_it_a_product_page();
					if ( $product_id ) {
						$attributes['add_review'] = $product_id;
					} else {
						$attributes['add_review'] = true;
					}
				} elseif ( is_numeric( $attributes['add_review'] ) ) {
					$attributes['add_review'] = intval( $attributes['add_review'] );
				} else {
					$attributes['add_review'] = false;
				}

				return $this->render_reviews_grid( $attributes );
			}
		}

		/**
		* When displaying the block editor, check for WooCommerce support then set
		* an action to print wc data.
		*
		* @since 3.61
		*/
		public function maybe_print_wc_settings() {
			if ( ! function_exists( 'wc_get_theme_support' ) ) {
				return;
			}

			add_action( 'admin_print_footer_scripts', array( $this, 'print_settings' ), 1 );
		}

		/**
		* Print JS variables to the editor page with WC data.
		*
		* @since 3.61
		*/
		public function print_settings() {
			global $wp_locale;

			$code = get_woocommerce_currency();

			// NOTE: wcSettings is not used directly, it's only for @woocommerce/components
			//
			// Settings and variables can be passed here for access in the app.
			// Will need `wcAdminAssetUrl` if the ImageAsset component is used.
			// Will need `dataEndpoints.countries` if Search component is used with 'country' type.
			// Will need `orderStatuses` if the OrderStatus component is used.
			// Deliberately excluding: `embedBreadcrumbs`, `trackingEnabled`.
			$settings = array(
				'adminUrl'      => admin_url(),
				'wcAssetUrl'    => plugins_url( 'assets/', WC_PLUGIN_FILE ),
				'siteLocale'    => esc_attr( get_bloginfo( 'language' ) ),
				'currency'      => array(
					'code'      => $code,
					'precision' => wc_get_price_decimals(),
					'symbol'    => get_woocommerce_currency_symbol( $code ),
					'position'  => get_option( 'woocommerce_currency_pos' ),
				),
				'stockStatuses' => wc_get_product_stock_status_options(),
				'siteTitle'     => get_bloginfo( 'name' ),
				'dataEndpoints' => array(),
				'l10n'          => array(
					'userLocale'    => get_user_locale(),
					'weekdaysShort' => array_values( $wp_locale->weekday_abbrev ),
				),
			);

			// Global settings used in each block.
			$block_settings = array(
				'min_columns'       => wc_get_theme_support( 'product_grid::min_columns', 1 ),
				'max_columns'       => wc_get_theme_support( 'product_grid::max_columns', 6 ),
				'default_columns'   => wc_get_default_products_per_row(),
				'min_rows'          => wc_get_theme_support( 'product_grid::min_rows', 1 ),
				'max_rows'          => wc_get_theme_support( 'product_grid::max_rows', 6 ),
				'default_rows'      => wc_get_default_product_rows_per_page(),
				'placeholderImgSrc' => wc_placeholder_img_src(),
				'min_height'        => wc_get_theme_support( 'featured_block::min_height', 500 ),
				'default_height'    => wc_get_theme_support( 'featured_block::default_height', 500 ),
			);

			?>
			<script type="text/javascript">
			var wcSettings = wcSettings || <?php echo wp_json_encode( $settings ); ?>;
			var wc_product_block_data = <?php echo wp_json_encode( $block_settings ); ?>;
			</script>
			<?php
		}

		/**
		* Fetch the product categories for use by the reviews grid block settings.
		*
		* @since 3.61
		*/
		public function fetch_product_categories() {
			$prepared_args = array(
				'exclude'    => [],
				'include'    => [],
				'order'      => 'asc',
				'orderby'    => 'name',
				'product'    => null,
				'hide_empty' => false,
				'number'     => 100,
				'offset'     => 0
			);

			$query_result = get_terms( 'product_cat', $prepared_args );

			$response = array();
			foreach ( $query_result as $term ) {
				$response[] = array(
					'id'     => (int) $term->term_id,
					'name'   => $term->name,
					'slug'   => $term->slug,
					'parent' => (int) $term->parent,
					'count'  => (int) $term->count
				);
			}

			wp_send_json( $response, 200 );
		}

		/**
		* Fetch the products for use by the reviews grid block settings.
		*
		* @since 3.61
		*/
		public function fetch_products() {
			$query_args = array(
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post_type'      => 'product',
				'orderby'        => 'date ID'
			);

			$query = new WP_Query();
			$products = $query->query( $query_args );
			$products = array_map( 'wc_get_product', $products );

			$response = array();
			foreach ( $products as $product ) {
				$response[] = array(
					'id'   => $product->get_id(),
					'name' => $product->get_name(),
					'slug' => $product->get_slug()
				);
			}

			wp_send_json( $response );
		}

		/**
		* Fetch the product tags for use by the reviews block settings.
		*
		* @since 4.6
		*/
		public function fetch_product_tags() {
			$args = array(
				'order'      => 'asc',
				'orderby'    => 'name',
				'hide_empty' => false,
			);

			$terms = get_terms( 'product_tag', $args );

			$response = [];
			foreach ( $terms as $term ) {
				$response[] = array(
					'id'     => (int) $term->term_id,
					'name'   => $term->name,
					'slug'   => $term->slug,
				);
			}

			wp_send_json( $response, 200 );
		}

		public static function cr_register_blocks_script() {
			wp_register_script(
				'cr-frontend-js',
				plugins_url('/js/frontend.js', dirname( dirname( __FILE__ ) ) ),
				array(),
				Ivole::CR_VERSION,
				true
			);
			wp_register_script(
				'cr-colcade',
				plugins_url( '/js/colcade.js', dirname( dirname( __FILE__) ) ),
				array(),
				Ivole::CR_VERSION,
				true
			);
		}

		public static function cr_enqueue_block_scripts() {
			global $current_screen;
			$assets_version = Ivole::CR_VERSION;

			wp_register_style( 'cr-frontend-css', plugins_url( '/css/frontend.css', dirname( dirname( __FILE__ ) ) ), array(), $assets_version, 'all' );
			wp_enqueue_style( 'cr-frontend-css' );

			wp_register_style( 'cr-badges-css', plugins_url( '/css/badges.css', dirname( dirname( __FILE__ ) ) ), array(), $assets_version, 'all' );

			wp_localize_script(
				'cr-frontend-js',
				'cr_ajax_object',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				)
			);
			wp_enqueue_script( 'cr-frontend-js' );
			wp_enqueue_script( 'cr-colcade' );
		}

		private static function compare_dates_sort( $a, $b ) {
			if( 'rating' === CR_Reviews_Grid::$sort_order_by ) {
				$rating1 = intval( get_comment_meta( $a->comment_ID, 'rating', true ) );
				$rating2 = intval( get_comment_meta( $b->comment_ID, 'rating', true ) );
				if( 'ASC' === CR_Reviews_Grid::$sort_order ) {
					return $rating1 - $rating2;
				} elseif( 'RAND' === CR_Reviews_Grid::$sort_order ) {
					return rand( -1, 1 );
				} else {
					return $rating2 - $rating1;
				}
			} else if( 'media' === CR_Reviews_Grid::$sort_order_by ) {
				$media1 = intval( get_comment_meta( $a->comment_ID, 'ivole_media_count', true ) );
				$media2 = intval( get_comment_meta( $b->comment_ID, 'ivole_media_count', true ) );
				if( 'ASC' === CR_Reviews_Grid::$sort_order ) {
					return $media1 - $media2;
				} elseif( 'RAND' === CR_Reviews_Grid::$sort_order ) {
					return rand( -1, 1 );
				} else {
					return $media2 - $media1;
				}
			} else {
				$datetime1 = strtotime( $a->comment_date );
				$datetime2 = strtotime( $b->comment_date );
				if( 'ASC' === CR_Reviews_Grid::$sort_order ) {
					return $datetime1 - $datetime2;
				}  elseif( 'RAND' === CR_Reviews_Grid::$sort_order ) {
					return rand( -1, 1 );
				} else {
					return $datetime2 - $datetime1;
				}
			}
		}

		public function show_more_reviews() {
			$response = array();
			$attributes = array();
			if( isset( $_POST['attributes'] ) && is_array( $_POST['attributes'] ) ) $attributes = $_POST['attributes'];
			if( isset( $_POST['rating'] ) ) {
				$rating = intval( $_POST['rating'] );
				if( 0 < $rating && 5 >= $rating ) {
					set_query_var( $this->ivrating, $rating );
				} else {
					set_query_var( $this->ivrating, 0 );
				}
			}

			//Sanitization
			foreach($attributes as $key => $val) {

				switch($key){
					case 'inactive_products':
					case 'product_links':
					case 'shop_reviews':
					case 'show_products':
						$val = $val === "true" ? "true" : "false";
						break;
					case 'categories':
					case 'products':
					case 'comment__not_in':
						$new_val = array();
						if ( is_array( $val ) && count( $val ) ) {
							foreach( $val as $item ) {
								$new_val[] = absint( $item );
							}
							$val = $new_val;
						}
						break;
					case 'count':
					case 'count_shop_reviews':
					case 'show_more':
						$val = absint($val);
						break;
					case 'sort':
						$allowed = array( 'ASC', 'DESC', 'RAND' );
						if(!in_array($val, $allowed)) $val = 'DESC';
						break;
					case 'sort_by':
						$allowed = array( 'date', 'rating', 'media' );
						if( !in_array( $val, $allowed ) ) $val = 'date';
						break;
					case 'avatars':
						$allowed = array( 'initials', 'standard', 'false' );
						if( !in_array( $val, $allowed ) ) $val = 'initials';
						break;
					case 'product_tags':
						$new_val = array();
						if ( is_array( $val ) && count( $val ) ) {
							foreach( $val as $item ) {
								$new_val[] = strval( $item );
							}
							$val = $new_val;
						}
						break;
					case 'tags':
						$new_val = array();
						if ( is_array( $val ) && count( $val ) ) {
							foreach( $val as $item ) {
								$new_val[] = strval( $item );
							}
							$val = $new_val;
						}
						break;
					default:
						$val = sanitize_text_field( $val );
				}

				$attributes[$key] = $val;
			}

			$attributes['count_total'] = $attributes['show_more'];
			$response['html'] = $html = $this->render_reviews_grid_shortcode( $attributes );

			wp_send_json( $response );
		}

		public function min_chars_comments_clauses( $clauses ) {
			global $wpdb;

			$clauses['where'] .= " AND CHAR_LENGTH({$wpdb->comments}.comment_content) >= " . $this->min_chars;

			return $clauses;
		}

		private function show_summary_table( $args, $args_shop = array() ){
			$all = $this->count_ratings( 0, $args, $args_shop );
			$output = '';
			if ($all > 0) {
				$five = (float)$this->count_ratings( 5, $args, $args_shop );
				$five_percent = floor($five / $all * 100);
				$five_rounding = $five / $all * 100 - $five_percent;
				$four = (float)$this->count_ratings( 4, $args, $args_shop );
				$four_percent = floor($four / $all * 100);
				$four_rounding = $four / $all * 100 - $four_percent;
				$three = (float)$this->count_ratings( 3, $args, $args_shop );
				$three_percent = floor($three / $all * 100);
				$three_rounding = $three / $all * 100 - $three_percent;
				$two = (float)$this->count_ratings( 2, $args, $args_shop );
				$two_percent = floor($two / $all * 100);
				$two_rounding = $two / $all * 100 - $two_percent;
				$one = (float)$this->count_ratings( 1, $args, $args_shop );
				$one_percent = floor($one / $all * 100);
				$one_rounding = $one / $all * 100 - $one_percent;
				$hundred = $five_percent + $four_percent + $three_percent + $two_percent + $one_percent;
				if( $hundred < 100 ) {
					$to_distribute = 100 - $hundred;
					$roundings = array( '5' => $five_rounding, '4' => $four_rounding, '3' => $three_rounding, '2' => $two_rounding, '1' => $one_rounding );
					arsort($roundings);
					$roundings = array_filter( $roundings, function( $value ) {
						return $value > 0;
					} );
					while( $to_distribute > 0 && count( $roundings ) > 0 ) {
						foreach( $roundings as $key => $value ) {
							if( $to_distribute > 0 ) {
								switch( $key ) {
									case 5:
										$five_percent++;
										break;
									case 4:
										$four_percent++;
										break;
									case 3:
										$three_percent++;
										break;
									case 2:
										$two_percent++;
										break;
									case 1:
										$one_percent++;
										break;
									default:
										break;
								}
								$to_distribute--;
							} else {
								break;
							}
						}
					}
				}
				$average = ( 5 * $five + 4 * $four + 3 * $three + 2 * $two + 1 * $one ) / $all;
			} else {
				$five_percent = $five = 0;
				$four_percent = $four = 0;
				$three_percent = $three = 0;
				$two_percent = $two = 0;
				$one_percent = $one = 0;
				$average = 0;
			}
			$summary_box_classes = 'cr-summaryBox-wrap';
			if ( $this->attributes['add_review'] ) {
				$summary_box_classes .= ' cr-summaryBox-add-review';
			}
			$output .= '<div class="' . $summary_box_classes . '">';
			if ( $this->attributes['add_review'] ) {
				$output .= '<div class="cr-summary-separator-side"></div>';
			}
			$output .= '<div class="cr-overall-rating-wrap">';
			$output .= '<div class="cr-average-rating"><span>' . number_format_i18n( $average, 1 ) . '</span></div>';
			$output .= '<div class="cr-average-rating-stars"><div class="crstar-rating"><span style="width:'.($average / 5 * 100).'%;"></span></div></div>';
			$output .= '<div class="cr-total-rating-count">' . sprintf( _n( 'Based on %s review', 'Based on %s reviews', $all, 'customer-reviews-woocommerce' ), number_format_i18n( $all ) ) . '</div>';
			$output .= '</div>';
			$output .= '<div class="cr-summary-separator"><div class="cr-summary-separator-int"></div></div>';
			if( 0 < $this->attributes['show_more'] ) {
				$output .= '<div class="ivole-summaryBox cr-grid-reviews-ajax">';
			} else {
				$output .= '<div class="ivole-summaryBox">';
			}
			$output .= '<table id="ivole-histogramTable">';
			$output .= '<tbody>';
			$output .= '<tr class="ivole-histogramRow">';
			// five
			if( $five > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="cr-histogram-a" data-rating="5" href="' . esc_url( add_query_arg( $this->ivrating, 5 ) ) . '" title="' . __( '5 star', 'customer-reviews-woocommerce' ) . '">' . __( '5 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="cr-histogram-a" data-rating="5" href="' . esc_url( add_query_arg( $this->ivrating, 5 ) ) . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $five_percent . '%">' . $five_percent . '</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="cr-histogram-a" data-rating="5" href="' . esc_url( add_query_arg( $this->ivrating, 5 ) ) . '">' . (string)$five_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __('5 star', 'customer-reviews-woocommerce') . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $five_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$five_percent . '%</td>';
			}

			$output .= '</tr>';
			$output .= '<tr class="ivole-histogramRow">';
			// four
			if( $four > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="cr-histogram-a" data-rating="4" href="' . esc_url( add_query_arg( $this->ivrating, 4 ) ) . '" title="' . __( '4 star', 'customer-reviews-woocommerce' ) . '">' . __( '4 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="cr-histogram-a" data-rating="4" href="' . esc_url( add_query_arg( $this->ivrating, 4 ) ) . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $four_percent . '%">' . $four_percent . '</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="cr-histogram-a" data-rating="4" href="' . esc_url( add_query_arg( $this->ivrating, 4 ) ) . '">' . (string)$four_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __('4 star', 'customer-reviews-woocommerce') . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $four_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$four_percent . '%</td>';
			}

			$output .= '</tr>';
			$output .= '<tr class="ivole-histogramRow">';
			// three
			if( $three > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="cr-histogram-a" data-rating="3" href="' . esc_url( add_query_arg( $this->ivrating, 3 ) ) . '" title="' . __( '3 star', 'customer-reviews-woocommerce' ) . '">' . __( '3 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="cr-histogram-a" data-rating="3" href="' . esc_url( add_query_arg( $this->ivrating, 3 ) ) . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $three_percent . '%">' . $three_percent . '</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="cr-histogram-a" data-rating="3" href="' . esc_url( add_query_arg( $this->ivrating, 3 ) ) . '">' . (string)$three_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __('3 star', 'customer-reviews-woocommerce') . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $three_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$three_percent . '%</td>';
			}

			$output .= '</tr>';
			$output .= '<tr class="ivole-histogramRow">';
			// two
			if( $two > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="cr-histogram-a" data-rating="2" href="' . esc_url( add_query_arg( $this->ivrating, 2 ) ) . '" title="' . __( '2 star', 'customer-reviews-woocommerce' ) . '">' . __( '2 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="cr-histogram-a" data-rating="2" href="' . esc_url( add_query_arg( $this->ivrating, 2 ) ) . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $two_percent . '%">' . $two_percent . '</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="cr-histogram-a" data-rating="2" href="' . esc_url( add_query_arg( $this->ivrating, 2 ) ) . '">' . (string)$two_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __('2 star', 'customer-reviews-woocommerce') . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $two_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$two_percent . '%</td>';
			}

			$output .= '</tr>';
			$output .= '<tr class="ivole-histogramRow">';
			// one
			if( $one > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="cr-histogram-a" data-rating="1" href="' . esc_url( add_query_arg( $this->ivrating, 1 ) ) . '" title="' . __( '1 star', 'customer-reviews-woocommerce' ) . '">' . __( '1 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="cr-histogram-a" data-rating="1" href="' . esc_url( add_query_arg( $this->ivrating, 1 ) ) . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $one_percent . '%">' . $one_percent . '</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="cr-histogram-a" data-rating="1" href="' . esc_url( add_query_arg( $this->ivrating, 1 ) ) . '">' . (string)$one_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __('1 star', 'customer-reviews-woocommerce') . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $one_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$one_percent . '%</td>';
			}

			$output .= '</tr>';
			$output .= '</tbody>';
			$output .= '</table>';
			$output .= '</div>';

			if ( $this->attributes['add_review'] ) {
				$output .= '<div class="cr-summary-separator"><div class="cr-summary-separator-int"></div></div>';
				$output .= '<div class="cr-add-review-wrap">';
				$output .= '<button class="cr-all-reviews-add-review" type="button">' . __( 'Add a review', 'customer-reviews-woocommerce' ) . '</button>';
				$output .= '</div>';
				$output .= '<div class="cr-summary-separator-side"></div>';
			}

			if (get_query_var($this->ivrating)) {
				$rating = intval(get_query_var($this->ivrating));
				if ($rating > 0 && $rating <= 5) {
					$filtered_comments = sprintf(esc_html(_n('Showing %1$d of %2$d review (%3$d star). ', 'Showing %1$d of %2$d reviews (%3$d star). ', $all, 'customer-reviews-woocommerce')), $this->count_ratings( $rating, $args, $args_shop), $all, $rating);
					$all_comments = sprintf(esc_html(_n('See all %d review', 'See all %d reviews', $all, 'customer-reviews-woocommerce')), $all);
					$output .= '<div class="cr-count-filtered-reviews">' . $filtered_comments . '<a class="cr-seeAllReviews" href="' . esc_url( get_permalink() ) . '">' . $all_comments . '</a></div>';
				}
			} else {
				$output .= '<div class="cr-count-filtered-reviews"></div>';
			}
			$output .= '</div>';

			return $output;
		}

		private function count_ratings( $rating, $args, $args_shop = array() ) {
			$args['count'] = true;
			$args['type__not_in'] = 'cr_qna';
			$args['parent'] = 0;
			unset($args['meta_query']);

			if ($rating > 0) {
				$args['meta_query'][] = array(
					'key' => 'rating',
					'value'   => $rating,
					'compare' => '=',
					'type'    => 'numeric'
				);
			}

			if ( ! empty( $this->min_chars ) ) {
				add_filter( 'comments_clauses', array( $this, 'min_chars_comments_clauses' ) );
			}
			$count = get_comments( $args );

			if( !empty( $args_shop ) ){
				$args_shop['count'] = true;
				$args_shop['type__not_in'] = 'cr_qna';
				$args_shop['parent'] = 0;
				unset($args_shop['meta_query']);

				if ($rating > 0) {
					$args_shop['meta_query'][] = array(
						'key' => 'rating',
						'value'   => $rating,
						'compare' => '=',
						'type'    => 'numeric'
					);
				}

				$count_shop = get_comments($args_shop);
				$count = $count + $count_shop;
			}
			remove_filter( 'comments_clauses', array( $this, 'min_chars_comments_clauses' ) );

			return $count;
		}

		public static function cr_get_avatar( $avatar, $id_or_email, $size = 96, $default = '', $alt = '' ) {
			if ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
				$id_or_email = get_comment( $id_or_email );
				$author = trim( mb_ereg_replace( '[\.,]', ' ', get_comment_author( $id_or_email ) ) );
				if( 0 < mb_strlen( $author ) ) {
					$initials = mb_substr( $author, 0, 1 );
					$words = mb_split( '\s+', $author );
					if( 1 < count( $words ) ) {
						$initials .= mb_substr( $words[1], 0, 1 );
					}
					$initials = mb_strtoupper( $initials );
					if ( ! $alt ) {
						$alt = $initials;
					}

					$svg_template = '
						<svg width="%d" height="%d" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
							<rect x="0" y="0" width="%d" height="%d" style="fill: #CCD1D4"></rect>
							<text x="50%%" y="50%%" dy=".1em" fill="#4D5D64" text-anchor="middle" dominant-baseline="middle" style="font-family: sans-serif; font-size: %d; line-height: 1">%s</text>
						</svg>
					';

					$svg_avatar_check = '
						<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24">
							<path fill="#FFFFFF" d="m10 16.4l-4-4L7.4 11l2.6 2.6L16.6 7L18 8.4Z"/>
						</svg>
					';

					$svg = sprintf( $svg_template, $size, $size, $size, $size, $size/2, $initials );

					$avatar = sprintf( '<img alt="%s" src="%s" width="%d" height="%d" class="%s"><div class="cr-avatar-check">%s</div>', $alt, 'data:image/svg+xml;base64,' . base64_encode( $svg ), $size, $size, 'avatar', $svg_avatar_check );
				}
			}
			return $avatar;
		}

		public function add_block_editor_settings( $settings, $p ) {
			$settings['cusrev'] = array(
				'reviews_shortcodes' => ( get_option( 'ivole_reviews_shortcode', 'no' ) !== 'no' )
			);
			return $settings;
		}

	}

}
