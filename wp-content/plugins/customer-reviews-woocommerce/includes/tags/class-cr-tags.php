<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Tags' ) ) :

	class CR_Tags{
		/**
		* Constructor
		*
		* @since 3.137
		*/
		public function __construct() {
			add_action( 'init', array( $this, 'create_tags_taxonomy' ) );
			// standard WooCommerce review template
			add_action( 'woocommerce_review_after_comment_text', array( $this, 'display_tags' ), 9 );
			// enhanced CusRev review template
			add_action( 'cr_review_after_comment_text', array( $this, 'display_tags' ), 9 );
		}

		public function create_tags_taxonomy(){
			$labels = array(
				'name'          => _x( 'Tags for Reviews', 'taxonomy general name', 'customer-reviews-woocommerce' ),
				'singular_name' => _x( 'Tag', 'taxonomy singular name', 'customer-reviews-woocommerce' ),
				'search_items' =>  __( 'Search Tags', 'customer-reviews-woocommerce' ),
				'popular_items' => __( 'Popular Tags', 'customer-reviews-woocommerce' ),
				'all_items' => __( 'All Tags', 'customer-reviews-woocommerce' ),
				'parent_item' => null,
				'parent_item_colon' => null,
				'edit_item' => __( 'Edit Tag', 'customer-reviews-woocommerce' ),
				'update_item' => __( 'Update Tag', 'customer-reviews-woocommerce' ),
				'add_new_item' => __( 'Create New Tag', 'customer-reviews-woocommerce' ),
				'new_item_name' => __( 'New Tag Name', 'customer-reviews-woocommerce' ),
				'separate_items_with_commas' => __( 'Separate tags with commas', 'customer-reviews-woocommerce' ),
				'add_or_remove_items' => __( 'Add or remove tags', 'customer-reviews-woocommerce' ),
				'choose_from_most_used' => __( 'Choose from the most used tags', 'customer-reviews-woocommerce' ),
				'not_found' => __( 'No tags found', 'customer-reviews-woocommerce' ),
				'menu_name' => __( 'Tags for Reviews', 'customer-reviews-woocommerce' ),
			);

			register_taxonomy('cr_tag', 'comment', array(
				'hierarchical'          => false,
				'labels'                => $labels,
				'description'           => __( 'Tags for Reviews', 'customer-reviews-woocommerce' ),
				'show_ui'               => true,
				'show_in_menu'          => false,
				'show_in_nav_menus'     => false,
				'show_admin_column'     => true,
				'show_in_rest'          => true,
				'publicly_queryable'    => false,
				'query_var'             => true
			));
		}

		public function display_tags( $comment ){

			$review_tags = wp_get_object_terms( $comment->comment_ID, 'cr_tag' );
			$tags = '';

			/**
			* @var $tag WP_Term
			*/
			foreach( $review_tags as $tag ) {
				$tags .= '<span class="cr-tag cr-tag-' . $tag->term_id . '">' . esc_html( $tag->name ) . '</span> ';
			}

			if( !empty( $tags ) ) {
				echo '<div class="cr-review-tags">' . $tags . '</div>';
			}

		}
	}

endif;
