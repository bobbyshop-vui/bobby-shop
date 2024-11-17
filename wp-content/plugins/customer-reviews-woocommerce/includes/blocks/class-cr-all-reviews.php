<?php

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('CR_All_Reviews')) :

	class CR_All_Reviews
	{
		private $shortcode_atts;
		private $shop_page_id;
		private $ivrating = 'ivrating';
		private $crsearch = 'crsearch';
		private $search = '';
		private $tags = array();
		private $default_per_page = 10;
		private $page = 0;

		public function __construct() {
			$this->register_shortcode();
			$this->shop_page_id = wc_get_page_id( 'shop' );
			add_action( 'wp_enqueue_scripts', array( $this, 'cr_style_1' ) );
			add_action( 'wp_ajax_cr_show_more_all_reviews', array( $this, 'show_more_reviews' ) );
			add_action( 'wp_ajax_nopriv_cr_show_more_all_reviews', array( $this, 'show_more_reviews' ) );
			add_action( 'wp_ajax_cr_submit_review', array( $this, 'submit_review' ) );
			add_action( 'wp_ajax_nopriv_cr_submit_review', array( $this, 'submit_review' ) );
			add_action( 'wp_ajax_cr_upload_media', array( $this, 'upload_media_files' ) );
			add_action( 'wp_ajax_nopriv_cr_upload_media', array( $this, 'upload_media_files' ) );
			add_action( 'wp_ajax_cr_delete_media', array( $this, 'delete_media_files' ) );
			add_action( 'wp_ajax_nopriv_cr_delete_media', array( $this, 'delete_media_files' ) );
		}

		public function register_shortcode() {
			add_shortcode( 'cusrev_all_reviews', array( $this, 'render_all_reviews_shortcode' ) );
		}

		private function fill_attributes( $attributes ) {
			$defaults = array(
				'sort' => 'desc',
				'sort_by' => 'date',
				'per_page' => $this->default_per_page,
				'show_summary_bar' => 'true',
				'show_media' => 'true',
				'show_pictures' => 'false',
				'show_products' => 'true',
				'categories' => [],
				'products' => [],
				'product_reviews' => 'true',
				'shop_reviews' => 'true',
				'inactive_products' => 'false',
				'show_replies' => 'false',
				'product_tags' => [],
				'tags' => [],
				'show_more' => 5,
				'min_chars' => 0,
				'avatars' => 'initials',
				'users' => 'all',
				'add_review' => false
			);

			if ( isset( $attributes['categories'] ) && ! is_array( $attributes['categories'] ) ) {
				$categories = str_replace( ' ', '', $attributes['categories'] );
				$categories = explode( ',', $categories );
				$categories = array_filter( $categories, 'is_numeric' );
				$categories = array_map( 'intval', $categories );

				$attributes['categories'] = $categories;
			}

			if ( ! isset( $attributes['products'] ) ) {
				$attributes['products'] = 'current';
			}
			if ( isset( $attributes['products'] ) ) {
				if (
					is_string( $attributes['products'] ) &&
					'current' === trim( strtolower( $attributes['products'] ) )
				) {
					$product_id = self::is_it_a_product_page();
					if ( $product_id ) {
						$attributes['products'] = array( $product_id );
					} else {
						$attributes['products'] = array();
					}
				} elseif ( ! is_array( $attributes['products'] ) ) {
					$products = str_replace( ' ', '', $attributes['products'] );
					$products = explode( ',', $products );
					$products = array_filter( $products, 'is_numeric' );
					$products = array_map( 'intval', $products );

					$attributes['products'] = $products;
				}
			}

			if( ! empty( $attributes['product_tags'] ) && !is_array( $attributes['product_tags'] ) ) {
				$attributes['product_tags'] = array_filter( array_map( 'trim', explode( ',', $attributes['product_tags'] ) ) );
				$tagged_products = CR_Reviews_Slider::cr_products_by_tags( $attributes['product_tags'] );
				$attributes['products'] = array_merge( $attributes['products'], $tagged_products );
			}

			if ( ! empty( $attributes['tags'] ) && ! is_array( $attributes['tags'] ) ) {
				$attributes['tags'] = array_filter( array_map( 'trim', explode( ',', $attributes['tags'] ) ) );
			}

			$this->shortcode_atts = shortcode_atts( $defaults, $attributes );
			$this->shortcode_atts['show_summary_bar'] = $this->shortcode_atts['show_summary_bar'] === 'true' ? true : false;
			$this->shortcode_atts['show_media'] = $this->shortcode_atts['show_media'] === 'true' ? true : false;
			$this->shortcode_atts['show_pictures'] = $this->shortcode_atts['show_pictures'] === 'true' ? true : false;
			$this->shortcode_atts['show_products'] = $this->shortcode_atts['show_products'] === 'true' ? true : false;
			$this->shortcode_atts['product_reviews'] = $this->shortcode_atts['product_reviews'] === 'true' ? true : false;
			$this->shortcode_atts['shop_reviews'] = $this->shortcode_atts['shop_reviews'] === 'true' ? true : false;
			$this->shortcode_atts['inactive_products'] = $this->shortcode_atts['inactive_products'] === 'true' ? true : false;
			$this->shortcode_atts['show_replies'] = $this->shortcode_atts['show_replies'] === 'true' ? true : false;
			$this->shortcode_atts['sort'] = strtolower( $this->shortcode_atts['sort'] );
			$this->shortcode_atts['sort_by'] = strtolower( $this->shortcode_atts['sort_by'] );
			$this->shortcode_atts['show_more'] = absint( $this->shortcode_atts['show_more'] );
			if( !empty( $this->shortcode_atts['show_more'] ) ) {
				$this->shortcode_atts['per_page'] = $this->shortcode_atts['show_more'];
			}
			$this->shortcode_atts['min_chars'] = intval( $this->shortcode_atts['min_chars'] );
			if( $this->shortcode_atts['min_chars'] < 0 ) {
				$this->shortcode_atts['min_chars'] = 0;
			}
			if(
				$this->shortcode_atts['avatars'] !== 'standard' &&
				$this->shortcode_atts['avatars'] !== 'hidden'
			) {
				$this->shortcode_atts['avatars'] = 'initials';
			}
			$this->shortcode_atts['users'] = strtolower( $this->shortcode_atts['users'] );
			if( 'current' !== $this->shortcode_atts['users'] ) {
				$this->shortcode_atts['users'] = 'all';
			}
			if ( 'true' === $this->shortcode_atts['add_review'] ) {
				$product_id = self::is_it_a_product_page();
				if ( $product_id ) {
					$this->shortcode_atts['add_review'] = $product_id;
				} else {
					$this->shortcode_atts['add_review'] = true;
				}
			} elseif ( is_numeric( $this->shortcode_atts['add_review'] ) ) {
				$this->shortcode_atts['add_review'] = intval( $this->shortcode_atts['add_review'] );
			} else {
				$this->shortcode_atts['add_review'] = false;
			}
		}

		public function render_all_reviews_shortcode( $attributes ) {
			if ( ! is_array( $attributes ) ) {
				// if the shortcode is used without parameters, $attributes will be an empty string
				$attributes = array();
			}
			$this->fill_attributes( $attributes );
			return $this->display_reviews();
		}

		public function get_reviews() {
			$comments = array();
			$reviews_top_level = 0;
			$reviews_w_media = array();
			$reviews_w_tags = array();

			if ( $this->shortcode_atts['product_reviews'] || $this->shortcode_atts['shop_reviews'] ) {
				// tags
				$comment_in = array();
				$reviews_by_tags = '';
				if ( 0 < count( $this->tags ) ) {
					$tags_objects = get_objects_in_term( $this->tags, 'cr_tag' );
					if (
						$tags_objects &&
						! is_wp_error( $tags_objects ) &&
						is_array( $tags_objects ) &&
						0 < count( $tags_objects )
					) {
						$reviews_by_tags = $tags_objects;
					}
				}

				$limit_per_page = $this->shortcode_atts['show_more'];
				if ( 0 >= $limit_per_page ) {
					if ( 0 < $this->shortcode_atts['per_page'] ) {
						$limit_per_page = $this->shortcode_atts['per_page'];
					} else {
						$limit_per_page = $this->default_per_page;
					}
				}

				$args = array(
					'number'       => $limit_per_page,
					'status'       => 'approve',
					'orderby'      => 'comment_date_gmt',
					'order'        => $this->shortcode_atts['sort'],
					'type__not_in' => 'cr_qna',
					'offset'       => $this->page * $limit_per_page,
					'cache_domain' => $this->get_cache_domain()
				);
				// filter by the current user if 'users' parameter was provided in the shortcode
				if ( 'current' === $this->shortcode_atts['users'] ) {
					$current_user = get_current_user_id();
					if ( 0 < $current_user ) {
						$args['user_id'] = $current_user;
					}
				}
				//
				if( $this->shortcode_atts['sort_by'] === 'helpful' ) {
					$args['meta_query'] = array(
						'cr_helpful' => array(
							'relation' => 'OR',
							'cr_helpful1' => array(
								'key' => 'ivole_review_votes',
								'type' => 'NUMERIC',
								'compare' => 'NOT EXISTS'
							),
							'cr_helpful2' => array(
								'key' => 'ivole_review_votes',
								'type' => 'NUMERIC',
								'compare' => 'EXISTS'
							)
						)
					);

					$args['orderby'] = array(
						'cr_helpful1' => $this->shortcode_atts['sort'],
						'comment_date_gmt' => $this->shortcode_atts['sort']
					);
				}

				// search
				$args['search'] = $this->search;

				// tags passed in the shortcode parameters
				if ( isset( $this->shortcode_atts['tags'] ) && $this->shortcode_atts['tags'] ) {
					$tags = array();
					foreach ( $this->shortcode_atts['tags'] as $tag_name ) {
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

				// filter by tags product reviews via UI
				if ( $reviews_by_tags ) {
					$comment_in = array_merge( $comment_in, $reviews_by_tags );
				}

				$args['comment__in'] = $comment_in;

				if ( ! $this->shortcode_atts['inactive_products'] ) {
					$args['post_status'] = 'publish';
				}

				$is_filtered_by_rating = false;
				if ( get_query_var( $this->ivrating ) ) {
					$rating = intval( get_query_var( $this->ivrating ) );
					if ( $rating > 0 && $rating <= 5 ) {
						$args['meta_query']['relation'] = 'AND';
						$args['meta_query']['cr_rating_filter'] = array(
							'key' => 'rating',
							'value'   => $rating,
							'compare' => '=',
							'type'    => 'numeric'
						);
						$is_filtered_by_rating = true;
					}
				}

				if ( ! $is_filtered_by_rating ) {
					$args['meta_query']['relation'] = 'AND';
					$args['meta_query']['cr_rating_exists'] = array(
						'key' => 'rating',
						'compare' => 'EXISTS',
						'type' => 'numeric'
					);
				}

				// Query needs to be modified if min_chars constraints are set
				if ( ! empty( $this->shortcode_atts['min_chars'] ) ) {
					add_filter( 'comments_clauses', array( $this, 'min_chars_comments_clauses' ) );
				}
				// Query needs to be modified if category constraints are set
				if ( ! empty( $this->shortcode_atts['categories'] ) ) {
					add_filter( 'comments_clauses', array( $this, 'modify_comments_clauses' ) );
				}
				if ( function_exists( 'pll_current_language' ) ) {
					// Polylang compatibility
					$args['lang'] = '';
				} elseif ( has_filter( 'wpml_current_language' ) ) {
					// WPML compatibility
					// Check for the 'show reviews in all languages' setting of WPML
					$is_filtered = apply_filters(
						'wpml_is_comment_query_filtered',
						true,
						null,
						(object) array( 'query_vars' => array( 'post_type' => 'product' ) )
					);
					if ( false === $is_filtered ) {
						foreach ( $this->shortcode_atts['products'] as $product_id ) {
							$trid = apply_filters( 'wpml_element_trid', NULL, $product_id, 'post_product' );
							if ( $trid ) {
								$translations = apply_filters( 'wpml_get_element_translations', NULL, $trid, 'post_product' );
								if ( $translations && is_array( $translations ) ) {
									foreach ( $translations as $translation ) {
										if ( isset( $translation->element_id ) ) {
											$this->shortcode_atts['products'][] = intval( $translation->element_id );
										}
									}
								}
							}
						}
					}
					//
					global $sitepress;
					if ( $sitepress ) {
						remove_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ), 10, 2 );
					}
				}

				// a filter to merge product and shop reviews
				add_filter( 'comments_clauses', array( $this, 'merge_comments_clauses' ) );

				// check if there are any featured product reviews
				$args_f = $args;
				$args_f['meta_query']['cr_featured'] = array(
					'key' => 'ivole_featured',
					'compare' => '>',
					'value' => '0',
					'type' => 'NUMERIC'
				);
				$featured_reviews = get_comments( $args_f );

				if ( 0 < count( $featured_reviews ) ) {
					$featured_reviews = array_map( function( $fr ) {
							$fr->comment_karma = 1;
							return $fr;
						},
						$featured_reviews
					);
				}

				$args_f['offset'] = 0;
				$args_f['number'] = '';
				$all_featured_reviews = get_comments( $args_f );
				$count_featured = count( $all_featured_reviews );
				if ( 0 < $count_featured ) {
					if ( $args['offset'] < $count_featured ) {
						$args['offset'] = 0;
					} else {
						$args['offset'] = $args['offset'] - $count_featured;
					}
					$args['comment__not_in'] = array_map( function( $fr ) { return $fr->comment_ID; }, $all_featured_reviews );
				}

				// get product reviews (limited to per page)
				$comments = get_comments( $args );
				// get count of top level product reviews (excluding replies)
				$args_top_level = $args;
				$args_top_level['parent'] = 0;
				$args_top_level['offset'] = 0;
				$args_top_level['comment__not_in'] = '';
				$args_top_level['count'] = true;
				$args_top_level['number'] = '';
				unset( $args_top_level['meta_query']['cr_helpful'] );
				$args_top_level['orderby'] = 'comment_date_gmt';
				$reviews_top_level = get_comments( $args_top_level );
				// get product reviews with media attachments (limited to max number of media that can be displayed at the top)
				$args_media = $args;
				$args_media['parent'] = 0;
				$args_media['offset'] = 0;
				$args_media['comment__not_in'] = '';
				$args_media['number'] = CR_Reviews::get_max_top_images();
				$args_media['meta_key'][] = CR_Reviews::REVIEWS_META_IMG;
				$args_media['meta_key'][] = CR_Reviews::REVIEWS_META_LCL_IMG;
				$args_media['meta_key'][] = CR_Reviews::REVIEWS_META_VID;
				$args_media['meta_key'][] = CR_Reviews::REVIEWS_META_LCL_VID;
				$reviews_w_media = get_comments( $args_media );
				// get product reviews with tags
				$args_tags = $args;
				$args_tags['parent'] = 0;
				$args_tags['offset'] = 0;
				$args_tags['comment__not_in'] = '';
				$args_tags['number'] = '';
				add_filter( 'comments_clauses', array( $this, 'tags_comments_clauses' ) );
				$reviews_w_tags = get_comments( $args_tags );
				remove_filter( 'comments_clauses', array( $this, 'tags_comments_clauses' ) );

				remove_filter( 'comments_clauses', array( $this, 'merge_comments_clauses' ) );

				// WPML compatibility
				if( has_filter( 'wpml_current_language' ) && ! function_exists( 'pll_current_language' ) ) {
					global $sitepress;
					if ( $sitepress ) {
						add_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ), 10, 2 );
					}
				}
				//
				remove_filter( 'comments_clauses', array( $this, 'modify_comments_clauses' ) );
				remove_filter( 'comments_clauses', array( $this, 'min_chars_comments_clauses' ) );

				if( 0 < count( $featured_reviews ) ) {
					$comments = array_merge( $featured_reviews, $comments );
				}

				//highlight search results for products
				if( !empty( $this->search ) ) {
					$highlight = $this->search;
					$comments = array_map( function( $item ) use( $highlight ) {
						$item->comment_content = preg_replace( '/(' . $highlight . ')(?![^<>]*\/>)/iu', '<span class="cr-search-highlight">\0</span>', $item->comment_content );
						return $item;
					}, $comments );
				}

				// include review replies after application of filters
				if ( $this->shortcode_atts['show_replies'] ) {
					$comments = $this->include_review_replies( $comments );
				}
			}

			return array(
				'reviews' => $comments,
				'top' => $reviews_top_level,
				'media' => $reviews_w_media,
				'tags' => $reviews_w_tags
			);
		}

		public function display_reviews() {
			$page = 1;
			$per_page = $this->shortcode_atts['per_page'];

			if( 0  == $per_page ) {
				$per_page = $this->default_per_page;
			}

			$rating = 0;

			$return = '<div class="cr-all-reviews-shortcode" data-attributes="' . wc_esc_json( wp_json_encode( $this->shortcode_atts ) ) . '">';

			// add credits
			if ('yes' !== get_option('ivole_reviews_nobranding', 'yes')) {
				$return .= '<div class="cr-credits-div">';
				$return .= '<span>Powered by</span><a href="https://wordpress.org/plugins/customer-reviews-woocommerce/" target="_blank" alt="Customer Reviews for WooCommerce" title="Customer Reviews for WooCommerce"><img src="' . plugins_url( '/img/logo-vs.svg', dirname( dirname( __FILE__ ) ) ) . '" alt="CusRev"></a>';
				$return .= '</div>';
			}

			// add review form
			if ( $this->shortcode_atts['add_review'] ) {
				$return .= self::show_add_review_form( $this->shortcode_atts['add_review'] );
			}

			$reviews_array = $this->get_reviews();
			$comments = $reviews_array['reviews'];
			$top_comments_count = $reviews_array['top'];
			$comments_media = $reviews_array['media'];
			$comments_tags = $reviews_array['tags'];

			// show summary bar
			if ( $this->shortcode_atts['show_summary_bar'] || $this->shortcode_atts['add_review'] ) {
				$return .= $this->show_summary_table();
			}

			// show media files uploaded by customers
			if ( $this->shortcode_atts['show_media'] ) {
				$return .= CR_Reviews::display_review_images_top( $comments_media );
			}

			$return .= CR_Ajax_Reviews::get_search_field( true );

			// show tags
			$return .= CR_Ajax_Reviews::get_tags_field( $comments_tags );

			// show count of reviews
			$return .= $this->show_count_row( $top_comments_count, $page, $per_page, 0 == $this->shortcode_atts['show_more'], 0, 0 );

			if( 0 >= count( $comments ) ) {
				$return .= '<p class="cr-search-no-reviews">' . esc_html__( 'Sorry, no reviews match your current selections', 'customer-reviews-woocommerce' ) . '</p>';
				$return .= '</div>';
				return $return;
			}

			$hide_avatars = 'hidden' === $this->shortcode_atts['avatars'] ? true : false;

			$return .= '<ol class="commentlist">';
			if ( 'initials' === $this->shortcode_atts['avatars'] ) {
				add_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ), 10, 5 );
			}
			$return .= wp_list_comments( apply_filters('ivole_product_review_list_args', array(
				'callback' => array( 'CR_Reviews', 'callback_comments' ),
				'page'  => 1,
				'per_page' => $per_page,
				'reverse_top_level' => false,
				'echo' => false,
				'cr_show_products' => $this->shortcode_atts['show_products'],
				'cr_hide_avatars' => $hide_avatars
			)), $comments );
			if ( 'initials' === $this->shortcode_atts['avatars'] ) {
				remove_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ) );
			}
			$return .= '<span class="cr-pagination-review-spinner"></span>';
			$return .= '</ol>';

			if ( $this->shortcode_atts['show_more'] == 0 ) {
				$pages = ceil( $top_comments_count / $per_page );
				// echo the pagination
				$return .= '<div class="cr-all-reviews-pagination">';
				$return .= self::cr_paginate_links($page, $pages);
				$return .= '</div>';
			} else {
				if( $this->shortcode_atts['show_more'] < $top_comments_count ) {
					$return .= '<button id="cr-show-more-all-reviews" class="cr-show-more-button" type="button" data-page="1">';
					$return .=  sprintf( __( 'Show more reviews (%d)', 'customer-reviews-woocommerce' ), $top_comments_count - $this->shortcode_atts['show_more'] );
					$return .= '</button>';
				}
			}
			$return .= '<span class="cr-show-more-review-spinner" style="display:none;"></span>';
			$return .= '<p class="cr-search-no-reviews" style="display:none">' . esc_html__( 'Sorry, no reviews match your current selections', 'customer-reviews-woocommerce' ) . '</p>';

			$return .= '</div>';

			return $return;
		}

		public function show_more_reviews() {
			$attributes = array();
			$rating = 0;
			$all = 0;
			if ( isset( $_POST['attributes'] ) && is_array( $_POST['attributes'] ) ) {
				$attributes = $_POST['attributes'];
			}
			//search
			if ( isset( $_POST['search'] ) && ! empty( trim( $_POST['search'] ) ) ) {
				$this->search = sanitize_text_field( trim( $_POST['search'] ) );
			}
			$this->fill_attributes($attributes);

			// sort
			if ( isset( $_POST['sort'] ) ) {
				if( 'helpful' === $_POST['sort'] ) {
					$this->shortcode_atts['sort_by'] = 'helpful';
				} else {
					$this->shortcode_atts['sort_by'] = 'date';
				}
			}

			// filter by rating
			if ( isset( $_POST['rating'] ) ) {
				$rating = intval( $_POST['rating'] );
				if ( 0 < $rating && 5 >= $rating ) {
					set_query_var( $this->ivrating, $rating );
					$all = $this->count_ratings(0);
				} else {
					$rating = 0;
				}
			}

			// tags
			if (
				isset( $_POST['tags'] ) &&
				is_array( $_POST['tags'] ) &&
				count( $_POST['tags'] ) > 0
			) {
				$this->tags = array_map( 'intval', $_POST['tags'] );
			}

			$page = intval( $_POST['page'] ) + 1;
			$html = "";
			$pagination_required = false;
			$pagination = "";
			$this->page = $page - 1;
			$reviews_array = $this->get_reviews();
			$comments = $reviews_array['reviews'];
			$top_comments_count = $reviews_array['top'];

			$per_page = $this->shortcode_atts['show_more'];
			if ( 0 >= $per_page ) {
				if ( 0 < $this->shortcode_atts['per_page'] ) {
					$per_page = $this->shortcode_atts['per_page'];
				} else {
					$per_page = $this->default_per_page;
				}
				$pagination_required = true;
			}

			$count_pages = ceil( $top_comments_count / $per_page );

			if ( $pagination_required ) {
				$pagination = self::cr_paginate_links( $page, $count_pages );
			}

			$hide_avatars = 'hidden' === $this->shortcode_atts['avatars'] ? true : false;

			if ( 'initials' === $this->shortcode_atts['avatars'] ) {
				add_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ), 10, 5 );
			}
			$html .= wp_list_comments( apply_filters( 'ivole_product_review_list_args', array(
				'callback' => array( 'CR_Reviews', 'callback_comments' ),
				'page'  => 1,
				'per_page' => $per_page,
				'reverse_top_level' => false,
				'echo' => false,
				'cr_show_products' => $this->shortcode_atts['show_products'],
				'cr_hide_avatars' => $hide_avatars
			) ), $comments );
			if ( 'initials' === $this->shortcode_atts['avatars'] ) {
				remove_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ) );
			}

			$last_page = false;
			if ( $count_pages <= $page ) {
				$last_page = true;
			}

			wp_send_json( array(
				'page' => $page,
				'html' => $html,
				'last_page' => $last_page,
				'show_more_label' => sprintf( __( 'Show more reviews (%d)', 'customer-reviews-woocommerce' ), $top_comments_count - $page * $per_page ),
				'count_row' => self::get_count_wording(
					$top_comments_count,
					$page,
					$per_page,
					$pagination_required,
					$rating,
					$all
				),
				'pagination' => $pagination
			) );
		}

		private function enqueue_wc_script( $handle, $path = '', $deps = array( 'jquery' ), $version = WC_VERSION, $in_footer = true ) {
			if ( ! wp_script_is( $handle, 'registered' ) ) {
				wp_register_script( $handle, $path, $deps, $version, $in_footer );
			}
			if ( ! wp_script_is( $handle ) ) {
				wp_enqueue_script( $handle );
			}
		}

		private function enqueue_wc_style( $handle, $path = '', $deps = array(), $version = WC_VERSION, $media = 'all', $has_rtl = false ) {
			if ( ! wp_style_is( $handle, 'registered' ) ) {
				wp_register_style( $handle, $path, $deps, $version, $media );
			}
			if ( ! wp_style_is( $handle ) ) {
				wp_enqueue_style( $handle );
			}
		}

		public function cr_style_1()
		{
			if ( is_singular() && ! is_product() ) {
				$assets_version = Ivole::CR_VERSION;
				$disable_lightbox = 'yes' === get_option( 'ivole_disable_lightbox', 'no' ) ? true : false;
				// Load gallery scripts on product pages only if supported.
				if ( 'yes' === get_option( 'ivole_attach_image', 'no' ) || 'yes' === get_option( 'ivole_form_attach_media', 'no' ) ) {
					if ( ! $disable_lightbox ) {
						$this->enqueue_wc_script( 'photoswipe-ui-default' );
						$this->enqueue_wc_style( 'photoswipe-default-skin' );
						add_action( 'wp_footer', array( $this, 'cr_photoswipe' ) );
					}
				}

				wp_register_style( 'cr-frontend-css', plugins_url( '/css/frontend.css', dirname( dirname( __FILE__ ) ) ), array(), $assets_version, 'all' );
				wp_register_script( 'cr-frontend-js', plugins_url( '/js/frontend.js', dirname( dirname( __FILE__) ) ), array( 'jquery' ), $assets_version, true );
				wp_register_script( 'cr-colcade', plugins_url( '/js/colcade.js', dirname( dirname( __FILE__) ) ), array(), $assets_version, true );
				wp_enqueue_style( 'cr-frontend-css' );
				wp_localize_script(
					'cr-frontend-js',
					'cr_ajax_object',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'disable_lightbox' => ( $disable_lightbox ? 1 : 0 )
					)
				);
				wp_enqueue_script( 'cr-frontend-js' );
			}
		}

		private function count_ratings( $rating ) {
			$count = 0;
			if ( $this->shortcode_atts['product_reviews'] || $this->shortcode_atts['shop_reviews'] ) {
				// tags passed in the shortcode parameters
				$comment_in = array();
				if ( isset( $this->shortcode_atts['tags'] ) && $this->shortcode_atts['tags'] ) {
					$tags = array();
					foreach ( $this->shortcode_atts['tags'] as $tag_name ) {
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
				$args = array(
					'number'       => '',
					'status'       => 'approve',
					'parent'       => 0,
					'count'        => true,
					'type__not_in' => 'cr_qna',
					'comment__in'  => $comment_in,
					'meta_key'     => 'rating',
					'cache_domain' => $this->get_cache_domain()
				);
				// filter by the current user if 'users' parameter was provided in the shortcode
				if ( 'current' === $this->shortcode_atts['users'] ) {
					$current_user = get_current_user_id();
					if ( 0 < $current_user ) {
						$args['user_id'] = $current_user;
					}
				}
				//
				if ( ! $this->shortcode_atts['inactive_products'] ) {
					$args['post_status'] = 'publish';
				}
				if ( $rating > 0 ) {
					$args['meta_query'][] = array(
						'key' => 'rating',
						'value'   => $rating,
						'compare' => '=',
						'type'    => 'numeric'
					);
				}
				// Query needs to be modified if min_chars constraints are set
				if ( ! empty( $this->shortcode_atts['min_chars'] ) ) {
					add_filter( 'comments_clauses', array( $this, 'min_chars_comments_clauses' ) );
				}
				// Query needs to be modified if category constraints are set
				if ( ! empty( $this->shortcode_atts['categories'] ) ) {
					add_filter( 'comments_clauses', array( $this, 'modify_comments_clauses' ) );
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

				// a filter to merge product and shop reviews
				add_filter( 'comments_clauses', array( $this, 'merge_comments_clauses' ) );
				//
				$count = get_comments($args);
				//
				remove_filter( 'comments_clauses', array( $this, 'merge_comments_clauses' ) );

				// WPML compatibility
				if( has_filter( 'wpml_current_language' ) && ! function_exists( 'pll_current_language' ) ) {
					global $sitepress;
					if ( $sitepress ) {
						add_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ), 10, 2 );
					}
				}
				//
				remove_filter( 'comments_clauses', array( $this, 'modify_comments_clauses' ) );
				remove_filter( 'comments_clauses', array( $this, 'min_chars_comments_clauses' ) );
			}
			return $count;
		}

		public function show_summary_table() {
			$all = $this->count_ratings(0);
			if ($all > 0) {
				$five = (float)$this->count_ratings(5);
				$five_percent = floor($five / $all * 100);
				$five_rounding = $five / $all * 100 - $five_percent;
				$four = (float)$this->count_ratings(4);
				$four_percent = floor($four / $all * 100);
				$four_rounding = $four / $all * 100 - $four_percent;
				$three = (float)$this->count_ratings(3);
				$three_percent = floor($three / $all * 100);
				$three_rounding = $three / $all * 100 - $three_percent;
				$two = (float)$this->count_ratings(2);
				$two_percent = floor($two / $all * 100);
				$two_rounding = $two / $all * 100 - $two_percent;
				$one = (float)$this->count_ratings(1);
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
			if ( $this->shortcode_atts['add_review'] ) {
				$summary_box_classes .= ' cr-summaryBox-add-review';
			}
			$output = '';
			$output .= '<div class="' . $summary_box_classes . '">';
			if ( $this->shortcode_atts['add_review'] ) {
				$output .= '<div class="cr-summary-separator-side"></div>';
			}
			$output .= '<div class="cr-overall-rating-wrap">';
			$output .= '<div class="cr-average-rating"><span>' . number_format_i18n( $average, 1 ) . '</span></div>';
			$output .= '<div class="cr-average-rating-stars"><div class="crstar-rating"><span style="width:'.($average / 5 * 100).'%;"></span></div></div>';
			$output .= '<div class="cr-total-rating-count">' . sprintf( _n( 'Based on %s review', 'Based on %s reviews', $all, 'customer-reviews-woocommerce' ), number_format_i18n( $all ) ) . '</div>';
			$output .= '</div>';
			$output .= '<div class="cr-summary-separator"><div class="cr-summary-separator-int"></div></div>';
			if( 0 < $this->shortcode_atts['show_more'] ) {
				$output .= '<div class="ivole-summaryBox cr-all-reviews-ajax">';
			} else {
				$output .= '<div class="ivole-summaryBox">';
			}
			$output .= '<table id="ivole-histogramTable">';
			$output .= '<tbody>';
			$output .= '<tr class="ivole-histogramRow">';
			// five
			if( $five > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="cr-histogram-a" data-rating="5" href="' . esc_url( add_query_arg( $this->ivrating, 5, get_permalink() ) ) . '" title="' . __( '5 star', 'customer-reviews-woocommerce' ) . '">' . __( '5 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="cr-histogram-a" data-rating="5" href="' . esc_url( add_query_arg( $this->ivrating, 5, get_permalink() ) ) . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $five_percent . '%">' . $five_percent . '</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="cr-histogram-a" data-rating="5" href="' . esc_url( add_query_arg( $this->ivrating, 5, get_permalink() ) ) . '">' . (string)$five_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __('5 star', 'customer-reviews-woocommerce') . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $five_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$five_percent . '%</td>';
			}

			$output .= '</tr>';
			$output .= '<tr class="ivole-histogramRow">';
			// four
			if( $four > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="cr-histogram-a" data-rating="4" href="' . esc_url( add_query_arg( $this->ivrating, 4, get_permalink() ) ) . '" title="' . __( '4 star', 'customer-reviews-woocommerce' ) . '">' . __( '4 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="cr-histogram-a" data-rating="4" href="' . esc_url( add_query_arg( $this->ivrating, 4, get_permalink() ) ) . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $four_percent . '%">' . $four_percent . '</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="cr-histogram-a" data-rating="4" href="' . esc_url( add_query_arg( $this->ivrating, 4, get_permalink() ) ) . '">' . (string)$four_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __('4 star', 'customer-reviews-woocommerce') . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $four_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$four_percent . '%</td>';
			}

			$output .= '</tr>';
			$output .= '<tr class="ivole-histogramRow">';
			// three
			if( $three > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="cr-histogram-a" data-rating="3" href="' . esc_url( add_query_arg( $this->ivrating, 3, get_permalink() ) ) . '" title="' . __( '3 star', 'customer-reviews-woocommerce' ) . '">' . __( '3 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="cr-histogram-a" data-rating="3" href="' . esc_url( add_query_arg( $this->ivrating, 3, get_permalink() ) ) . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $three_percent . '%">' . $three_percent . '</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="cr-histogram-a" data-rating="3" href="' . esc_url( add_query_arg( $this->ivrating, 3, get_permalink() ) ) . '">' . (string)$three_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __('3 star', 'customer-reviews-woocommerce') . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $three_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$three_percent . '%</td>';
			}

			$output .= '</tr>';
			$output .= '<tr class="ivole-histogramRow">';
			// two
			if( $two > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="cr-histogram-a" data-rating="2" href="' . esc_url( add_query_arg( $this->ivrating, 2, get_permalink() ) ) . '" title="' . __( '2 star', 'customer-reviews-woocommerce' ) . '">' . __( '2 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="cr-histogram-a" data-rating="2" href="' . esc_url( add_query_arg( $this->ivrating, 2, get_permalink() ) ) . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $two_percent . '%">' . $two_percent .'</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="cr-histogram-a" data-rating="2" href="' . esc_url( add_query_arg( $this->ivrating, 2, get_permalink() ) ) . '">' . (string)$two_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __('2 star', 'customer-reviews-woocommerce') . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $two_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$two_percent . '%</td>';
			}

			$output .= '</tr>';
			$output .= '<tr class="ivole-histogramRow">';
			// one
			if( $one > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="cr-histogram-a" data-rating="1" href="' . esc_url( add_query_arg( $this->ivrating, 1, get_permalink() ) ) . '" title="' . __( '1 star', 'customer-reviews-woocommerce' ) . '">' . __( '1 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="cr-histogram-a" data-rating="1" href="' . esc_url( add_query_arg( $this->ivrating, 1, get_permalink() ) ) . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $one_percent . '%">' . $one_percent . '</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="cr-histogram-a" data-rating="1" href="' . esc_url( add_query_arg( $this->ivrating, 1, get_permalink() ) ) . '">' . (string)$one_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __('1 star', 'customer-reviews-woocommerce') . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $one_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$one_percent . '%</td>';
			}

			$output .= '</tr>';
			$output .= '</tbody>';
			$output .= '</table>';
			$output .= '</div>';
			if ( $this->shortcode_atts['add_review'] ) {
				$output .= '<div class="cr-summary-separator"><div class="cr-summary-separator-int"></div></div>';
				$output .= '<div class="cr-add-review-wrap">';
				$output .= '<button class="cr-all-reviews-add-review" type="button">' . __( 'Add a review', 'customer-reviews-woocommerce' ) . '</button>';
				$output .= '</div>';
				$output .= '<div class="cr-summary-separator-side"></div>';
			}
			$output .= '</div>';
			return $output;
		}

		/**
		* Modify the comments query to constrain results to the provided categories
		*/
		public function modify_comments_clauses( $clauses ) {
			global $wpdb;

			$terms = get_terms( array(
				'taxonomy' => 'product_cat',
				'include'  => $this->shortcode_atts['categories'],
				'fields'   => 'tt_ids'
			) );

			if ( is_array( $terms ) && count( $terms ) > 0 ) {
				$clauses['join'] .= " LEFT JOIN {$wpdb->term_relationships} ON {$wpdb->comments}.comment_post_ID = {$wpdb->term_relationships}.object_id";
				$clauses['where'] .= " AND {$wpdb->term_relationships}.term_taxonomy_id IN(" . implode( ',', $terms ) . ")";
			}

			return $clauses;
		}

		public function min_chars_comments_clauses( $clauses ) {
			global $wpdb;

			$clauses['where'] .= " AND CHAR_LENGTH({$wpdb->comments}.comment_content) >= " . $this->shortcode_atts['min_chars'];

			return $clauses;
		}

		/**
		* Modify the comments query to constrain results to reviews with tags
		*/
		public function tags_comments_clauses( $clauses ) {
			global $wpdb;

			$clauses['join'] .= " INNER JOIN {$wpdb->term_relationships} ON {$wpdb->comments}.comment_ID = {$wpdb->term_relationships}.object_id
				LEFT JOIN {$wpdb->term_taxonomy} ON {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.taxonomy = 'cr_tag'";

			return $clauses;
		}

		/**
		* Modify the comments query to constrain results to product and/or shop reviews
		*/
		public function merge_comments_clauses( $clauses ) {
			global $wpdb;
			$where_clause = '';

			// product reviews
			if ( $this->shortcode_atts['product_reviews'] ) {
				$products = $this->shortcode_atts['products'];
				if ( $products ) {
					$where_clause = "( {$wpdb->posts}.post_type = 'product' AND {$wpdb->posts}.ID IN (" . implode( ',', $products ) . ") )";
				} else {
					$where_clause = "{$wpdb->posts}.post_type = 'product'";
				}
			}
			// shop reviews
			if ( $this->shortcode_atts['shop_reviews'] ) {
				$shop_pages = CR_Reviews_List_Table::get_shop_page();
				if ( $shop_pages ) {
					if ( $where_clause ) {
						$where_clause = 	$where_clause . " OR {$wpdb->posts}.ID IN (" . implode( ',', $shop_pages ) . ")";
					} else {
						$where_clause = "{$wpdb->posts}.ID IN (" . implode( ',', $shop_pages ) . ")";
					}
				}
			}

			if ( $where_clause ) {
				$clauses['where'] .= " AND ( " . $where_clause . " )";
			}

			$join = "JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->comments.comment_post_ID";
			if ( isset( $clauses['join'] ) ) {
				if ( false === strstr( $clauses['join'], "$wpdb->posts" ) ) {
					$clauses['join'] = $join . ' ' . $clauses['join'];
				}
			} else {
				$clauses['join'] = $join;
			}

			return $clauses;
		}

		private function include_review_replies( $comments ) {
			$comments_w_replies = array();
			foreach ( $comments as $comment ) {
				$comments_w_replies[]  = $comment;
				$args = array(
					'parent' => $comment->comment_ID,
					'format' => 'flat',
					'status' => 'approve',
					'orderby' => 'comment_date_gmt'
				);
				$comment_children = get_comments( $args );
				foreach ( $comment_children as $comment_child ) {
					$reply_already_exist = false;
					foreach( $comments as $comment_flat ) {
						if ( $comment_flat->comment_ID === $comment_child->comment_ID ) {
							$reply_already_exist = true;
						}
					}
					if ( ! $reply_already_exist ) {
						$comments_w_replies[] = $comment_child;
					}
				}
			}
			return $comments_w_replies;
		}

		public function cr_photoswipe() {
			wc_get_template(
				'cr-photoswipe.php',
				array(),
				'customer-reviews-woocommerce',
				dirname( dirname( dirname( __FILE__ ) ) ) . '/templates/'
			);
		}

		public function show_count_row( $count, $page, $per_page, $pagination, $rating, $all ) {
			$count_wording = self::get_count_wording( $count, $page, $per_page, $pagination, $rating, $all );
			$sort_helpful = 'helpful' === $this->shortcode_atts['sort_by'] ? true : false;

			$output = '<div class="cr-count-row">';
			$output .=  '<div class="cr-count-row-count">' . $count_wording . '</div>';
			$output .=  '<div class="cr-ajax-reviews-sort-div">';
			$output .=   '<select name="cr_ajax_reviews_sort" class="cr-ajax-reviews-sort" data-nonce="' . wp_create_nonce( 'cr_product_reviews_sort' ) . '" aria-label="' . esc_attr__( 'Sort reviews', 'customer-reviews-woocommerce' ) . '">';
			$output .=    '<option value="recent"' . ( $sort_helpful ? '' : ' selected="selected"' ) . '>';
			$output .=     esc_html__( 'Most Recent', 'customer-reviews-woocommerce' );
			$output .=    '</option>';
			$output .=    '<option value="helpful"' . ( $sort_helpful ? ' selected="selected"' : '' ) . '>';
			$output .=     esc_html__( 'Most Helpful', 'customer-reviews-woocommerce' );
			$output .=    '</option>';
			$output .=   '</select>';
			$output .=  '</div>';
			$output .= '</div>';
			return $output;
		}

		public static function get_count_wording( $count, $page, $per_page, $pagination, $rating, $all ) {
			// optional strings that need to be displayed when reviews are filtered by rating
			$rating_string = '';
			$all_reviews_string = '';
			if( $rating ) {
				$rating_string = sprintf(
					_n( '%d star', '%d stars', $rating, 'customer-reviews-woocommerce' ),
					$rating
				);
			}
			if( $all ) {
				$all_reviews_string = sprintf(
					_n( 'See all %d review', 'See all %d reviews', $all, 'customer-reviews-woocommerce' ),
					$all
				);
				$all_reviews_string =
					'<a class="cr-seeAllReviews" data-rating="0" href="' . esc_url( get_permalink() ) . '">' .
					esc_html( $all_reviews_string ) .
					'</a>';
			}
			//
			if( 0 < $count ) {
				if( $pagination ) {
					$from = ( $page - 1 ) * $per_page + 1;
				} else {
					$from = 1;
				}
				$to = $page * $per_page < $count ? $page * $per_page : $count;
				if( $rating_string ) {
					return sprintf(
						_n( '%d-%d of %d review (%s). %s', '%d-%d of %d reviews (%s). %s', $count, 'customer-reviews-woocommerce' ),
						$from,
						$to,
						$count,
						$rating_string,
						$all_reviews_string
					);
				} else {
					return sprintf(
						_n( '%d-%d of %d review', '%d-%d of %d reviews', $count, 'customer-reviews-woocommerce' ),
						$from,
						$to,
						$count
					);
				}
			} else {
				if( $rating_string ) {
					return sprintf (
						__( '0 of 0 reviews (%s). %s', 'customer-reviews-woocommerce' ),
						$rating_string,
						$all_reviews_string
					);
				} else {
					return __( '0 of 0 reviews', 'customer-reviews-woocommerce' );
				}
			}
		}

		public static function show_add_review_form( $add_review ) {
			$item_id = -1;
			$item_name = Ivole_Email::get_blogname();
			$item_pic = get_site_icon_url( 512, plugins_url( '/img/store.svg', dirname( dirname( __FILE__ ) ) ) );
			$media_upload = false;
			$cr_form_item_media_array = array();
			$cr_form_item_media_desc = __( 'Add photos or video to your review', 'customer-reviews-woocommerce' );
			if ( is_numeric( $add_review ) ) {
				$product = wc_get_product( $add_review );
				$item_id = $product->get_id();
				$item_name = $product->get_name();
				$item_pic = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail', false );
				if ( 'yes' === get_option( 'ivole_attach_image', 'no' ) ) {
					$media_upload = true;
				}
			}
			$cr_form_permissions = CR_Forms_Settings::get_default_review_permissions();
			$form_settings = CR_Forms_Settings::get_default_form_settings();
			$cr_form_checkbox = ( 'yes' === CR_Forms_Settings::get_onsite_form_checkbox( $form_settings ) ) ? true : false;
			$cr_form_checkbox_text = CR_Forms_Settings::get_onsite_form_checkbox_text( $form_settings );
			if ( false === $cr_form_checkbox_text ) {
				$cr_form_checkbox_text = CR_Forms_Settings::get_default_form_onsite_checkbox_text();
			}
			ob_start();
			wc_get_template(
				'cr-review-form.php',
				array(
					'cr_item_id' => $item_id,
					'cr_item_name' => $item_name,
					'cr_item_pic' => $item_pic,
					'cr_form_media_enabled' => $media_upload,
					'cr_form_item_media_array' => $cr_form_item_media_array,
					'cr_form_item_media_desc' => $cr_form_item_media_desc,
					'cr_form_permissions' => $cr_form_permissions,
					'cr_form_checkbox' => $cr_form_checkbox,
					'cr_form_checkbox_text' => wp_specialchars_decode( $cr_form_checkbox_text, ENT_QUOTES )
				),
				'customer-reviews-woocommerce',
				dirname( dirname( dirname( __FILE__ ) ) ) . '/templates/'
			);
			return ob_get_clean();
		}

		public function submit_review() {
			$return = array(
				'code' => 2,
				'description' => __( 'Data validation error', 'customer-reviews-woocommerce' ),
				'button' => __( 'OK', 'customer-reviews-woocommerce' )
			);
			// read settings for review permissions
			$cr_form_permissions = CR_Forms_Settings::get_default_review_permissions();
			// check if reviews are allowed
			if ( ! in_array( $cr_form_permissions, array( 'registered', 'verified', 'anybody' ) ) ) {
				$return['code'] = 3;
				$return['description'] = __( 'Currently, we are not accepting new reviews', 'customer-reviews-woocommerce' );
			} else {
				if (
					isset( $_POST['rating'] ) &&
					isset( $_POST['review'] ) &&
					isset( $_POST['id'] ) &&
					isset( $_POST['name'] ) &&
					isset( $_POST['email'] ) &&
					is_numeric( $_POST['id'] )
				) {
					// check if a user is logged-in and permission is 'registered'
					// check if a user is logged-in, it is a shop review, and permission is 'verified'
					if (
						(
							'registered' === $cr_form_permissions &&
							! is_user_logged_in()
						) ||
						(
							'verified' === $cr_form_permissions &&
							0 > $_POST['id'] &&
							! is_user_logged_in()
						)
					) {
						$return['code'] = 4;
						$return['description'] = __( 'You must be logged in to post a review', 'customer-reviews-woocommerce' );
					} else {
						$page_id = 0;
						if ( -1 == $_POST['id'] ) {
							$page_id = wc_get_page_id( 'shop' );
							// WPML compatibility
							if ( has_filter( 'wpml_object_id' ) ) {
								$page_id = apply_filters( 'wpml_object_id', $page_id, 'page', true );
							}
						} else {
							$page_id = $_POST['id'];
						}
						if( 0 < $page_id ) {
							$rating = intval( $_POST['rating'] );
							$review = sanitize_textarea_field( trim( $_POST['review'] ) );
							$name = sanitize_text_field( trim( $_POST['name'] ) );
							$email = sanitize_email( trim( $_POST['email'] ) );
							//
							if (
								$rating &&
								$review &&
								$name &&
								is_email( $email )
							) {
								// check if a user bought the product in the past and permission is 'verified'
								if (
									'verified' === $cr_form_permissions &&
									! wc_customer_bought_product( $email, get_current_user_id(), $page_id )
								) {
									$return['code'] = 5;
									$return['description'] = __( 'Only customers who have purchased this product may leave a review. Please use the same email address as in your order for this product.', 'customer-reviews-woocommerce' );
								} else {
									$user = get_user_by( 'email', $email );
									if( $user ) {
										$user = $user->ID;
									} else {
										$user = 0;
									}
									$commentdata = array(
										'comment_author' => $name,
										'comment_author_email' => $email,
										'comment_author_url' => '',
										'comment_content' => $review,
										'comment_type' => 'review',
										'comment_post_ID' => $page_id,
										'user_id' => $user,
										'comment_meta' => array(
											'rating' => intval( $rating )
										)
									);
									add_filter( 'pre_comment_approved', array( 'CR_All_Reviews', 'is_review_approved' ), 10, 2 );
									$result = wp_new_comment( $commentdata, true );
									remove_filter( 'pre_comment_approved', array( 'CR_All_Reviews', 'is_review_approved' ), 10 );

									$error_description = __( 'Your review could not be added', 'customer-reviews-woocommerce' );
									$error_button = __( 'Try again', 'customer-reviews-woocommerce' );
									$success_description = __( 'Your review has been successfully added', 'customer-reviews-woocommerce' );
									$success_button = __( 'Continue', 'customer-reviews-woocommerce' );

									if (
										!$result ||
										is_wp_error( $result )
									) {
										if( is_wp_error( $result ) ) {
											$error_description = $result->get_error_message();
										}
										$return = array(
											'code' => 1,
											'description' => $error_description,
											'button' => $error_button
										);
									} else {
										wp_update_comment_count_now( $page_id );
										$return = array(
											'code' => 0,
											'description' => $success_description,
											'button' => $success_button
										);
									}
								}
							}
						} else {
							if ( -1 == $_POST['id'] ) {
								// no shop page configured in the settings
								$return['description'] = __( 'Error: no shop page configured in WooCommerce settings (WooCommerce > Settings > Products > Shop page)', 'customer-reviews-woocommerce' );
							} else {
								$return['description'] = sprintf( __( 'Error: no product with ID %d found', 'customer-reviews-woocommerce' ), $page_id );
							}
						}
					}
				}
			}
			wp_send_json( $return );
		}

		public static function is_review_approved( $approved, $commentdata ) {
			if ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ) ) {
				$approved = 1;
			} else {
				$approved = 0;
			}
			return $approved;
		}

		public static function is_it_a_product_page() {
			if ( is_product() ) {
				$product = wc_get_product();
				if ( is_object( $product ) ) {
					return $product->get_id();
				}
			}
			return false;
		}

		public function upload_media_files() {
			$return = array(
				'code' => 100,
				'message' => ''
			);
			if( isset( $_POST['cr_item'] ) ) {
				if( isset( $_FILES ) && is_array( $_FILES ) && 0 < count( $_FILES ) ) {
					// check the file size
					$attach_image_size = get_option( 'ivole_attach_image_size', 25 );
					$max_size = 1024 * 1024 * $attach_image_size;
					if ( $max_size < $_FILES['cr_file']['size'] ) {
						$return['code'] = 501;
						$return['message'] = sprintf( __( 'The file cannot be uploaded because its size exceeds the limit of %d MB', 'customer-reviews-woocommerce' ), $attach_image_size );
						wp_send_json( $return );
						return;
					}
					// check the file type
					$file_name_parts = explode( '.', $_FILES['cr_file']['name'] );
					$file_ext = $file_name_parts[ count( $file_name_parts ) - 1 ];
					if( ! CR_Reviews::is_valid_file_type( $file_ext ) ) {
						$return['code'] = 502;
						$return['message'] = __( 'Error: accepted file types are PNG, JPG, JPEG, GIF, MP4, MPEG, OGG, WEBM, MOV, AVI', 'customer-reviews-woocommerce' );
						wp_send_json( $return );
						return;
					}
					// upload the file
					$post_id = $_POST['cr_item'] ? $_POST['cr_item'] : 0;
					$attachmentId = media_handle_upload( 'cr_file', $post_id );
					if( !is_wp_error( $attachmentId ) ) {
						$upload_key = bin2hex( openssl_random_pseudo_bytes( 10 ) );
						if( false !== update_post_meta( $attachmentId, 'cr-upload-temp-key', $upload_key ) ) {
							// return to js
							$return['attachment'] = array(
								'id' => $attachmentId,
								'key' => $upload_key
							);
						} else {
							$return['code'] = 503;
							$return['message'] = $_FILES['cr_file']['name'] . ': could not update the upload key.';
						}
					} else {
						$return['code'] = $attachmentId->get_error_code();
						$return['message'] = $attachmentId->get_error_message();
					}
					$return['code'] = 200;
					$return['message'] = 'OK';
				}
			}
			wp_send_json( $return );
		}

		public function delete_media_files() {
			$return = array(
				'code' => 100,
				'message' => ''
			);
			if( isset( $_POST['image'] ) && $_POST['image'] ) {
				$image_decoded = json_decode( stripslashes( $_POST['image'] ), true );
				if( $image_decoded && is_array( $image_decoded ) ) {
					if( isset( $image_decoded["id"] ) && $image_decoded["id"] ) {
						if( isset( $image_decoded["key"] ) && $image_decoded["key"] ) {
							$attachmentId = intval( $image_decoded["id"] );
							if( 'attachment' === get_post_type( $attachmentId ) ) {
								if( $image_decoded["key"] === get_post_meta( $attachmentId, 'cr-upload-temp-key', true ) ) {
									if( wp_delete_attachment( $attachmentId, true ) ) {
										$return['code'] = 200;
										$return['message'] = 'OK';
									} else {
										$return['code'] = 507;
										$return['message'] = 'Error: could not delete the image.';
									}
								} else {
									$return['code'] = 506;
									$return['message'] = 'Error: meta key does not match.';
								}
							} else {
								$return['code'] = 505;
								$return['message'] = 'Error: id does not belong to an attachment.';
							}
						} else {
							$return['code'] = 504;
							$return['message'] = 'Error: image key is not set.';
						}
					} else {
						$return['code'] = 503;
						$return['message'] = 'Error: image id is not set.';
					}
				} else {
					$return['code'] = 502;
					$return['message'] = 'Error: JSON decoding problem.';
				}
			} else {
				$return['code'] = 501;
				$return['message'] = 'Error: no image to delete.';
			}
			wp_send_json( $return );
		}

		public static function cr_paginate_links( $current, $total ) {
			if ( $total < 2 ) {
				return '';
			}

			$page_links = array();
			$mid_size = 1;
			$end_size = 1;
			$dots = false;

			if ( $current && 1 < $current ) {
				$page_links[] = sprintf(
					'<span class="prev cr-page-numbers cr-page-numbers-a" data-page="%d">%s</span>',
					$current - 2,
					'&laquo;'
				);
			}

			for ( $n = 1; $n <= $total; $n++ ) {
				if ( $n == $current ) :
					$page_links[] = sprintf(
						'<span class="cr-page-numbers current">%s</span>',
						number_format_i18n( $n )
					);
					$dots = true;
				else :
					if ( $n <= $end_size || ( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) || $n > $total - $end_size ) :
						$page_links[] = sprintf(
							'<span class="cr-page-numbers cr-page-numbers-a" data-page="%d">%s</span>',
							$n - 1,
							number_format_i18n( $n )
						);
						$dots = true;
					elseif ( $dots ) :
						$page_links[] = '<span class="cr-page-numbers dots">' . __( '&hellip;' ) . '</span>';
						$dots = false;
					endif;
				endif;
			}

			if ( $current && $current < $total ) {
				$page_links[] = sprintf(
					'<span class="next cr-page-numbers cr-page-numbers-a" data-page="%d">%s</span>',
					$current,
					'&raquo;'
				);
			}

			$r = implode( "\n", $page_links );
			return $r;
		}

		public function get_cache_domain() {
			return md5( serialize( $this->shortcode_atts ) );
		}

	}

endif;
