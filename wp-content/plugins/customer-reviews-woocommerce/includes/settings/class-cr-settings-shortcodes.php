<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Shortcodes_Settings' ) ):

	class CR_Shortcodes_Settings {

		/**
		* @var CR_Settings_Admin_Menu The instance of the settings admin menu
		*/
		protected $settings_menu;

		/**
		* @var string The slug of this tab
		*/
		protected $tab;

		/**
		* @var array The fields for this tab
		*/
		protected $settings;

		public function __construct( $settings_menu ) {
			$this->settings_menu = $settings_menu;

			$this->tab = 'shortcodes';

			add_filter( 'cr_settings_tabs', array( $this, 'register_tab' ) );
			add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'cr_save_settings_' . $this->tab, array( $this, 'save' ) );
			add_action( 'woocommerce_admin_field_shortcodes', array( $this, 'show_shortcodes' ) );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'Shortcodes', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			$this->init_settings();

			WC_Admin_Settings::output_fields( $this->settings );
		}

		public function save() {
			$this->init_settings();
			WC_Admin_Settings::save_fields( $this->settings );
		}

		protected function init_settings() {
			$this->settings = array(
				array(
					'title' => __( 'Shortcodes and Blocks', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'The plugin provides several shortcodes and Gutenberg blocks that you can use to display reviews in various places on your website. You can find the list of available shortcodes and their parameters below.', 'customer-reviews-woocommerce' ),
					'id'    => 'cr_options_shortcodes'
				),
				array(
					'title'   => __( 'Reviews Shortcodes', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable shortcodes and Gutenberg blocks', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_reviews_shortcode',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'id'      => 'ivole_reviews_shortcode_desc',
					'type'    => 'shortcodes'
				),
				array(
					'type' => 'sectionend',
					'id'   => 'cr_options_shortcodes'
				),
			);
		}

		public function is_this_tab() {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
		}

		public function show_shortcodes( $value ) {
			$shortcodes_desc = '<p class="cr-admin-shortcodes-large"><code>[cusrev_all_reviews]</code></p>' .
			'<p>' .__( 'Use this shortcode to display a list of all reviews on any page or post. Here are the default parameters of the shortcode:', 'customer-reviews-woocommerce' ) . '</p>' .
			'<p class="cr-admin-shortcodes"><code>[cusrev_all_reviews sort="DESC" sort_by="date" per_page="10" show_summary_bar="true" show_media="true" ' .
			'show_products="true" categories="" product_tags="" tags="" products="current" product_reviews="true" shop_reviews="true" inactive_products="false" show_replies="false" show_more="5" min_chars="0" avatars="initials" users="all" add_review="false"]</code></p>' .
			'<p class="cr-admin-shortcodes"><b>' . __( 'Parameters:', 'customer-reviews-woocommerce' ) . '</b></p>' .
			'<ul>' .
			'<li>' . sprintf( __( '%1$s argument defines how reviews are sorted. Possible values are %2$s and %3$s.', 'customer-reviews-woocommerce' ), '<code>sort</code>', '<code>"ASC"</code>', '<code>"DESC"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s to sort reviews by date and %3$s to sort reviews by upvotes.', 'customer-reviews-woocommerce' ), '<code>sort_by</code>', '<code>"date"</code>', '<code>"helpful"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument defines how many reviews will be shown at once. This argument is ignored when %2$s argument is greater than %3$s.', 'customer-reviews-woocommerce' ), '<code>per_page</code>', '<code>show_more</code>', '<code>0</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and specifies if a summary bar should be shown on top of the reviews.', 'customer-reviews-woocommerce' ), '<code>show_summary_bar</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s parameter accepts %2$s or %3$s and specifies if media files uploaded by customers should be shown in an area above the reviews.', 'customer-reviews-woocommerce' ), '<code>show_media</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and specifies if product names along with product thumbnails should be shown for each review.', 'customer-reviews-woocommerce' ), '<code>show_products</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts a comma-separated list of <a href="%2$s">product categories IDs</a> to show only reviews corresponding to specified categories of products.', 'customer-reviews-woocommerce' ), '<code>categories</code>', admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts a comma-separated list of <a href="%2$s">product tags</a>. Use this argument to show reviews from products associated with specific tags only.', 'customer-reviews-woocommerce' ), '<code>product_tags</code>', admin_url( 'edit-tags.php?taxonomy=product_tag&post_type=product' ) ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts a comma-separated list of <a href="%2$s">review tags</a>. Use this argument to show reviews associated with specific tags only.', 'customer-reviews-woocommerce' ), '<code>tags</code>', admin_url( 'admin.php?page=cr-tags' ) ) . '</li>' .
			'<li>' . sprintf( __( '%1$s parameter accepts %2$s or a comma-separated list of <a href="%3$s">product IDs</a>. Use this parameter to show reviews of the current product only or reviews corresponding to product IDs from the comma-separated list. If you provide an empty list of product IDs like %4$s, the shortcode will display reviews of all products.', 'customer-reviews-woocommerce' ), '<code>products</code>', '<code>"current"</code>', admin_url( 'edit.php?post_type=product' ), '<code>""</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and specifies if product reviews will be shown.', 'customer-reviews-woocommerce' ), '<code>product_reviews</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and specifies if general shop reviews will be shown.', 'customer-reviews-woocommerce' ), '<code>shop_reviews</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and specifies if reviews corresponding to unpublished products will be shown.', 'customer-reviews-woocommerce' ), '<code>inactive_products</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and specifies if replies to reviews will be shown.', 'customer-reviews-woocommerce' ), '<code>show_replies</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument defines the number of additional reviews to display after a user presses the \'Show more\' button. If this argument is %2$s, then \'Show more\' button will be hidden and the standard WordPress pagination will be used.', 'customer-reviews-woocommerce' ), '<code>show_more</code>', '<code>"0"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument defines the minimum number of characters that a review must have to be displayed. If this argument is %2$s, then all reviews (including rating-only reviews) will be displayed.', 'customer-reviews-woocommerce' ), '<code>min_chars</code>', '<code>"0"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s, %3$s or %4$s and defines how avatars of customers will be displayed on reviews.', 'customer-reviews-woocommerce' ), '<code>avatars</code>', '<code>"initials"</code>', '<code>"standard"</code>', '<code>"hidden"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s parameter accepts %2$s or %3$s. Use it to display reviews written by anyone or only the current WordPress user.', 'customer-reviews-woocommerce' ), '<code>users</code>', '<code>"all"</code>', '<code>"current"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s parameter accepts %2$s, %3$s or a product ID. Use it to display a form to write a new review. If the shortcode is placed on a page of a product and the parameter is set to %2$s, the review form will correspond to that product. If the shortocode is placed on a non-product page and the parameter is set to %2$s, the review form will collect shop reviews. If the parameter is set to a product ID, the review form will correspond to a product with that ID.', 'customer-reviews-woocommerce' ), '<code>add_review</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'</ul>' . '<br>' .

			'<p class="cr-admin-shortcodes-large"><code>[cusrev_reviews_grid]</code></p>' .
			'<p>' . __( 'Use this shortcode to display a grid of reviews on any page or post. Here are the default parameters of the shortcode:', 'customer-reviews-woocommerce' ) . '</p>' .
			'<p class="cr-admin-shortcodes"><code>[cusrev_reviews_grid count="3" show_products="true" product_links="true" sort_by="date" sort="DESC" categories="" product_tags="" tags="" ' .
			'products="current" color_ex_brdr="#ebebeb" color_brdr="#ebebeb" color_ex_bcrd="" color_bcrd="#ffffff" color_pr_bcrd="#f4f4f4" color_stars="#FFD707" ' .
			'shop_reviews="false" count_shop_reviews="1" inactive_products="false" avatars="initials" show_more="0" min_chars="0" show_summary_bar="false" add_review="false"]</code></p>' .
			'<p class="cr-admin-shortcodes"><b>' . __( 'Parameters:', 'customer-reviews-woocommerce' ) . '</b></p>' .
			'<ul>' .
			'<li>' . sprintf( __( '%1$s argument defines the number of product reviews to show. It is recommended to keep it between %2$s and %3$s.', 'customer-reviews-woocommerce' ), '<code>count</code>', '<code>"1"</code>', '<code>"9"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and defines if pictures and names of products corresponding to the review will be shown below the review.', 'customer-reviews-woocommerce' ), '<code>show_products</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and defines if product names will include links to product pages.', 'customer-reviews-woocommerce' ), '<code>product_links</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s to sort reviews by date, %3$s to sort reviews by rating, and %4$s to sort reviews by the number of attached images.', 'customer-reviews-woocommerce' ), '<code>sort_by</code>', '<code>"date"</code>', '<code>"rating"</code>', '<code>"media"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument defines how reviews are sorted. Possible values are %2$s, %3$s and %4$s.', 'customer-reviews-woocommerce' ), '<code>sort</code>', '<code>"ASC"</code>', '<code>"DESC"</code>', '<code>"RAND"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts a comma-separated list of <a href="%2$s">product categories IDs</a> to show only reviews corresponding to specified categories of products.', 'customer-reviews-woocommerce' ), '<code>categories</code>', admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts a comma-separated list of <a href="%2$s">product tags</a>. Use this argument to show reviews from products associated with specific tags only.', 'customer-reviews-woocommerce' ), '<code>product_tags</code>', admin_url( 'edit-tags.php?taxonomy=product_tag&post_type=product' ) ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts a comma-separated list of <a href="%2$s">review tags</a>. Use this argument to show reviews associated with specific tags only.', 'customer-reviews-woocommerce' ), '<code>tags</code>', admin_url( 'admin.php?page=cr-tags' ) ) . '</li>' .
			'<li>' . sprintf( __( '%1$s parameter accepts %2$s or a comma-separated list of <a href="%3$s">product IDs</a>. Use this parameter to show reviews of the current product only or reviews corresponding to product IDs from the comma-separated list. If you provide an empty list of product IDs like %4$s, the shortcode will display reviews of all products.', 'customer-reviews-woocommerce' ), '<code>products</code>', '<code>"current"</code>', admin_url( 'edit.php?post_type=product' ), '<code>""</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument is a hex color code of the external border around the grid of reviews.', 'customer-reviews-woocommerce' ), '<code>color_ex_brdr</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument is a hex color code of the border around review cards.', 'customer-reviews-woocommerce' ), '<code>color_brdr</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument is a hex color code of the external background of the grid.', 'customer-reviews-woocommerce' ), '<code>color_ex_bcrd</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument is a hex color code of the background of review cards.', 'customer-reviews-woocommerce' ), '<code>color_bcrd</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument is a hex color code of the background color of product areas on review cards.', 'customer-reviews-woocommerce' ), '<code>color_pr_bcrd</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument is a hex color code of rating stars on review cards.', 'customer-reviews-woocommerce' ), '<code>color_stars</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and specifies if general shop reviews will be shown.', 'customer-reviews-woocommerce' ), '<code>shop_reviews</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument defines the total number of shop reviews to show. It is recommended to keep it between %2$s and %3$s.', 'customer-reviews-woocommerce' ), '<code>count_shop_reviews</code>', '<code>"0"</code>', '<code>"3"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and specifies if reviews corresponding to unpublished products will be shown.', 'customer-reviews-woocommerce' ), '<code>inactive_products</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s, %3$s or %4$s and defines how avatars of customers will be displayed on reviews.', 'customer-reviews-woocommerce' ), '<code>avatars</code>', '<code>"initials"</code>', '<code>"standard"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument defines the number of additional reviews to display after a user presses the \'Show more\' button. If this argument is %2$s, then \'Show more\' button will be hidden.', 'customer-reviews-woocommerce' ), '<code>show_more</code>', '<code>"0"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument defines the minimum number of characters that a review must have to be displayed. If this argument is %2$s, then all reviews (including rating-only reviews) will be displayed.', 'customer-reviews-woocommerce' ), '<code>min_chars</code>', '<code>"0"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and specifies if a summary bar should be shown on top of the reviews.', 'customer-reviews-woocommerce' ), '<code>show_summary_bar</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s parameter accepts %2$s, %3$s or a product ID. Use it to display a form to write a new review. If the shortcode is placed on a page of a product and the parameter is set to %2$s, the review form will correspond to that product. If the shortocode is placed on a non-product page and the parameter is set to %2$s, the review form will collect shop reviews. If the parameter is set to a product ID, the review form will correspond to a product with that ID.', 'customer-reviews-woocommerce' ), '<code>add_review</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'</ul>' . '<br>' .
			sprintf( __( '%1$s shortcode is also available as <strong>Reviews Grid</strong> block in the new WordPress Gutenberg page editor (blocks require WordPress 5.0 or newer).', 'customer-reviews-woocommerce' ), '<code>[cusrev_reviews_grid]</code>' ) . '<br><br>' .

			'<p class="cr-admin-shortcodes-large"><code>[cusrev_reviews_slider]</code></p>' .
			'<p>' . __( 'Use this shortcode to display a slider with reviews on any page or post. Here are the default parameters of the shortcode:', 'customer-reviews-woocommerce' ) . '</p>' .
			'<p class="cr-admin-shortcodes"><code>[cusrev_reviews_slider count="5" slides_to_show="3" show_products="true" product_links="true" sort_by="date" sort="DESC" categories="" product_tags="" tags="" ' .
			'products="current" color_ex_brdr="#ebebeb" color_brdr="#ebebeb" color_ex_bcrd="" color_bcrd="#ffffff" color_pr_bcrd="#f4f4f4" color_stars="#FFD707" shop_reviews="false" count_shop_reviews="1" inactive_products="false" autoplay="false" avatars="initials" max_chars="0" min_chars="0" show_dots="true"]</code></p>' .
			'<p class="cr-admin-shortcodes"><b>' . __( 'Parameters:', 'customer-reviews-woocommerce' ) . '</b></p>' .
			'<ul>' .
			'<li>' . sprintf( __( '%1$s argument defines the number of product reviews to show. It is recommended to keep it between %2$s and %3$s. If you do not want to show product reviews, set it to %4$s and enable shop reviews (see the parameters below).', 'customer-reviews-woocommerce' ), '<code>count</code>', '<code>"0"</code>', '<code>"5"</code>', '<code>"0"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument defines the number of slides to show. It is recommended to keep it between %2$s and %3$s.', 'customer-reviews-woocommerce' ), '<code>slides_to_show</code>', '<code>"1"</code>', '<code>"4"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and defines if pictures and names of products corresponding to the review will be shown below the review.', 'customer-reviews-woocommerce' ), '<code>show_products</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and defines if product names will include links to product pages.', 'customer-reviews-woocommerce' ), '<code>product_links</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s to sort reviews by date and %3$s to sort reviews by rating.', 'customer-reviews-woocommerce' ), '<code>sort_by</code>', '<code>"date"</code>', '<code>"rating"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument defines how reviews are sorted. Possible values are %2$s, %3$s and %4$s.', 'customer-reviews-woocommerce' ), '<code>sort</code>', '<code>"ASC"</code>', '<code>"DESC"</code>', '<code>"RAND"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts a comma-separated list of <a href="%2$s">product categories IDs</a> to show only reviews corresponding to specified categories of products.', 'customer-reviews-woocommerce' ), '<code>categories</code>', admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts a comma-separated list of <a href="%2$s">product tags</a>. Use this argument to show reviews from products associated with specific tags only.', 'customer-reviews-woocommerce' ), '<code>product_tags</code>', admin_url( 'edit-tags.php?taxonomy=product_tag&post_type=product' ) ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts a comma-separated list of <a href="%2$s">review tags</a>. Use this argument to show reviews associated with specific tags only.', 'customer-reviews-woocommerce' ), '<code>tags</code>', admin_url( 'admin.php?page=cr-tags' ) ) . '</li>' .
			'<li>' . sprintf( __( '%1$s parameter accepts %2$s or a comma-separated list of <a href="%3$s">product IDs</a>. Use this parameter to show reviews of the current product only or reviews corresponding to product IDs from the comma-separated list. If you provide an empty list of product IDs like %4$s, the shortcode will display reviews of all products.', 'customer-reviews-woocommerce' ), '<code>products</code>', '<code>"current"</code>', admin_url( 'edit.php?post_type=product' ), '<code>""</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument is a hex color code of the external border around the slider.', 'customer-reviews-woocommerce' ), '<code>color_ex_brdr</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument is a hex color code of the border around review cards.', 'customer-reviews-woocommerce' ), '<code>color_brdr</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument is a hex color code of the external background of the slider.', 'customer-reviews-woocommerce' ), '<code>color_ex_bcrd</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument is a hex color code of the background of review cards.', 'customer-reviews-woocommerce' ), '<code>color_bcrd</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument is a hex color code of the background color of product areas on review cards.', 'customer-reviews-woocommerce' ), '<code>color_pr_bcrd</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument is a hex color code of rating stars on review cards.', 'customer-reviews-woocommerce' ), '<code>"color_stars"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and specifies if general shop reviews will be shown.', 'customer-reviews-woocommerce' ), '<code>shop_reviews</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument defines the total number of shop reviews to show. It is recommended to keep it between %2$s and %3$s.', 'customer-reviews-woocommerce' ), '<code>count_shop_reviews</code>', '<code>"0"</code>', '<code>"5"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and specifies if reviews corresponding to unpublished products will be shown.', 'customer-reviews-woocommerce' ), '<code>inactive_products</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and defines if the slider will slide automatically.', 'customer-reviews-woocommerce' ), '<code>autoplay</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s, %3$s or %4$s and defines how avatars of customers will be displayed on reviews.', 'customer-reviews-woocommerce' ), '<code>avatars</code>', '<code>"initials"</code>', '<code>"standard"</code>', '<code>"false"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument limits the number of characters that are displayed by default for each review. A \'Show More\' button will be added to display the remaining content for reviews that exceed this limit. If you do not want to limit the number of characters to display, set this argument to %2$s.', 'customer-reviews-woocommerce' ), '<code>max_chars</code>', '<code>"0"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument defines the minimum number of characters that a review must have to be displayed. If this argument is %2$s, then all reviews (including rating-only reviews) will be displayed.', 'customer-reviews-woocommerce' ), '<code>min_chars</code>', '<code>"0"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s argument accepts %2$s or %3$s and specifies if dot indicators will be shown.', 'customer-reviews-woocommerce' ), '<code>show_dots</code>', '<code>"true"</code>', '<code>"false"</code>' ) . '</li>' .
			'</ul>' . '<br>' .
			sprintf( __( '%1$s shortcode is also available as <strong>Reviews Slider</strong> block in the new WordPress Gutenberg page editor (blocks require WordPress 5.0 or newer).', 'customer-reviews-woocommerce' ), '<code>[cusrev_reviews_slider]</code>' ) . '<br><br>' .

			'<p class="cr-admin-shortcodes-large"><code>[cusrev_reviews_rating]</code></p>' .
			'<p>' . __( 'Use this shortcode to display a widget with rating stars on any single WooCommerce product page. Here are the default parameters of the shortcode:', 'customer-reviews-woocommerce' ) . '</p>' .
			'<p class="cr-admin-shortcodes"><code>[cusrev_reviews_slider color_stars="#FFBC00" product=""]</code></p>' .
			'<p class="cr-admin-shortcodes"><b>' . __( 'Parameters:', 'customer-reviews-woocommerce' ) . '</b></p>' .
			'<ul>' .
			'<li>' . sprintf( __( '%1$s parameter is a hex color code of rating stars.', 'customer-reviews-woocommerce' ), '<code>"color_stars"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s parameter accepts a product ID. Use this parameter to show rating stars of a specific product. If you provide an empty parameter like %2$s and place the shortcode on a WooCommerce product page, it will display a rating of that product.', 'customer-reviews-woocommerce' ), '<code>"product"</code>', '<code>""</code>' ) . '</li>' .
			'</ul>' . '<br>' .

			'<p class="cr-admin-shortcodes-large"><code>[cusrev_qna]</code></p>' .
			'<p>' . __( 'Use this shortcode to display a block with Questions and Answers on any page or post. Here are the default parameters of the shortcode:', 'customer-reviews-woocommerce' ) . '</p>' .
			'<p class="cr-admin-shortcodes"><code>[cusrev_qna products="" shop=""]</code></p>' .
			'<p class="cr-admin-shortcodes"><b>' . __( 'Parameters:', 'customer-reviews-woocommerce' ) . '</b></p>' .
			'<ul>' .
			'<li>' . sprintf( __( '%1$s parameter accepts a comma-separated list of product IDs or %2$s. If product IDs are provided, the block will display Q & A linked to the corresponding products. If the parameter is equal to %3$s, the block will display Q & A for all products in the store.', 'customer-reviews-woocommerce' ), '<code>products</code>', '<code>"all"</code>', '<code>"all"</code>' ) . '</li>' .
			'<li>' . sprintf( __( '%1$s parameter accepts a comma-separated list of non-product pages (e.g., regular WordPress pages or posts) or %2$s. If non-product page IDs are provided, the block will display Q & A linked to the corresponding non-product pages. If the parameter is equal to %3$s, the block will display Q & A for all non-product pages in the store.', 'customer-reviews-woocommerce' ), '<code>shop</code>', '<code>"all"</code>', '<code>"all"</code>' ) . '</li>' .
			'</ul>';

			$shortcodes_desc = apply_filters( 'cr_settings_shortcodes_desc', $shortcodes_desc );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
				</th>
				<td class="forminp cr-admin-shortcodes-td">
					<?php echo $shortcodes_desc; ?>
				</td>
			</tr>
			<?php
		}
	}

endif;
