<?php

/**
 * Plugin Compatibility
 *
 * @package         LoginCustomizer\Includes
 * @author          WPBrigade
 * @copyright       Copyright (c) 2023, WPBrigade
 * @link            https://loginpress.pro/
 * @license         https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */
namespace LoginCustomizer\Includes;

class Compatibility {

	public function __construct() {
		$this->hooks();
	}

	/**
	 * The compatibility hooks
	 *
	 * @return void
	 */
	public function hooks() {
		if ( function_exists( 'is_plugin_active' ) ) {
			if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
				/**
				 * This filters the ID of the page/post which you want to remove from the sitemap XML.
				 *
				 * @since 2.3.2
				 *
				 * @documentation https://developer.yoast.com/features/xml-sitemaps/api/
				 */
				add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', array( $this, 'logincust_wpseo_exclude_from_sitemap' ), 10 );
			} else {
				add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'logincust_exclude_from_sitemap' ), 10, 2 );
			}
		}
	}


	/**
	 * Callback function to exclude Login Customizer page from sitemap.
	 *
	 * @return bool Exclude page/s or post/s.
	 * @since 2.3.2
	 */
	public function logincust_exclude_from_sitemap( $args, $post_type ) {
		if ( 'page' !== $post_type ) {
			return $args;
		}

		$page = get_page_by_path( 'login-customizer' );
		if ( is_object( $page ) ) {

			$args['post__not_in'] = isset( $args['post__not_in'] ) ? $args['post__not_in'] : array();

			$args['post__not_in'][] = $page->ID;
		}
		return $args;
	}

	/**
	 * Callback function to exclude Login Customizer page from sitemap.
	 *
	 * @return bool Exclude page/s or post/s.
	 * @since 2.3.2
	 */
	public function logincust_wpseo_exclude_from_sitemap() {
		$page = get_page_by_path( 'login-customizer' );
		if ( is_object( $page ) ) {
			return array( $page->ID );
		}
	}
}
