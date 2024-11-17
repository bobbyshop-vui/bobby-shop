<?php

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('CR_Ajax_Reviews')) :

	class CR_Ajax_Reviews
	{
		private static $per_page = 2;
		private static $sort = 'recent';
		private static $rating = 0;
		private static $search = '';
		private static $tags = array();
		const WPML_COOKIE = 'cr_wpml_is_filtered';

		public function __construct()
		{
			self::$per_page = get_option( 'ivole_ajax_reviews_per_page', 5 );
			self::$sort = get_option( 'ivole_ajax_reviews_sort', 'recent' );
			add_action( 'wp_ajax_cr_show_more_reviews', array( 'CR_Ajax_Reviews', 'show_more_reviews' ) );
			add_action( 'wp_ajax_nopriv_cr_show_more_reviews', array( 'CR_Ajax_Reviews', 'show_more_reviews' ) );
			add_action( 'wp_ajax_cr_sort_reviews', array( 'CR_Ajax_Reviews', 'sort_reviews' ) );
			add_action( 'wp_ajax_nopriv_cr_sort_reviews', array( 'CR_Ajax_Reviews', 'sort_reviews' ) );
			add_action( 'wp_ajax_cr_filter_reviews', array( 'CR_Ajax_Reviews', 'filter_reviews' ) );
			add_action( 'wp_ajax_nopriv_cr_filter_reviews', array( 'CR_Ajax_Reviews', 'filter_reviews' ) );
			add_action( 'init', array( 'CR_Ajax_Reviews', 'register_slider_script' ) );
			add_action( 'cr_reviews_search', array( 'CR_Ajax_Reviews', 'display_search_ui' ) );

			// WPML integration for setting a cookie about all reviews filter
			if( has_filter( 'wpml_is_comment_query_filtered' ) && ! wp_doing_ajax() ) {
				add_action( 'get_header', array( $this, 'wpml_is_filtered' ), 10, 2 );
			}
		}

		public static function get_reviews( $product_id ) {
			$post_in = array();
			if( function_exists( 'pll_current_language' ) && function_exists( 'PLL' )  && apply_filters( 'cr_reviews_polylang_merge', true ) ) {
				// Polylang integration
				global $polylang;
				$translationIds = PLL()->model->post->get_translations( $product_id );
				foreach ( $translationIds as $key => $translationID ) {
					$post_in[] = $translationID;
				}
			} elseif( has_filter( 'wpml_object_id' ) && has_filter( 'wpml_is_comment_query_filtered' ) &&
		 			has_filter( 'wpml_element_trid' ) && has_filter( 'wpml_get_element_translations' ) ) {
				// WPML integration
				if( wp_doing_ajax() ) {
					if( isset( $_COOKIE[self::WPML_COOKIE] ) && 'no' === $_COOKIE[self::WPML_COOKIE] ) {
						$is_filtered = false;
					} else {
						$is_filtered = true;
					}
				} else {
					$is_filtered = apply_filters( 'wpml_is_comment_query_filtered', true, $product_id );
				}
				if( false === $is_filtered ) {
					$trid = apply_filters( 'wpml_element_trid', NULL, $product_id, 'post_product' );
					if( $trid ) {
						$translations = apply_filters( 'wpml_get_element_translations', NULL, $trid, 'post_product' );
						if( $translations && is_array( $translations ) ) {
							foreach ($translations as $translation) {
								if( isset( $translation->element_id ) ) {
									$post_in[] = intval( $translation->element_id );
								}
							}
							global $sitepress;
							if ( $sitepress ) {
								remove_filter( 'comments_clauses', [ $sitepress, 'comments_clauses' ], 10 );
							}
						}
					}
				}
				if ( class_exists( 'WCML_Comments' ) ) {
					global $woocommerce_wpml;
					if ( $woocommerce_wpml ) {
						remove_action( 'the_comments', array( $woocommerce_wpml->comments, 'translate_product_ids' ) );
					}
				}
			}
			if( 1 > count( $post_in ) ) {
				$post_in[] = $product_id;
			}
			// different queries depending on sorting
			if ( 'helpful' === self::$sort ) {
				// most helpful reviews first
				$args = array(
					'post__in' => $post_in,
					'status' => 'approve',
					'meta_query' => array(
						array(
							'relation' => 'OR',
							array(
								'key' => 'ivole_review_votes',
								'type' => 'NUMERIC',
								'compare' => 'NOT EXISTS'
							),
							array(
								'key' => 'ivole_review_votes',
								'type' => 'NUMERIC',
								'compare' => 'EXISTS'
							)
						)
					),
					'orderby' => array(
						'meta_value_num',
						'comment_date_gmt'
					),
					'order' => 'DESC'
				);
			} elseif (
					'ratinglow' === self::$sort ||
					'ratinghigh' === self::$sort
				) {
				// reviews with lowest or highest ratings first
				$args = array(
					'post__in' => $post_in,
					'status' => 'approve',
					'meta_query' => array(
						array(
							'key' => 'rating',
							'type' => 'NUMERIC',
							'compare' => 'EXISTS'
						)
					),
					'orderby' => array(
						'meta_value_num',
						'comment_date_gmt'
					),
					'order' => 'ratinglow' === self::$sort ? 'ASC' : 'DESC'
				);
			} else {
				// most recent reviews first
				$args = array(
					'post__in' => $post_in,
					'status' => 'approve',
					'orderby' => 'comment_date_gmt',
					'order' => 'DESC'
				);
			}
			// filter by rating
			$args['meta_query']['relation'] = 'AND';
			if( 1 <= self::$rating && 5 >= self::$rating ) {
				$args['meta_query'][] = array(
					'key' => 'rating',
					'compare' => '=',
					'value' => self::$rating,
					'type' => 'NUMERIC'
				);
			} else {
				$args['meta_query'][] = array(
					'key' => 'rating',
					'compare' => 'EXISTS',
					'type' => 'NUMERIC'
				);
			}
			// search
			$args['search'] = self::$search;
			// tags
			if( 0 < count( self::$tags ) ) {
				$reviews_by_tags = get_objects_in_term( self::$tags, 'cr_tag' );
				if( $reviews_by_tags && !is_wp_error( $reviews_by_tags ) && is_array( $reviews_by_tags ) && 0 < count( $reviews_by_tags ) ) {
					$args['comment__in'] = $reviews_by_tags;
				}
			}
			// exclude qna
			$args['type__not_in'] = 'cr_qna';

			// get the reviews based on the prepared query
			$reviews_tmp = get_comments( $args );

			// get the featured reviews based on the prepared query
			$args['meta_query'][] = array(
				'key' => 'ivole_featured',
				'compare' => '>',
				'value' => '0',
				'type' => 'NUMERIC'
			);
			$featured_reviews_tmp = get_comments( $args );

			// remove featured reviews from the main array of the reviews while preserving their order
			if( 0 < count( $featured_reviews_tmp ) ) {
				$reviews = array();
				$featured_reviews = array();
				foreach ($reviews_tmp as $review_key => $review) {
					$is_featured = false;
					if( 0 < count( $featured_reviews_tmp ) ) {
						foreach ($featured_reviews_tmp as $featured_review_key => $featured_review) {
							if( $review->comment_ID === $featured_review->comment_ID ) {
								unset( $featured_reviews_tmp[$featured_review_key] );
								$review->comment_karma = 1;
								$featured_reviews[] = $review;
								$is_featured = true;
								break;
							}
						}
					}
					if( !$is_featured ) {
						$review->comment_karma = 0;
						$reviews[] = $review;
					}
				}
				// add the featured reviews back to the main array of reviews
				$reviews =  array_merge( $featured_reviews, $reviews );
			} else {
				$reviews = $reviews_tmp;
			}

			// replies are not counted against the number of reviews per page
			$top_level_reviews_count = count( $reviews );

			// add replies to reviews
			$reviews_ids = array_map( function ( $r ) { return $r->comment_ID; }, $reviews );
			if( $reviews_ids && is_array( $reviews_ids ) && 0 < count( $reviews_ids ) ) {
				$args_replies = array(
					'post__in' => $post_in,
					'status' => 'approve',
					'orderby' => 'comment_date_gmt',
					'order' => 'DESC',
					'parent__in' => $reviews_ids,
					'type__not_in' => 'cr_qna'
				);
				// loop to check for nested comments (replies to replies)
				$fetch_more_replies = true;
				while( $fetch_more_replies ) {
					$replies = get_comments( $args_replies );
					if( $replies && is_array( $replies ) && 0 < count( $replies ) ) {
						$reviews = array_merge( $reviews, $replies );
						$args_replies['parent__in'] = array_map( function ( $r ) { return $r->comment_ID; }, $replies );
					} else {
						$fetch_more_replies = false;
					}
				}
			}

			//highlight search results
			if( !empty( self::$search ) ) {
				$highlight = self::$search;
				$reviews = array_map( function( $item ) use( $highlight ) {
					$item->comment_content = preg_replace( '/(' . $highlight . ')(?![^<>]*\/>)/iu', '<span class="cr-search-highlight">\0</span>', $item->comment_content );
					return $item;
				}, $reviews);
			}

			return array(
				'reviews' => apply_filters( 'cr_reviews_array', array( $reviews, array() ), $product_id ),
				'reviews_count' => $top_level_reviews_count
			);
		}

		public static function show_more_reviews() {
			$html = '';
			$page = 0;
			$last_page = false;
			$show_more_label = '';
			$count_row = '';
			$all = 0;
			if( isset( $_POST['productID'] ) ) {
				if( apply_filters( 'cr_reviews_check_nonce', false ) ) {
					check_ajax_referer( 'cr_product_reviews_' . $_POST['productID'], 'security' );
				}
				if( isset( $_POST['page'] ) ) {
					if (
						isset( $_POST['sort'] ) &&
						(
							'recent' === $_POST['sort'] ||
							'helpful' === $_POST['sort'] ||
							'ratinghigh' === $_POST['sort'] ||
							'ratinglow' === $_POST['sort']
						)
					) {
						self::$sort = $_POST['sort'];
					}
					if( isset( $_POST['rating'] ) && ( 0 <= $_POST['rating'] && 6 > $_POST['rating'] ) ) {
						$all = self::count_ratings( $_POST['productID'], 0 );
						self::$rating = $_POST['rating'];
					}
					//search
					if( !empty( trim( $_POST['search'] ) ) ) {
						self::$search = sanitize_text_field( trim( $_POST['search'] ) );
					}
					//tags
					if( isset( $_POST['tags'] ) && is_array( $_POST['tags'] ) && count( $_POST['tags'] ) > 0 ) {
						self::$tags = array_map( 'intval', $_POST['tags'] );
					}
					$page = intval( $_POST['page'] ) + 1;
					$get_reviews = self::get_reviews( $_POST['productID'] );
					$initials_setting = get_option( 'ivole_avatars', 'standard' );
					if( 'initials' === $initials_setting ) {
						add_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ), 10, 5 );
					}
					$hide_avatars = 'hidden' === get_option( 'ivole_avatars', 'standard' ) ? true : false;
					$more_reviews = wp_list_comments( apply_filters(
						'woocommerce_product_review_list_args',
						array(
							'callback' => array( 'CR_Reviews', 'callback_comments' ),
							'reverse_top_level' => false,
							'per_page' => self::$per_page,
							'page' => $page,
							'echo' => false,
							'cr_hide_avatars' => $hide_avatars )
						),
						$get_reviews['reviews'][0]
					);
					if( 'initials' === $initials_setting ) {
						remove_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ) );
					}
					$html = $more_reviews;
					$count_pages = ceil( $get_reviews['reviews_count'] / self::$per_page );
					if( $count_pages <= $page ) {
						$last_page = true;
					}
					$show_more_label = sprintf(
						__( 'Show more reviews (%d)', 'customer-reviews-woocommerce' ),
						$get_reviews['reviews_count'] - $page * self::$per_page
					);
					$count_row = CR_All_Reviews::get_count_wording(
						$get_reviews['reviews_count'],
						$page,
						self::$per_page,
						false,
						self::$rating,
						$all
					);
				}
			}
			wp_send_json(
				array(
					'page' => $page,
					'html' => $html,
					'last_page' => $last_page,
					'show_more_label' => $show_more_label,
					'count_row' => $count_row
				)
			);
		}

		public static function get_per_page() {
			return self::$per_page;
		}

		public static function get_sort() {
			return self::$sort;
		}

		public static function sort_reviews() {
			$html = '';
			$page = 0;
			$last_page = false;
			$show_more_label = '';
			$count_row = '';
			$all = 0;
			if( isset( $_POST['productID'] ) ) {
				if( isset( $_POST['sort'] ) ) {
					if (
						'recent' === $_POST['sort'] ||
						'helpful' === $_POST['sort'] ||
						'ratinghigh' === $_POST['sort'] ||
						'ratinglow' === $_POST['sort']
					) {
						self::$sort = $_POST['sort'];
						if( isset( $_POST['rating'] ) && ( 0 <= $_POST['rating'] && 6 > $_POST['rating'] ) ) {
							$all = self::count_ratings( $_POST['productID'], 0 );
							self::$rating = $_POST['rating'];
						}
						$get_reviews = self::get_reviews( $_POST['productID'] );
						$initials_setting = get_option( 'ivole_avatars', 'standard' );
						if( 'initials' === $initials_setting ) {
							add_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ), 10, 5 );
						}
						$hide_avatars = 'hidden' === get_option( 'ivole_avatars', 'standard' ) ? true : false;
						$more_reviews = wp_list_comments( apply_filters(
							'woocommerce_product_review_list_args',
							array(
								'callback' => array( 'CR_Reviews', 'callback_comments' ),
								'reverse_top_level' => false,
								'per_page' => self::$per_page,
								'page' => 1,
								'echo' => false,
								'cr_hide_avatars' => $hide_avatars )
							),
							$get_reviews['reviews'][0]
						);
						if( 'initials' === $initials_setting ) {
							remove_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ) );
						}
						$html = $more_reviews;
						$page = 1;
						if( $get_reviews['reviews_count'] <= self::$per_page ) {
							$last_page = true;
						}
						$show_more_label = sprintf(
							__( 'Show more reviews (%d)', 'customer-reviews-woocommerce' ),
							$get_reviews['reviews_count'] - $page * self::$per_page
						);
						$count_row = CR_All_Reviews::get_count_wording(
							$get_reviews['reviews_count'],
							$page,
							self::$per_page,
							false,
							self::$rating,
							$all
						);
					}
				}
			}
			wp_send_json(
				array(
					'page' => $page,
					'html' => $html,
					'last_page' => $last_page,
					'show_more_label' => $show_more_label,
					'count_row' => $count_row
				)
			);
		}

		public static function filter_reviews() {
			$html = '';
			$page = 0;
			$last_page = false;
			$show_more_label = '';
			$count_row = '';
			$all = 0;
			if( isset( $_POST['productID'] ) ) {
				if( apply_filters( 'cr_reviews_check_nonce', false ) ) {
					check_ajax_referer( 'cr_product_reviews_filter_' . $_POST['productID'], 'security' );
				}
				if( isset( $_POST['rating'] ) ) {
					if( 0 <= $_POST['rating'] && 6 > $_POST['rating'] ) {
						self::$rating = $_POST['rating'];
						if( isset( $_POST['sort'] ) && ( 'recent' === $_POST['sort'] || 'helpful' === $_POST['sort'] ) ) {
							self::$sort = $_POST['sort'];
						}
						$get_reviews = self::get_reviews( $_POST['productID'] );
						$initials_setting = get_option( 'ivole_avatars', 'standard' );
						if( 'initials' === $initials_setting ) {
							add_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ), 10, 5 );
						}
						$hide_avatars = 'hidden' === get_option( 'ivole_avatars', 'standard' ) ? true : false;
						$more_reviews = wp_list_comments( apply_filters(
							'woocommerce_product_review_list_args',
							array(
								'callback' => array( 'CR_Reviews', 'callback_comments' ),
								'reverse_top_level' => false,
								'per_page' => self::$per_page,
								'page' => 1,
								'echo' => false,
							 	'cr_hide_avatars' => $hide_avatars )
							),
							$get_reviews['reviews'][0]
						);
						if( 'initials' === $initials_setting ) {
							remove_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ) );
						}
						$html = $more_reviews;
						$page = 1;
						if( $get_reviews['reviews_count'] <= self::$per_page ) {
							$last_page = true;
						}
						if( 0 < self::$rating ) {
							$all = self::count_ratings( $_POST['productID'], 0 );
						}
						$show_more_label = sprintf(
							__( 'Show more reviews (%d)', 'customer-reviews-woocommerce' ),
							$get_reviews['reviews_count'] - $page * self::$per_page
						);
						$count_row = CR_All_Reviews::get_count_wording(
							$get_reviews['reviews_count'],
							$page,
							self::$per_page,
							false,
							self::$rating,
							$all
						);
					}
				}
			}
			wp_send_json(
				array(
					'page' => $page,
					'html' => $html,
					'last_page' => $last_page,
					'show_more_label' => $show_more_label,
					'count_row' => $count_row
				)
			);
		}

		public static function count_ratings( $product_id, $rating ) {
			$post_in = array();

			if( function_exists( 'pll_current_language' ) && function_exists( 'PLL' ) && apply_filters( 'cr_reviews_polylang_merge', true ) ) {
				//Polylang integration
				global $polylang;
				$translationIds = PLL()->model->post->get_translations( $product_id );
				foreach ( $translationIds as $key => $translationID ) {
					$post_in[] = $translationID;
				}
			} elseif (
				has_filter( 'wpml_object_id' ) &&
				has_filter( 'wpml_is_comment_query_filtered' ) &&
				has_filter( 'wpml_element_trid' ) &&
				has_filter( 'wpml_get_element_translations' )
			) {
				// WPML integration
				$is_filtered = false;
				if( wp_doing_ajax() ) {
					if( isset( $_COOKIE[self::WPML_COOKIE] ) && 'no' === $_COOKIE[self::WPML_COOKIE] ) {
						$is_filtered = false;
					} else {
						$is_filtered = true;
					}
				} else {
					$is_filtered = apply_filters( 'wpml_is_comment_query_filtered', true, $product_id );
				}
				if( false === $is_filtered ) {
					$trid = apply_filters( 'wpml_element_trid', NULL, $product_id, 'post_product' );
					if( $trid ) {
						$translations = apply_filters( 'wpml_get_element_translations', NULL, $trid, 'post_product' );
						if( $translations && is_array( $translations ) ) {
							foreach ($translations as $translation) {
								if( isset( $translation->element_id ) ) {
									$post_in[] = intval( $translation->element_id );
								}
							}
							global $sitepress;
							if ( $sitepress ) {
								remove_filter( 'comments_clauses', [ $sitepress, 'comments_clauses' ], 10 );
							}
						}
					}
				}
			} else {
				$post_in = array( $product_id );
			}
			$args = array(
				'post__in' => $post_in,
				'status' => 'approve',
				'parent' => 0,
				'count' => true
			);
			if( 0 === $rating ) {
				$args['meta_query'][] = array(
					'key' => 'rating',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'numeric'
				);
			} else if( $rating > 0 ){
				$args['meta_query'][] = array(
					'key' => 'rating',
					'value'   => $rating,
					'compare' => '=',
					'type'    => 'numeric'
				);
			}
			return get_comments( $args );
		}

		public static function update_reviews_meta() {
			$batch_size = 100;
			$args = array(
				'post_type' => 'product',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => 'ivole_review_votes',
						'compare' => 'NOT EXISTS'
					),
					array(
						'relation' => 'OR',
						array(
							'key' => 'ivole_review_reg_upvoters',
							'compare' => 'EXISTS'
						),
						array(
							'key' => 'ivole_review_reg_downvoters',
							'compare' => 'EXISTS'
						),
						array(
							'key' => 'ivole_review_unreg_upvoters',
							'compare' => 'EXISTS'
						),
						array(
							'key' => 'ivole_review_unreg_downvoters',
							'compare' => 'EXISTS'
						)
					)
				),
				'number' => $batch_size
			);
			$reviews = get_comments( $args );
			if( 0 < count( $reviews ) ) {
				// flag to show a message that reviews are being updated
				update_option( 'ivole_update_votes_meta', true );
				// loop through and update votes meta data in reviews
				foreach ($reviews as $review) {
					$r_upvotes = 0;
					$r_downvotes = 0;
					$u_upvotes = 0;
					$u_downvotes = 0;
					$registered_upvoters = get_comment_meta( $review->comment_ID, 'ivole_review_reg_upvoters', true );
					$registered_downvoters = get_comment_meta( $review->comment_ID, 'ivole_review_reg_downvoters', true );
					$unregistered_upvoters = get_comment_meta( $review->comment_ID, 'ivole_review_unreg_upvoters', true );
					$unregistered_downvoters = get_comment_meta( $review->comment_ID, 'ivole_review_unreg_downvoters', true );

					if( !empty( $registered_upvoters ) ) {
						$registered_upvoters = maybe_unserialize( $registered_upvoters );
						if( is_array( $registered_upvoters ) ) {
							$r_upvotes = count( $registered_upvoters );
						}
					}
					if( !empty( $registered_downvoters ) ) {
						$registered_downvoters = maybe_unserialize( $registered_downvoters );
						if( is_array( $registered_downvoters ) ) {
							$r_downvotes = count( $registered_downvoters );
						}
					}
					if( !empty( $unregistered_upvoters ) ) {
						$unregistered_upvoters = maybe_unserialize( $unregistered_upvoters );
						if( is_array( $unregistered_upvoters ) ) {
							$u_upvotes = count( $unregistered_upvoters );
						}
					}
					if( !empty( $unregistered_downvoters ) ) {
						$unregistered_downvoters = maybe_unserialize( $unregistered_downvoters );
						if( is_array( $unregistered_downvoters ) ) {
							$u_downvotes = count( $unregistered_downvoters );
						}
					}

					$votes = $r_upvotes + $u_upvotes - $r_downvotes - $u_downvotes;
					update_comment_meta( $review->comment_ID, 'ivole_review_votes', $votes );
				}
				return false;
			} else {
				// no more reviews to update, so remove the flag
				delete_option( 'ivole_update_votes_meta' );
				return true;
			}
		}

		// a function to create meta keys and values with the count of media files uploaded with a review
		public static function update_reviews_meta2() {
			$batch_size = 100;
			$args = array(
				'post_type' => 'product',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => 'ivole_media_count',
						'compare' => 'NOT EXISTS'
					),
					array(
						'relation' => 'OR',
						array(
							'key' => 'ivole_review_image',
							'compare' => 'EXISTS'
						),
						array(
							'key' => 'ivole_review_image2',
							'compare' => 'EXISTS'
						),
						array(
							'key' => 'ivole_review_video',
							'compare' => 'EXISTS'
						),
						array(
							'key' => 'ivole_review_video2',
							'compare' => 'EXISTS'
						)
					)
				),
				'number' => $batch_size
			);
			$reviews = get_comments( $args );
			if( 0 < count( $reviews ) ) {
				// flag to show a message that reviews are being updated
				update_option( 'ivole_update_media_meta', true );
				// loop through and update votes meta data in reviews
				foreach ($reviews as $review) {
					$media_count = self::get_media_count( $review->comment_ID );
					update_comment_meta( $review->comment_ID, 'ivole_media_count', $media_count );
				}
				return false;
			} else {
				// no more reviews to update, so remove the flag
				delete_option( 'ivole_update_media_meta' );
				return true;
			}
		}

		public static function get_media_count( $comment_id ) {
			$media_count = 0;

			$review_image = get_comment_meta( $comment_id, CR_Reviews::REVIEWS_META_IMG, false );
			$review_image2 = get_comment_meta( $comment_id, CR_Reviews::REVIEWS_META_LCL_IMG, false );
			$review_video = get_comment_meta( $comment_id, CR_Reviews::REVIEWS_META_VID, false );
			$review_video2 = get_comment_meta( $comment_id, CR_Reviews::REVIEWS_META_LCL_VID, false );

			if( is_array( $review_image ) ) {
				$review_image = count( $review_image );
			} else {
				$review_image = 0;
			}
			if( is_array( $review_image2 ) ) {
				$review_image2 = count( $review_image2 );
			} else {
				$review_image2 = 0;
			}
			if( is_array( $review_video ) ) {
				$review_video = count( $review_video );
			} else {
				$review_video = 0;
			}
			if( is_array( $review_video2 ) ) {
				$review_video2 = count( $review_video2 );
			} else {
				$review_video2 = 0;
			}

			$media_count = $review_image + $review_image2 + $review_video + $review_video2;

			return $media_count;
		}

		public static function register_slider_script() {
			wp_register_script(
				'cr-reviews-slider',
				plugins_url( 'js/slick.min.js', dirname( dirname( __FILE__ ) ) ),
				array( 'jquery' ),
				'3.119',
				true
			);
		}

		public static function display_search_ui( $reviews ) {
			if( apply_filters( 'cr_ajaxreviews_show_search', true ) ) {
				echo self::get_search_field( false );
			}
			echo self::get_tags_field( $reviews[0] );
		}

		public static function get_search_field( $search_button ) {
			$search_val = '';
			$clear_class = 'cr-clear-input';
			if( get_query_var( 'crsearch' ) ) {
				$search_val = strval( get_query_var( 'crsearch' ) );
				if( 0 < mb_strlen( $search_val ) ) {
					$clear_class = 'cr-clear-input cr-visible';
				}
			}
			$button = '';
			if ( $search_button ) {
				$button = '<button type="button" class="cr-button-search">'.  __( 'Search', 'customer-reviews-woocommerce' ) .'</button>';
			}
			$html = '
				<div class="cr-ajax-search">
					<div>
						<svg width="1em" height="1em" viewBox="0 0 16 16" class="cr-ajax-search-icon" fill="#868686" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" d="M10.442 10.442a1 1 0 0 1 1.415 0l3.85 3.85a1 1 0 0 1-1.414 1.415l-3.85-3.85a1 1 0 0 1 0-1.415z"/>
							<path fill-rule="evenodd" d="M6.5 12a5.5 5.5 0 1 0 0-11 5.5 5.5 0 0 0 0 11zM13 6.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0z"/>
						</svg>
						<input name="cr_input_text_search" class="cr-input-text" type="text" placeholder="'. esc_attr__( 'Search customer reviews', 'customer-reviews-woocommerce' ) .'" value="' . $search_val . '" aria-label="' . esc_attr__( 'Search customer reviews', 'customer-reviews-woocommerce' ) . '">
						<span class="' . $clear_class . '">
							<svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-x-circle-fill" fill="#868686" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
							</svg>
						</span>
					</div>' . $button . '</div>';

			return $html;
		}

		public function wpml_is_filtered( $name, $args ) {
			if (
				has_filter( 'wpml_is_comment_query_filtered' ) &&
				! wp_doing_ajax() &&
				is_product() &&
				apply_filters( 'cr_wpml_cookie', true )
			) {
				$is_filtered = ( false === apply_filters( 'wpml_is_comment_query_filtered', true, get_the_ID() ) ) ? 'no' : 'yes';
				$domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : parse_url( get_option( 'siteurl' ), PHP_URL_HOST );
				setcookie( self::WPML_COOKIE, $is_filtered, array(
					'expires' => 0,
					'path' => '/',
					'domain' => $domain,
					'samesite' => 'Lax' )
				);
			}
		}

		public static function get_tags_field( $comments ) {
			$tags_field = '';
			$all_reviews = array_map( function ( $r ) { return $r->comment_ID; }, $comments );
			// get tags based on the list of comment ids
			$all_tags = array();
			if ( $all_reviews && is_array( $all_reviews ) && 0 < count( $all_reviews ) ) {
				$all_tags = wp_get_object_terms( $all_reviews, 'cr_tag' );
			}
			//
			if ( 0 < count( $all_tags ) ) {
				$output = '';
				$unique_tags = array();
				foreach ($all_tags as $tag) {
					$tag_exists = false;
					foreach ($unique_tags as $utag) {
						if ( $utag->term_id === $tag->term_id ) {
							$tag_exists = true;
							break;
						}
					}
					if ( ! $tag_exists ) {
						$unique_tags[] = $tag;
						$output .= '<span class="cr-tags-filter cr-tag cr-tag-' . $tag->term_id . '" data-crtagid="' . $tag->term_id . '">' . esc_html( $tag->name ) . '</span> ';
					}
				}
				if ( $output ) {
					$tags_field = '<div class="cr-review-tags-filter">' . $output . '</div>';
				}
			}
			return $tags_field;
		}
	}

endif;
