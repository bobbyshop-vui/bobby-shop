<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . '/wp-admin/includes/class-wp-list-table.php';
}

class CR_Reviews_List_Table extends WP_List_Table {

	public $checkbox = true;
	public $pending_count = array();
	public $extra_items;
	private $user_can;
	private $ivole_verified_page = '';
	private $cr_ajax_enabled = false;
	private $cust_avatars = 'standard';

	/**
	* Constructor.
	*
	* @since 3.1.0
	*
	* @see WP_List_Table::__construct() for more information on default arguments.
	*
	* @global int $post_id
	*
	* @param array $args An associative array of arguments.
	*/
	public function __construct( $args = array() ) {
		global $post_id;

		$post_id = isset( $_REQUEST['p'] ) ? absint( $_REQUEST['p'] ) : 0;

		if ( get_option( 'show_avatars' ) ) {
			add_filter( 'comment_author', array( $this, 'floated_admin_avatar' ), 10, 2 );
		}

		$this->ivole_verified_page = get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() );
		$this->cr_ajax_enabled = ( 'yes' === get_option( 'ivole_ajax_reviews', 'no' ) ? true : false );
		$this->cust_avatars = $args['avatars'];

		parent::__construct( array(
			'plural'   => 'comments',
			'singular' => 'comment',
			'ajax'     => true,
			'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
		) );
	}

	public function floated_admin_avatar( $name, $comment_ID ) {
		$comment = get_comment( $comment_ID );
		$avatar = get_avatar( $comment, 32, 'mystery' );
		return "$avatar $name";
	}

	/**
	*
	* @global int    $post_id
	* @global string $comment_status
	* @global string $search
	*/
	public function prepare_items() {
		global $post_id, $comment_status, $comment_type, $search, $cr_rating_type;

		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable, 'comment' ];

		$comment_status = isset( $_REQUEST['comment_status'] ) ? $_REQUEST['comment_status'] : 'all';
		if ( ! in_array( $comment_status, array( 'all', 'moderated', 'approved', 'spam', 'trash' ) ) ) {
			$comment_status = 'all';
		}

		$comment_type = ! empty( $_REQUEST['comment_type'] ) ? $_REQUEST['comment_type'] : '';
		$cr_rating_type = ! empty( $_REQUEST['review_rating'] ) ? $_REQUEST['review_rating'] : '';

		$search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : '';

		$post_type = ( isset( $_REQUEST['post_type'] ) ) ? sanitize_key( $_REQUEST['post_type'] ) : '';

		$user_id = ( isset( $_REQUEST['user_id'] ) ) ? $_REQUEST['user_id'] : '';

		$orderby = ( isset( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : '';
		$order = ( isset( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : '';

		$comments_per_page = $this->get_per_page( $comment_status );

		$doing_ajax = wp_doing_ajax();

		if ( isset( $_REQUEST['number'] ) ) {
			$number = (int) $_REQUEST['number'];
		}
		else {
			$number = $comments_per_page + min( 8, $comments_per_page ); // Grab a few extra
		}

		$page = $this->get_pagenum();

		if ( isset( $_REQUEST['start'] ) ) {
			$start = $_REQUEST['start'];
		} else {
			$start = ( $page - 1 ) * $comments_per_page;
		}

		if ( $doing_ajax && isset( $_REQUEST['offset'] ) ) {
			$start += $_REQUEST['offset'];
		}

		// WPML compatibility to show reviews in all languages
		if( has_filter( 'wpml_object_id' ) ) {
			global $sitepress;
			if ( $sitepress ) {
				remove_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ), 10, 2 );
			}
		}

		$status_map = array(
			'moderated' => 'hold',
			'approved' => 'approve',
			'all' => '',
		);

		$args = array(
			'status' => isset( $status_map[$comment_status] ) ? $status_map[$comment_status] : $comment_status,
			'search' => $search,
			'user_id' => $user_id,
			'offset' => $start,
			'number' => $number,
			'post_id' => $post_id,
			'orderby' => $orderby,
			'order' => $order,
			'type__not_in' => array( 'cr_qna' )
		);
		if ( 'f_product_review' === $comment_type ) {
			// filter by featured product reviews
			$args['post_type'] = 'product';
			$args['meta_query'][] = array(
				'key' => 'ivole_featured',
				'compare' => '>',
				'value' => '0',
				'type' => 'NUMERIC'
			);
		} elseif ( 'product_review' === $comment_type ) {
			// filter by product reviews
			$args['post_type'] = 'product';
		} elseif ( 'f_store_review' === $comment_type ) {
			// filter by featured store reviews
			$args['post__in'] = self::get_shop_page();
			$args['meta_query'][] = array(
				'key' => 'ivole_featured',
				'compare' => '>',
				'value' => '0',
				'type' => 'NUMERIC'
			);
		} elseif ( 'store_review' === $comment_type ) {
			// filter by store reviews
			$args['post__in'] = self::get_shop_page();
		} else {
			// all reviews
			add_filter( 'comments_clauses', array( $this, 'filter_include_shop_reviews' ), 10, 1 );
		}
		if( 'all' !== $cr_rating_type && 0 < $cr_rating_type && 6 > $cr_rating_type ) {
			$args['meta_key'] = 'rating';
			$args['meta_value'] = intval( $cr_rating_type );
		}
		$_comments = get_comments( $args );

		if ( is_array( $_comments ) ) {
			update_comment_cache( $_comments );

			$this->items = array_slice( $_comments, 0, $comments_per_page );
			$this->extra_items = array_slice( $_comments, $comments_per_page );

			$_comment_post_ids = array_unique( wp_list_pluck( $_comments, 'comment_post_ID' ) );

			$this->pending_count = get_pending_comments_num( $_comment_post_ids );
		}

		if (
			'f_product_review' !== $comment_type &&
			'product_review' !== $comment_type &&
			'f_store_review' !== $comment_type &&
			'store_review' !== $comment_type
		) {
			// all reviews
			add_filter( 'comments_clauses', array( $this, 'filter_include_shop_reviews' ), 10, 1 );
		}
		$total_comments = get_comments( array_merge( $args, array(
			'count'     => true,
			'offset'    => 0,
			'number'    => 0
		) ) );

		$this->set_pagination_args( array(
			'total_items' => $total_comments,
			'per_page' => $comments_per_page,
		) );
	}

	/**
	*
	* @param string $comment_status
	* @return int
	*/
	public function get_per_page( $comment_status = 'all' ) {
		$comments_per_page = $this->get_items_per_page( 'edit_comments_per_page' );
		/**
		* Filters the number of comments listed per page in the comments list table.
		*
		* @since 2.6.0
		*
		* @param int    $comments_per_page The number of comments to list per page.
		* @param string $comment_status    The comment status name. Default 'All'.
		*/
		return apply_filters( 'comments_per_page', $comments_per_page, $comment_status );
	}

	/**
	*
	* @global string $comment_status
	*/
	public function no_items() {
		global $comment_status;

		if ( 'moderated' === $comment_status ) {
			_e( 'No reviews awaiting moderation.' );
		} else {
			_e( 'No reviews found.', 'customer-reviews-woocommerce' );
		}
	}

	/**
	*
	* @global int $post_id
	* @global string $comment_status
	*/
	protected function get_views() {
		global $post_id, $comment_status, $comment_type , $cr_rating_type;

		$status_links = array();
		$num_comments = ( $post_id ) ? $this->count_reviews( $post_id, $comment_type, $cr_rating_type ) : $this->count_reviews( 0, $comment_type, $cr_rating_type );

		$stati = array(
			'all' => _nx_noop(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				'comments'
			), // singular not used

			'moderated' => _nx_noop(
				'Pending <span class="count">(%s)</span>',
				'Pending <span class="count">(%s)</span>',
				'comments'
			),

			'approved' => _nx_noop(
				'Approved <span class="count">(%s)</span>',
				'Approved <span class="count">(%s)</span>',
				'comments'
			),

			'spam' => _nx_noop(
				'Spam <span class="count">(%s)</span>',
				'Spam <span class="count">(%s)</span>',
				'comments'
			),

			'trash' => _nx_noop(
				'Trash <span class="count">(%s)</span>',
				'Trash <span class="count">(%s)</span>',
				'comments'
			)
		);

		if ( !EMPTY_TRASH_DAYS ) unset($stati['trash']);

		$link = admin_url( 'admin.php?page=cr-reviews' );

		if ( ! empty( $comment_type ) && 'all' !== $comment_type ) {
			$link = add_query_arg( 'comment_type', $comment_type, $link );
		}

		if ( ! empty( $cr_rating_type ) && 'all' !== $cr_rating_type ) {
			$link = add_query_arg( 'review_rating', $cr_rating_type, $link );
		}

		foreach ( $stati as $status => $label ) {
			$current_link_attributes = '';

			if ( $status === $comment_status ) {
				$current_link_attributes = ' class="current" aria-current="page"';
			}

			if ( !isset( $num_comments->$status ) )
			$num_comments->$status = 10;
			$link = add_query_arg( 'comment_status', $status, $link );
			if ( $post_id )
			$link = add_query_arg( 'p', absint( $post_id ), $link );
			/*
			// I toyed with this, but decided against it. Leaving it in here in case anyone thinks it is a good idea. ~ Mark
			if ( !empty( $_REQUEST['s'] ) )
			$link = add_query_arg( 's', esc_attr( wp_unslash( $_REQUEST['s'] ) ), $link );
			*/
			$status_links[ $status ] = "<a href='$link'$current_link_attributes>" .
			sprintf( translate_nooped_plural( $label, $num_comments->$status ),
			sprintf( '<span class="%s-count">%s</span>', ( 'moderated' === $status ) ? 'pending' : $status, number_format_i18n( $num_comments->$status ) ) ) . '</a>';
		}

		/**
		* Filters the comment status links.
		*
		* @since 2.5.0
		*
		* @param array $status_links An array of fully-formed status links. Default 'All'.
		*                            Accepts 'All', 'Pending', 'Approved', 'Spam', and 'Trash'.
		*/
		return apply_filters( 'comment_status_links', $status_links );
	}

	/**
	*
	* @global string $comment_status
	*
	* @return array
	*/
	protected function get_bulk_actions() {
		global $comment_status;

		$actions = array();
		if ( in_array( $comment_status, array( 'all', 'approved' ) ) )
		$actions['unapprove'] = __( 'Unapprove' );
		if ( in_array( $comment_status, array( 'all', 'moderated' ) ) )
		$actions['approve'] = __( 'Approve' );
		if ( in_array( $comment_status, array( 'all', 'moderated', 'approved', 'trash' ) ) )
		$actions['spam'] = _x( 'Mark as Spam', 'comment' );

		if ( 'trash' === $comment_status ) {
			$actions['untrash'] = __( 'Restore' );
		} elseif ( 'spam' === $comment_status ) {
			$actions['unspam'] = _x( 'Not Spam', 'comment' );
		}

		if ( in_array( $comment_status, array( 'trash', 'spam' ) ) || !EMPTY_TRASH_DAYS )
		$actions['delete'] = __( 'Delete Permanently' );
		else
		$actions['trash'] = __( 'Move to Trash' );

		// a bulk action to remove verification from reviews
		$status = 'all';
		switch ($comment_status) {
			case 'approved':
				$status = 'approve';
				break;
			case 'moderated':
				$status = 'hold';
				break;
			default:
				$status = $comment_status;
				break;
		}
		$args = array(
			'post_status' => 'publish',
			'status' => $status,
			'meta_key' => 'ivole_order',
			'type__not_in' => 'cr_qna',
			'count' => true
		);
		if( 0 < get_comments( $args ) ) {
			$actions['unverify'] = __( 'Unverify', 'customer-reviews-woocommerce' );
		}

		return $actions;
	}

	/**
	*
	* @global string $comment_status
	*
	* @param string $which
	*/
	protected function extra_tablenav( $which ) {
		global $comment_status, $comment_type, $cr_rating_type;
		static $has_items;

		if ( ! isset( $has_items ) ) {
			$has_items = $this->has_items();
		}
		echo '<div class="alignleft actions">';
		if ( 'top' === $which ) {
			ob_start();

			$this->comment_type_dropdown( $comment_type, $cr_rating_type );

			/**
			* Fires just before the Filter submit button for comment types.
			*
			* @since 3.5.0
			*/
			do_action( 'restrict_manage_comments' );

			$output = ob_get_clean();

		if ( ! empty( $output ) /*&& $this->has_items()*/ ) {
				echo $output;
				submit_button( __( 'Filter' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
			}
		}

		if ( ( 'spam' === $comment_status || 'trash' === $comment_status ) && current_user_can( 'moderate_comments' ) && $has_items ) {
			wp_nonce_field( 'bulk-destroy', '_destroy_nonce' );
			$title = ( 'spam' === $comment_status ) ? esc_attr__( 'Empty Spam' ) : esc_attr__( 'Empty Trash' );
			submit_button( $title, 'apply', 'delete_all', false );
		}
		/**
		* Fires after the Filter submit button for comment types.
		*
		* @since 2.5.0
		*
		* @param string $comment_status The comment status name. Default 'All'.
		*/
		do_action( 'manage_comments_nav', $comment_status );
		echo '</div>';
	}

	/**
	* @return string|false
	*/
	public function current_action() {
		if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) {
			return 'delete_all';
		}

		return parent::current_action();
	}

	/**
	*
	* @global int $post_id
	*
	* @return array
	*/
	public function get_columns() {
		global $post_id;

		$columns = array();

		if ( $this->checkbox ) {
			$columns['cb'] = '<input type="checkbox" />';
		}

		$columns['author'] = __( 'Customer', 'customer-reviews-woocommerce' );
		$columns['comment'] = __( 'Review', 'customer-reviews-woocommerce' );
		$columns['tags'] = __( 'Tags', 'customer-reviews-woocommerce' );

		if ( ! $post_id ) {
			$columns['response'] = __( 'Product', 'customer-reviews-woocommerce' );
		}

		$columns['date'] = __( 'Submitted On', 'customer-reviews-woocommerce' );

		return $columns;
	}

	/**
	 * Displays a comment type drop-down for filtering on the Comments list table.
	 *
	 * @since 5.5.0
	 * @since 5.6.0 Renamed from `comment_status_dropdown()` to `comment_type_dropdown()`.
	 *
	 * @param string $comment_type The current comment type slug.
	 */
	protected function comment_type_dropdown( $comment_type, $rating_type ) {
		/**
		 * Filters the comment types shown in the drop-down menu on the Comments list table.
		 *
		 * @since 2.7.0
		 *
		 * @param string[] $comment_types Array of comment type labels keyed by their name.
		 */
		$comment_types = array(
			'f_product_review' => __( 'Featured product reviews', 'customer-reviews-woocommerce' ),
			'product_review' => __( 'Product reviews', 'customer-reviews-woocommerce' ),
			'f_store_review'   => __( 'Featured store reviews', 'customer-reviews-woocommerce' ),
			'store_review'   => __( 'Store reviews', 'customer-reviews-woocommerce' )
		);

		$rating_types = array(
			1 => __( '1 star', 'customer-reviews-woocommerce' ),
			2 => __( '2 stars', 'customer-reviews-woocommerce' ),
			3 => __( '3 stars', 'customer-reviews-woocommerce' ),
			4 => __( '4 stars', 'customer-reviews-woocommerce' ),
			5 => __( '5 stars', 'customer-reviews-woocommerce' )
		);

		if ( $comment_types && is_array( $comment_types ) ) {
			printf( '<label class="screen-reader-text" for="filter-by-comment-type">%s</label>', __( 'Filter by review type', 'customer-reviews-woocommerce' ) );

			echo '<select id="filter-by-comment-type" name="comment_type">';

			printf( "\t<option value=''>%s</option>", __( 'All review types', 'customer-reviews-woocommerce' ) );

			$shop_page_ids = self::get_shop_page();
			foreach ( $comment_types as $type => $label ) {
				$args = array(
					'number' => 1,
					'type' => 'review'
				);
				if( 'f_product_review' === $type ) {
					$args['post__not_in'] = $shop_page_ids;
					$args['meta_query'][] = array(
						'key' => 'ivole_featured',
						'compare' => '>',
						'value' => '0',
						'type' => 'NUMERIC'
					);
				}
				if( 'product_review' === $type ) {
					$args['post__not_in'] = $shop_page_ids;
				}
				if( 'f_store_review' === $type ) {
					$args['post__in'] = $shop_page_ids;
					$args['meta_query'][] = array(
						'key' => 'ivole_featured',
						'compare' => '>',
						'value' => '0',
						'type' => 'NUMERIC'
					);
				}
				if( 'store_review' === $type ) {
					$args['post__in'] = $shop_page_ids;
				}
				if ( get_comments(
					$args
				) ) {
					printf(
						"\t<option value='%s'%s>%s</option>\n",
						esc_attr( $type ),
						selected( $comment_type, $type, false ),
						esc_html( $label )
					);
				}
			}

			echo '</select>';
		}

		if ( $rating_types && is_array( $rating_types ) ) {
			printf( '<label class="screen-reader-text" for="filter-by-comment-type">%s</label>', __( 'Filter by rating', 'customer-reviews-woocommerce' ) );

			echo '<select id="filter-by-review-rating" name="review_rating">';

			printf( "\t<option value=''>%s</option>", __( 'All ratings', 'customer-reviews-woocommerce' ) );

			foreach ( $rating_types as $type => $label ) {
				$args = array(
					'number' => 1,
					'type' => 'review',
					'meta_key' => 'rating',
					'meta_value' => $type
				);
				if ( get_comments(
					$args
				) ) {
					printf(
						"\t<option value='%s'%s>%s</option>\n",
						esc_attr( $type ),
						selected( $rating_type, $type, false ),
						esc_html( $label )
					);
				}
			}

			echo '</select>';
		}

	}

	/**
	*
	* @return array
	*/
	protected function get_sortable_columns() {
		return array(
			'author'   => array( 'comment_author', false ),
			'response' => array( 'comment_post_ID', false ),
			'date'     => array( 'comment_date', false )
		);
	}

	/**
	* Get the name of the default primary column.
	*
	* @since 4.3.0
	*
	* @return string Name of the default primary column, in this case, 'comment'.
	*/
	protected function get_default_primary_column_name() {
		return 'comment';
	}

	/**
	*/
	public function display() {
		wp_nonce_field( "fetch-list-" . get_class( $this ), '_ajax_fetch_list_nonce' );

		$this->display_tablenav( 'top' );

		$this->screen->render_screen_reader_content( 'heading_list' );

		?>
		<table class="wp-list-table cr-reviews-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
			<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
			</thead>

			<tbody id="the-comment-list" data-wp-lists="list:comment">
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>

			<tbody id="the-extra-comment-list" data-wp-lists="list:comment" style="display: none;">
				<?php
				$this->items = $this->extra_items;
				$this->display_rows_or_placeholder();
				?>
			</tbody>

			<tfoot>
				<tr>
					<?php $this->print_column_headers( false ); ?>
				</tr>
			</tfoot>

		</table>
		<?php

		$this->display_tablenav( 'bottom' );
	}

	/**
	* @global WP_Post    $post
	* @global WP_Comment $comment
	*
	* @param WP_Comment $item
	*/
	public function single_row( $item ) {
		global $post, $comment;

		$comment = $item;

		$the_comment_class = wp_get_comment_status( $comment );
		if ( ! $the_comment_class ) {
			$the_comment_class = '';
		}
		$the_comment_class = join( ' ', get_comment_class( $the_comment_class, $comment, $comment->comment_post_ID ) );

		if ( $comment->comment_post_ID > 0 ) {
			$post = get_post( $comment->comment_post_ID );
		}
		$this->user_can = current_user_can( 'edit_comment', $comment->comment_ID );

		$comment->rating = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );
		if ( "" !== get_comment_meta( $comment->comment_ID, 'ivole_order', true ) ) {
			// reviews collected via CusRev in the Live mode
			$comment->via_cr = sprintf(
				'%s<img src="' . plugins_url( '/img/shield-20.png', dirname( dirname( __FILE__ ) ) ) . '" alt="' . __( 'Verified review', 'customer-reviews-woocommerce' ) . '" class="ivole-verified-badge-icon">',
				esc_html( __( 'via CR', 'customer-reviews-woocommerce' ) )
			);
		} elseif ( "" !== get_comment_meta( $comment->comment_ID, 'ivole_order_locl', true ) ) {
			// reviews collected locally
			$comment->via_cr = esc_html( __( 'via CR (Local)', 'customer-reviews-woocommerce' ) );
		} elseif ( "" !== get_comment_meta( $comment->comment_ID, 'ivole_order_priv', true ) ) {
			// reviews collected via CusRev in the Private mode
			$comment->via_cr = esc_html( __( 'via CR (Private)', 'customer-reviews-woocommerce' ) );
		} elseif ( "" !== get_comment_meta( $comment->comment_ID, 'ivole_order_unve', true ) ) {
			// reviews collected via CusRev in the Live mode with removed verification (unverified)
			$comment->via_cr = esc_html( __( 'via CR', 'customer-reviews-woocommerce' ) );
		} else {
			// other reviews
			$comment->via_cr = '';
		}

		echo "<tr id='comment-$comment->comment_ID' class='$the_comment_class'>";
		$this->single_row_columns( $comment );
		echo "</tr>\n";

		unset( $GLOBALS['post'], $GLOBALS['comment'] );
	}

	/**
	* Generate and display row actions links.
	*
	* @since 4.3.0
	*
	* @global string $comment_status Status for the current listed comments.
	*
	* @param WP_Comment $comment     The comment object.
	* @param string     $column_name Current column name.
	* @param string     $primary     Primary column name.
	* @return string|void Comment row actions output.
	*/
	protected function handle_row_actions( $comment, $column_name, $primary ) {
		global $comment_status;

		if ( $primary !== $column_name ) {
			return '';
		}

		if ( ! $this->user_can ) {
			return;
		}

		$the_comment_status = wp_get_comment_status( $comment );

		$out = '';

		$del_nonce = esc_html( '_wpnonce=' . wp_create_nonce( "delete-comment_$comment->comment_ID" ) );
		$approve_nonce = esc_html( '_wpnonce=' . wp_create_nonce( "approve-comment_$comment->comment_ID" ) );

		$url = "comment.php?c=$comment->comment_ID";

		$approve_url = esc_url( $url . "&action=approvecomment&$approve_nonce" );
		$unapprove_url = esc_url( $url . "&action=unapprovecomment&$approve_nonce" );
		$spam_url = esc_url( $url . "&action=spamcomment&$del_nonce" );
		$unspam_url = esc_url( $url . "&action=unspamcomment&$del_nonce" );
		$trash_url = esc_url( $url . "&action=trashcomment&$del_nonce" );
		$untrash_url = esc_url( $url . "&action=untrashcomment&$del_nonce" );
		$delete_url = esc_url( $url . "&action=deletecomment&$del_nonce" );

		// Preorder it: Approve | Edit | Spam | Trash.
		$actions = array(
			'approve' => '', 'unapprove' => '',
			'reply' => '',
			'edit' => '',
			'spam' => '', 'unspam' => '',
			'trash' => '', 'untrash' => '', 'delete' => ''
		);

		// Not looking at all comments.
		if ( $comment_status && 'all' != $comment_status ) {
			if ( 'approved' === $the_comment_status ) {
				$actions['unapprove'] = "<a href='$unapprove_url' data-wp-lists='delete:the-comment-list:comment-$comment->comment_ID:e7e7d3:action=dim-comment&amp;new=unapproved' class='vim-u vim-destructive' aria-label='" . esc_attr__( 'Unapprove this review' ) . "'>" . __( 'Unapprove' ) . '</a>';
			} elseif ( 'unapproved' === $the_comment_status ) {
				$actions['approve'] = "<a href='$approve_url' data-wp-lists='delete:the-comment-list:comment-$comment->comment_ID:e7e7d3:action=dim-comment&amp;new=approved' class='vim-a vim-destructive' aria-label='" . esc_attr__( 'Approve this review' ) . "'>" . __( 'Approve' ) . '</a>';
			}
		} else {
			$actions['approve'] = "<a href='$approve_url' data-wp-lists='dim:the-comment-list:comment-$comment->comment_ID:unapproved:e7e7d3:e7e7d3:new=approved' class='vim-a' aria-label='" . esc_attr__( 'Approve this review' ) . "'>" . __( 'Approve' ) . '</a>';
			$actions['unapprove'] = "<a href='$unapprove_url' data-wp-lists='dim:the-comment-list:comment-$comment->comment_ID:unapproved:e7e7d3:e7e7d3:new=unapproved' class='vim-u' aria-label='" . esc_attr__( 'Unapprove this review' ) . "'>" . __( 'Unapprove' ) . '</a>';
		}

		if ( 'spam' !== $the_comment_status ) {
			$actions['spam'] = "<a href='$spam_url' data-wp-lists='delete:the-comment-list:comment-$comment->comment_ID::spam=1' class='vim-s vim-destructive' aria-label='" . esc_attr__( 'Mark this review as spam' ) . "'>" . _x( 'Spam', 'verb' ) . '</a>';
		} elseif ( 'spam' === $the_comment_status ) {
			$actions['unspam'] = "<a href='$unspam_url' data-wp-lists='delete:the-comment-list:comment-$comment->comment_ID:66cc66:unspam=1' class='vim-z vim-destructive' aria-label='" . esc_attr__( 'Restore this review from the spam' ) . "'>" . _x( 'Not Spam', 'comment' ) . '</a>';
		}

		if ( 'trash' === $the_comment_status ) {
			$actions['untrash'] = "<a href='$untrash_url' data-wp-lists='delete:the-comment-list:comment-$comment->comment_ID:66cc66:untrash=1' class='vim-z vim-destructive' aria-label='" . esc_attr__( 'Restore this review from the Trash' ) . "'>" . __( 'Restore' ) . '</a>';
		}

		if ( 'spam' === $the_comment_status || 'trash' === $the_comment_status || !EMPTY_TRASH_DAYS ) {
			$actions['delete'] = "<a href='$delete_url' data-wp-lists='delete:the-comment-list:comment-$comment->comment_ID::delete=1' class='delete vim-d vim-destructive' aria-label='" . esc_attr__( 'Delete this review permanently' ) . "'>" . __( 'Delete Permanently' ) . '</a>';
		} else {
			$actions['trash'] = "<a href='$trash_url' data-wp-lists='delete:the-comment-list:comment-$comment->comment_ID::trash=1' class='delete vim-d vim-destructive' aria-label='" . esc_attr__( 'Move this review to the Trash' ) . "'>" . _x( 'Trash', 'verb' ) . '</a>';
		}

		if ( 'spam' !== $the_comment_status && 'trash' !== $the_comment_status ) {
			$actions['edit'] = "<a href='comment.php?action=editcomment&amp;c={$comment->comment_ID}' aria-label='" . esc_attr__( 'Edit this review' ) . "'>". __( 'Edit' ) . '</a>';

			$format = '<a data-comment-id="%d" data-post-id="%d" data-action="%s" class="%s" aria-label="%s" href="#">%s</a>';

			//if the review was received via CR, add a special class to facilitate publishing copies of replies to CR
			//if the reply corresponds to a review received via CR, also add a special class to facilitate publishing copies of new replies to CR
			$ivole_order = get_comment_meta( $comment->comment_ID, 'ivole_order', true );
			$ivole_order_class = '';
			if( $ivole_order ) {
				$ivole_order_class = ' ivole-comment-inline';
			} else {
				if( CR_Replies::isReplyForCRReview( $comment ) ) {
					$ivole_order_class = ' ivole-reply-inline';
				}
			}

			$actions['reply'] = sprintf( $format, $comment->comment_ID, $comment->comment_post_ID, 'replyto', 'vim-r comment-inline' . $ivole_order_class, esc_attr__( 'Reply to this comment' ), __( 'Reply' ) );

			if( $this->cr_ajax_enabled ) {
				if( 0 < get_comment_meta( $comment->comment_ID, 'rating', true ) ) {
					$feature_label = __( 'Feature', 'customer-reviews-woocommerce' );
					if( 0 < get_comment_meta( $comment->comment_ID, 'ivole_featured', true ) ) {
						$feature_label = __( 'Unfeature', 'customer-reviews-woocommerce' );
					}
					$feature_nonce = wp_create_nonce( "cr-feature_$comment->comment_ID" );
					$format_feature = '<a data-reviewid="%d" data-nonce="%s" class="%s" href="#">%s</a>';
					$actions['feature'] = sprintf( $format_feature, $comment->comment_ID, $feature_nonce, 'cr-feature-review-link', $feature_label );
				}
			}

			if ( $ivole_order ) {
				// add a link to remove verification of a review
				$del_verif_nonce = wp_create_nonce( "cr-del-verif_$comment->comment_ID" );
				$del_verif = '<a data-reviewid="%d" data-nonce="%s" class="%s" href="#">%s</a>';
				$actions['del_verif'] = sprintf(
					$del_verif,
					$comment->comment_ID,
					$del_verif_nonce,
					'cr-del-verif-link',
					__( 'Unverify', 'customer-reviews-woocommerce' )
				);
			}
		}

		/** This filter is documented in wp-admin/includes/dashboard.php */
		$actions = apply_filters( 'comment_row_actions', array_filter( $actions ), $comment );

		$i = 0;
		$out .= '<div class="row-actions">';
		foreach ( $actions as $action => $link ) {
			++$i;
			( ( ( 'approve' === $action || 'unapprove' === $action ) && 2 === $i ) || 1 === $i ) ? $sep = '' : $sep = ' | ';

			// Reply and quickedit need a hide-if-no-js span when not added with ajax
			if ( 'reply' === $action && ! wp_doing_ajax() )
			$action .= ' hide-if-no-js';
			elseif ( ( $action === 'untrash' && $the_comment_status === 'trash' ) || ( $action === 'unspam' && $the_comment_status === 'spam' ) ) {
				if ( '1' == get_comment_meta( $comment->comment_ID, '_wp_trash_meta_status', true ) ) {
					$action .= ' approve';
				} else {
					$action .= ' unapprove';
				}
			}

			$out .= "<span class='$action'>$sep$link</span>";
		}
		$out .= '</div>';

		$out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details' ) . '</span></button>';

		return $out;
	}

	/**
	*
	* @param WP_Comment $comment The comment object.
	*/
	public function column_cb( $comment ) {
		if ( $this->user_can ) { ?>
			<label class="screen-reader-text" for="cb-select-<?php echo $comment->comment_ID; ?>"><?php _e( 'Select review' ); ?></label>
			<input id="cb-select-<?php echo $comment->comment_ID; ?>" type="checkbox" name="delete_comments[]" value="<?php echo $comment->comment_ID; ?>" />
			<?php
		}
	}

	/**
	* @param WP_Comment $comment The comment object.
	*/
	public function column_comment( $comment ) {
		if ( $comment->comment_parent ) {
			$parent = get_comment( $comment->comment_parent );
			if ( $parent ) {
				$parent_link = esc_url( get_comment_link( $parent ) );
				$name = get_comment_author( $parent );
				echo '<p>';
				printf(
					__( 'In reply to %s', 'customer-reviews-woocommerce' ),
					'<a href="' . $parent_link . '">' . $name . '</a>'
				);
				echo '</p>';
			}
		}
		//if the reply was published to CR, add a label indicating this
		$ivole_reply = get_comment_meta( $comment->comment_ID, 'ivole_reply', true );
		if( $ivole_reply ) {
			$shop_page_ids = self::get_shop_page();
			$url_set = '<a href="' . admin_url( 'admin.php?page=cr-reviews-settings&tab=trust_badges' ) . '">plugin\'s settings</a>';
			//201 - successfully published reply from the shop
			if( 201 === $ivole_reply[0] ) {
				$order_product = CR_Replies::isReplyForCRReview( $comment );
				if( is_array( $order_product ) && $order_product[0] && $order_product[1]  ) {
					if( in_array( $order_product[1], $shop_page_ids ) ) {
						$url_cr = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/s/r-' . $order_product[0] . '" target="_blank">' . __( 'CusRev portal', 'customer-reviews-woocommerce' ) . '</a><span class="dashicons dashicons-external"></span>';
					} else {
						$url_cr = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/p/p-' . $order_product[1] . '/r-' . $order_product[0] . '" target="_blank">' . __( 'CusRev portal', 'customer-reviews-woocommerce' ) . '</a><span class="dashicons dashicons-external"></span>';
					}
					if ( isset( $_REQUEST['comment_status'] ) && $_REQUEST['comment_status'] === 'trash' ) {
						echo '<p class="ivole-reply-published-cr"><span class="ivole-reply-published-cr-span"><span class="dashicons dashicons-yes"></span> ' . sprintf( __( 'A verified copy of this reply was published on %1$s. If this reply is deleted, the verified copy will remain published. If this reply is edited, the verified copy will be updated. The page with verified copies of reviews and replies to reviews for your shop on CusRev portal can be completely hidden by disabling Trust Badges in the %2$s.', 'customer-reviews-woocommerce' ), $url_cr, $url_set ) . '</span></p>';
					} else {
						echo '<p class="ivole-reply-published-cr"><span class="ivole-reply-published-cr-span2"><span class="dashicons dashicons-yes"></span> ' . sprintf( __( 'A verified copy of this reply was published on %s', 'customer-reviews-woocommerce' ), $url_cr ) . '</span></p>';
					}
				} else {
					echo '<p class="ivole-reply-published-cr"><span class="dashicons dashicons-yes"></span> ' . __( 'A copy of this reply was published on CusRev portal', 'customer-reviews-woocommerce' ) . '</p>';
				}
				//202 - successfully received reply from the customer
			} elseif( 202 === $ivole_reply[0] ) {
				$order_product = CR_Replies::isReplyForCRReview( $comment );
				if( is_array( $order_product ) && $order_product[0] && $order_product[1]  ) {
					if( in_array( $order_product[1], $shop_page_ids ) ) {
						$url_cr = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/s/r-' . $order_product[0] . '" target="_blank">' . __( 'CusRev portal', 'customer-reviews-woocommerce' ) . '</a><span class="dashicons dashicons-external"></span>';
					} else {
						$url_cr = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/p/p-' . $order_product[1] . '/r-' . $order_product[0] . '" target="_blank">' . __( 'CusRev portal', 'customer-reviews-woocommerce' ) . '</a><span class="dashicons dashicons-external"></span>';
					}
					if ( isset( $_REQUEST['comment_status'] ) && $_REQUEST['comment_status'] === 'trash' ) {
						echo '<p class="ivole-reply-published-cr"><span class="ivole-reply-published-cr-span"><span class="dashicons dashicons-yes"></span> ' . sprintf( __( 'This reply was originally posted on %1$s. If this copy of the reply is deleted, the original on CusRev portal will remain published. If this copy is edited, the original reply will NOT be updated. Only the customer can edit the original reply on CusRev portal. The page with verified copies of reviews and replies to reviews for your shop on CusRev portal can be completely hidden by disabling Trust Badges in the %2$s.', 'customer-reviews-woocommerce' ), $url_cr, $url_set ) . '</span></p>';
					} else {
						echo '<p class="ivole-reply-published-cr"><span class="ivole-reply-published-cr-span2"><span class="dashicons dashicons-yes"></span> ' . sprintf( __( 'This reply was originally posted on %s. If this copy of the reply is edited, the original reply will NOT be updated. Only the customer can edit the original reply on CusRev portal.', 'customer-reviews-woocommerce' ), $url_cr ) . '</span></p>';
					}
				} else {
					echo '<p class="ivole-reply-published-cr"><span class="dashicons dashicons-yes"></span> ' . __( 'This reply was originally posted on CusRev portal', 'customer-reviews-woocommerce' ) . '</p>';
				}
				//203 - successfully received reply from the customer that was later manually modified
			} elseif( 203 === $ivole_reply[0] ) {
				$order_product = CR_Replies::isReplyForCRReview( $comment );
				if( is_array( $order_product ) && $order_product[0] && $order_product[1]  ) {
					if( in_array( $order_product[1], $shop_page_ids ) ) {
						$url_cr = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/s/r-' . $order_product[0] . '" target="_blank">' . __( 'CusRev portal', 'customer-reviews-woocommerce' ) . '</a><span class="dashicons dashicons-external"></span>';
					} else {
						$url_cr = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/p/p-' . $order_product[1] . '/r-' . $order_product[0] . '" target="_blank">' . __( 'CusRev portal', 'customer-reviews-woocommerce' ) . '</a><span class="dashicons dashicons-external"></span>';
					}
					if ( isset( $_REQUEST['comment_status'] ) && $_REQUEST['comment_status'] === 'trash' ) {
						echo '<p class="ivole-reply-published-cr"><span class="ivole-reply-published-cr-span"><span class="dashicons dashicons-yes"></span> ' . sprintf( __( 'This reply was originally posted on %1$s. If this copy of the reply is deleted, the original on CusRev portal will remain published. If this copy is edited, the original reply will NOT be updated. Only the customer can edit the original reply on CusRev portal. The page with verified copies of reviews and replies to reviews for your shop on CusRev portal can be completely hidden by disabling Trust Badges in the %2$s.', 'customer-reviews-woocommerce' ), $url_cr, $url_set ) . '</span></p>';
					} else {
						echo '<p class="ivole-reply-published-cr"><span class="ivole-reply-published-cr-span3"><span class="dashicons dashicons-yes"></span> ' . sprintf( __( 'This reply was originally posted on %s. This copy of the reply was modified and might be different from the original published on CusRev portal.', 'customer-reviews-woocommerce' ), $url_cr ) . '</span></p>';
					}
				} else {
					echo '<p class="ivole-reply-published-cr"><span class="dashicons dashicons-yes"></span> ' . __( 'This reply was originally posted on CusRev portal', 'customer-reviews-woocommerce' ) . '</p>';
				}
				//409 - a reply could not be updated because it is not the last one
			} elseif( 409 === $ivole_reply[0] ) {
				$order_product = CR_Replies::isReplyForCRReview( $comment );
				if( is_array( $order_product ) && $order_product[0] && $order_product[1]  ) {
					if( in_array( $order_product[1], $shop_page_ids ) ) {
						$url_cr = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/s/r-' . $order_product[0] . '" target="_blank">' . __( 'CusRev portal', 'customer-reviews-woocommerce' ) . '</a><span class="dashicons dashicons-external"></span>';
					} else {
						$url_cr = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/p/p-' . $order_product[1] . '/r-' . $order_product[0] . '" target="_blank">' . __( 'CusRev portal', 'customer-reviews-woocommerce' ) . '</a><span class="dashicons dashicons-external"></span>';
					}
					if ( isset( $_REQUEST['comment_status'] ) && $_REQUEST['comment_status'] === 'trash' ) {
						echo '<p class="ivole-reply-published-cr"><span class="ivole-reply-published-cr-span"><span class="dashicons dashicons-yes"></span> ' . sprintf( __( 'A verified copy of this reply was published on %1$s. If this reply is deleted, the verified copy will remain published. The page with verified copies of reviews and replies to reviews for your shop on CusRev portal can be completely hidden by disabling Trust Badges in the %2$s.', 'customer-reviews-woocommerce' ), $url_cr, $url_set ) . '</span></p>';
					} else {
						echo '<p class="ivole-reply-published-cr"><span class="ivole-reply-published-cr-span3"><span class="dashicons dashicons-yes"></span> ' . sprintf( __( 'A verified copy published on %s could not be updated because it is not the last reply for the review. The verified copy might be different from this reply.', 'customer-reviews-woocommerce' ), $url_cr ) . '</span></p>';
					}
				} else {
					echo '<p class="ivole-reply-published-cr"><span class="dashicons dashicons-yes"></span> ' . __( 'A copy of this reply was published on CusRev portal', 'customer-reviews-woocommerce' ) . '</p>';
				}
			} else {
				echo '<p class="ivole-reply-published-cr"><span class="dashicons dashicons-warning"></span> ' . __( 'Reply could not be published to CusRev portal. Error ', 'customer-reviews-woocommerce' ) . $ivole_reply[0] . ' (' . $ivole_reply[1] . ').</p>';
			}
		}
		?>
		<div class="comment-author">
			<?php $this->column_author( $comment );  ?>
		</div>
		<div class="cr-all-reviews-rating">
			<span class="star-rating">
				<?php
				if( $comment->rating > 0 ):
					for ( $i = 1; $i < 6; $i++ ):
						$class = ( $i <= $comment->rating ) ? 'filled': 'empty';
						?>
						<span class="dashicons dashicons-star-<?php echo $class; ?>"></span>
						<?php
					endfor;
				endif;
				?>
			</span>
			<?php if ( $comment->via_cr ): ?>
				<span style="padding-left:5px;" class="via-cr-label">
					<?php echo $comment->via_cr; ?>
				</span>
			<?php endif; ?>
		</div>
		<?php

		if( $this->cr_ajax_enabled ) {
			if( 0 < get_comment_meta( $comment->comment_ID, 'ivole_featured', true ) ) {
				$featured_class = 'cr-featured-badge-admin';
			} else {
				$featured_class = 'cr-featured-badge-admin cr-featured-badge-admin-hidden';
			}
			echo '<div class="' . $featured_class . '"><span>';
			echo __( 'Featured Review', 'customer-reviews-woocommerce' );
			echo '</span></div>';
		}

		comment_text( $comment );

		$custom_questions = new CR_Custom_Questions();
		$custom_questions->read_questions( $comment->comment_ID );
		$custom_questions->output_questions();

		$pics = get_comment_meta( $comment->comment_ID, 'ivole_review_image' );
		$pics_local = get_comment_meta( $comment->comment_ID, 'ivole_review_image2' );
		$pics_v = get_comment_meta( $comment->comment_ID, 'ivole_review_video' );
		$pics_v_local = get_comment_meta( $comment->comment_ID, 'ivole_review_video2' );
		$pics_n = count( $pics );
		$pics_local_n = count( $pics_local );
		$pics_v_n = count( $pics_v );
		$pics_v_local_n = count( $pics_v_local );
		$cr_query = '?crsrc=wp';
		if ( 0 < $pics_n || 0 < $pics_local_n || 0 < $pics_v_n || 0 < $pics_v_local_n ) {
			echo '<div class="cr-comment-images cr-comment-videos">';
			$k = 1;

			if ( $pics_n > 0 ) {
				for ( $i = 0; $i < $pics_n; $i++ ) {
					if ( isset( $pics[$i]['url'] ) ) {
						echo '<div class="iv-comment-image">';
						echo '<a href="' . $pics[$i]['url'] . $cr_query . '" class="cr-comment-a" rel="nofollow"><img src="' .
						$pics[$i]['url'] . $cr_query . '" alt="' . sprintf( __( 'Image #%1$d from ', 'customer-reviews-woocommerce' ), $k ) .
						$comment->comment_author . '"></a>';
						echo '</div>';
						$k++;
					}
				}
			}

			if ( $pics_local_n > 0 ) {
				$temp_comment_content_flag = false;
				$temp_comment_content = '';

				for ( $i = 0; $i < $pics_local_n; $i++ ) {
					$attachmentUrl = wp_get_attachment_url( $pics_local[$i] );

					if ( $attachmentUrl ) {
						$temp_comment_content_flag = true;
						$temp_comment_content .= '<div class="iv-comment-image">';
						$temp_comment_content .= '<a href="' . $attachmentUrl . '" class="cr-comment-a"><img src="' .
						$attachmentUrl . '" alt="' . sprintf( __( 'Image #%1$d from ', 'customer-reviews-woocommerce' ), $k ) .
						$comment->comment_author . '" /></a>';
						$temp_comment_content .= '</div>';
						$k++;
					}
				}

				if ( $temp_comment_content_flag ) {
					echo $temp_comment_content;
				}
			}

			$k = 1;

			if ( 0 < $pics_v_n ) {
				for ( $i = 0; $i < $pics_v_n; $i++ ) {
					echo '<div class="cr-comment-video cr-comment-video-' . $k . '">';
					echo '<div class="cr-video-cont">';
					echo '<video preload="metadata" class="cr-video-a" ';
					echo 'src="' . $pics_v[$i]['url'] . $cr_query;
					echo '"></video>';
					echo '<img class="cr-comment-videoicon" src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/video.svg" ';
					echo 'alt="' . sprintf( __( 'Video #%1$d from %2$s', 'customer-reviews-woocommerce' ), $k, $comment->comment_author ) . '">';
					echo '<button class="cr-comment-video-close">' . CR_Reviews::get_close_button_svg() . '</button>';
					echo '</div></div>';
					$k++;
				}
			}

			if ( 0 < $pics_v_local_n ) {
				$temp_comment_content_flag = false;
				$temp_comment_content = '';

				for ( $i = 0; $i < $pics_v_local_n; $i++ ) {
					$attachmentUrl = wp_get_attachment_url( $pics_v_local[$i] );

					if ( $attachmentUrl ) {
						$temp_comment_content_flag = true;
						$temp_comment_content .= '<div class="cr-comment-video cr-comment-video-' . $k . '">';
						$temp_comment_content .= '<div class="cr-video-cont">';
						$temp_comment_content .= '<video preload="metadata" class="cr-video-a" ';
						$temp_comment_content .= 'src="' . $attachmentUrl;
						$temp_comment_content .= '"></video>';
						$temp_comment_content .= '<img class="cr-comment-videoicon" src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/video.svg" ';
						$temp_comment_content .= 'alt="' . sprintf( __( 'Video #%1$d from %2$s', 'customer-reviews-woocommerce' ), $k, $comment->comment_author ) . '">';
						$temp_comment_content .= '<button class="cr-comment-video-close">' . CR_Reviews::get_close_button_svg() . '</button>';
						$temp_comment_content .= '</div></div>';
						$k++;
					}
				}

				if ( $temp_comment_content_flag ) {
					echo $temp_comment_content;
				}
			}

			echo '<div style="clear:both;"></div></div>';
		}

		if ( $this->user_can ) { ?>
			<div id="inline-<?php echo $comment->comment_ID; ?>" class="hidden">
				<textarea class="comment" rows="1" cols="1"><?php
				/** This filter is documented in wp-admin/includes/comment.php */
				echo esc_textarea( apply_filters( 'comment_edit_pre', $comment->comment_content ) );
				?></textarea>
				<div class="author-email"><?php echo esc_attr( $comment->comment_author_email ); ?></div>
				<div class="author"><?php echo esc_attr( $comment->comment_author ); ?></div>
				<div class="author-url"><?php echo esc_attr( $comment->comment_author_url ); ?></div>
				<div class="comment_status"><?php echo $comment->comment_approved; ?></div>
			</div>
			<?php
		}
	}

	/**
	*
	* @param WP_Comment $comment The comment object.
	*/
	public function column_tags( $comment ) {

		$review_tags = wp_get_object_terms( $comment->comment_ID, 'cr_tag' );
		$class_hidden = '';

		echo '<div class="cr-tags-assigned-new">';
		echo '<div class="cr-tags-assigned">';
		if( $review_tags ) {
			$output = array();

			foreach( $review_tags as $term ) {
				$output[] = '<a class="cr-tag-a" href="">'.$term->name.'</a>';
			}

			echo implode(", ", $output);
			$class_hidden = ' cr-tags-hidden';
		}
		echo '</div>';
		echo '<div class="cr-tags-new' . $class_hidden . '"><a class="cr-new-tag-a" href="" title="' . __( 'Add a tag', 'customer-reviews-woocommerce' ) . '"><span class="cr-new-tag dashicons dashicons-tag"></span></a></div>';
		echo '</div>';

		$tag_ids = array_map(function($item){
			return $item->term_id;
		}, $review_tags);
		?>
		<div class="cr-tags-edit cr-tags-hidden">
			<select class="cr_tags" multiple="multiple" name="cr_tags[]">
				<?php foreach($review_tags as $term) : ?>
					<option value="<?php echo $term->slug;?>" selected="selected"'><?php echo $term->name; ?></option>
				<?php endforeach; ?>
			</select>
			<button type="button" class="cr-button-primary" data-reviewid="<?php echo $comment->comment_ID; ?>" data-nonce="<?php echo wp_create_nonce( 'update_tags_' . $comment->comment_ID ); ?>"><span class="dashicons dashicons-saved"></span></button>
			<button type="button" class="cr-button-cancel"><span class="dashicons dashicons-no-alt"></button>
		</div>
		<?php

	}

	/**
	*
	* @global string $comment_status
	*
	* @param WP_Comment $comment The comment object.
	*/
	public function column_author( $comment ) {
		global $comment_status;

		$author_url = get_comment_author_url( $comment );

		$author_url_display = untrailingslashit( preg_replace( '|^http(s)?://(www\.)?|i', '', $author_url ) );
		if ( strlen( $author_url_display ) > 50 ) {
			$author_url_display = wp_html_excerpt( $author_url_display, 49, '&hellip;' );
		}

		if ( 'hidden' !== $this->cust_avatars ) {
			echo '<div class="cr-admin-avatar">' . get_avatar( $comment, 32, '' ) . '</div>';
		}
		echo "<strong>" . esc_html( get_comment_author( $comment ) ) . '</strong><br />';
		if ( ! empty( $author_url_display ) ) {
			printf( '<a href="%s">%s</a><br />', esc_url( $author_url ), esc_html( $author_url_display ) );
		}

		if ( $this->user_can ) {
			if ( ! empty( $comment->comment_author_email ) ) {
				/** This filter is documented in wp-includes/comment-template.php */
				$email = apply_filters( 'comment_email', $comment->comment_author_email, $comment );

				if ( ! empty( $email ) && '@' !== $email ) {
					printf( '<a href="%1$s">%2$s</a><br />', esc_url( 'mailto:' . $email ), esc_html( $email ) );
				}
			}
		}

		$author_type_label = '';
		if ( isset( $comment->user_id ) && 0 < $comment->user_id ) {
			if( user_can( $comment->user_id, 'manage_woocommerce' ) ) {
				$author_type_label = __(
					apply_filters( 'cr_reviews_store_manager', __( 'Store manager', 'customer-reviews-woocommerce' ) ),
					'customer-reviews-woocommerce'
				);
			}
		}
		if ( ! $author_type_label ) {
			if ( wc_review_is_from_verified_owner( $comment->comment_ID ) ) {
				$cr_verified_label = get_option( 'ivole_verified_owner', '' );
				if( $cr_verified_label ) {
					if ( function_exists( 'pll__' ) ) {
						$cr_verified_label = esc_html( pll__( $cr_verified_label ) );
					}
					$author_type_label = $cr_verified_label;
				} else {
					$author_type_label = __( 'Verified owner', 'customer-reviews-woocommerce' );
				}
			} else {
				$author_type_label = esc_html__( 'Reviewer', 'customer-reviews-woocommerce' );
			}
		}

		if ( $author_type_label ) {
			echo '<span class="cr-admin-store-manager">' . esc_html( $author_type_label ) . '</span>';
		}
	}

	/**
	*
	* @param WP_Comment $comment The comment object.
	*/
	public function column_date( $comment ) {
		$submitted = sprintf( __( '%1$s at %2$s' ),
		get_comment_date( __( 'Y/m/d' ), $comment ),
		get_comment_date( __( 'g:i a' ), $comment )
	);

	echo '<div class="submitted-on">';
	if ( 'approved' === wp_get_comment_status( $comment ) && ! empty ( $comment->comment_post_ID ) ) {
		printf(
			'<a href="%s">%s</a>',
			esc_url( get_comment_link( $comment ) ),
			$submitted
		);
	} else {
		echo $submitted;
	}
	echo '</div>';
}

/**
*
* @param WP_Comment $comment The comment object.
*/
public function column_response( $comment ) {
	$post = get_post();

	if ( ! $post ) {
		return;
	}

	if ( isset( $this->pending_count[$post->ID] ) ) {
		$pending_comments = $this->pending_count[$post->ID];
	} else {
		$_pending_count_temp = get_pending_comments_num( array( $post->ID ) );
		$pending_comments = $this->pending_count[$post->ID] = $_pending_count_temp[$post->ID];
	}

	if ( current_user_can( 'edit_post', $post->ID ) ) {
		$post_link = "<a href='" . get_edit_post_link( $post->ID ) . "' class='comments-edit-item-link'>";
		$post_link .= esc_html( get_the_title( $post->ID ) ) . '</a>';
	} else {
		$post_link = esc_html( get_the_title( $post->ID ) );
	}

	echo '<div class="response-links">';
	if ( 'attachment' === $post->post_type && ( $thumb = wp_get_attachment_image( $post->ID, array( 80, 60 ), true ) ) ) {
		echo $thumb;
	}
	echo $post_link;
	$post_type_object = get_post_type_object( $post->post_type );
	echo "<a href='" . get_permalink( $post->ID ) . "' class='comments-view-item-link'>" . $post_type_object->labels->view_item . '</a>';
	echo '<span class="post-com-count-wrapper post-com-count-', $post->ID, '">';
	$this->comments_bubble( $post->ID, $pending_comments );
	echo '</span> ';
	echo '</div>';
}

/**
*
* @param WP_Comment $comment     The comment object.
* @param string     $column_name The custom column's name.
*/
public function column_default( $comment, $column_name ) {
	/**
	* Fires when the default column output is displayed for a single row.
	*
	* @since 2.8.0
	*
	* @param string $column_name         The custom column's name.
	* @param int    $comment->comment_ID The custom column's unique ID number.
	*/
	do_action( 'manage_comments_custom_column', $column_name, $comment->comment_ID );
}

/**
* Custom counting function.
* Performs the same function as get_comment_count but is limited to reviews
*/
protected function count_reviews( $post_id, $comment_type, $rating_type ) {
	global $wpdb;

	$post_id = (int) $post_id;

	$count = wp_cache_get( "reviews-{$post_id}", 'counts' );
	if ( false !== $count ) {
		return $count;
	}

	$where = '';
	if ( $post_id > 0 ) {
		$where = $wpdb->prepare( "WHERE n.comment_post_ID = %d", $post_id );
	} else {
		$shop_page = self::get_shop_page();
		if( $shop_page ) {
			$in_shop_page = implode( ",", $shop_page );
		} else {
			$in_shop_page = 0;
		}
		if ( 'f_product_review' === $comment_type ) {
			// filter by featured product reviews
			$where = "WHERE n.comment_type NOT IN ( 'cr_qna' ) AND m.post_type = 'product' AND ( cm.meta_key = 'ivole_featured' AND cm.meta_value > 0 )";
		} elseif ( 'product_review' === $comment_type ) {
			// filter by product reviews
			$where = "WHERE n.comment_type NOT IN ( 'cr_qna' ) AND m.post_type = 'product'";
		} elseif ( 'f_store_review' === $comment_type ) {
			// filter by featured store reviews
			$where = "WHERE ( n.comment_type NOT IN ( 'cr_qna' ) AND n.comment_post_ID IN ( " . $in_shop_page . " ) ) AND ( cm.meta_key = 'ivole_featured' AND cm.meta_value > 0 )";
		} elseif ( 'store_review' === $comment_type ) {
			// filter by store reviews
			$where = "WHERE ( n.comment_type NOT IN ( 'cr_qna' ) AND n.comment_post_ID IN ( " . $in_shop_page . " ) )";
		} else {
			// all reviews
			$where = "WHERE ( n.comment_type NOT IN ( 'cr_qna' ) AND (m.post_type = 'product' OR n.comment_post_ID IN ( " . $in_shop_page . " ) ) )";
		}
	}

	$left_join = '';
	if( 'all' !== $rating_type && 0 < $rating_type && 6 > $rating_type ) {
		$left_join = "LEFT JOIN {$wpdb->posts} AS m ON n.comment_post_ID = m.ID LEFT JOIN {$wpdb->commentmeta} AS cm ON n.comment_ID = cm.comment_id";
		$where .= $wpdb->prepare( " AND ( cm.meta_key = 'rating' AND cm.meta_value = %d )", intval( $rating_type ) );
	} else {
		if (
			'f_product_review' === $comment_type ||
			'f_store_review' === $comment_type
		) {
			$left_join = "LEFT JOIN {$wpdb->posts} AS m ON n.comment_post_ID = m.ID LEFT JOIN {$wpdb->commentmeta} AS cm ON n.comment_ID = cm.comment_id";
		} else {
			$left_join = "LEFT JOIN {$wpdb->posts} AS m ON n.comment_post_ID = m.ID";
		}
	}

	$totals = (array) $wpdb->get_results( "
	SELECT n.comment_approved, COUNT( * ) AS total
	FROM {$wpdb->comments} AS n
	{$left_join}
	{$where}
	GROUP BY n.comment_approved
	", ARRAY_A );

	$comment_count = array(
		'approved'            => 0,
		'awaiting_moderation' => 0,
		'spam'                => 0,
		'trash'               => 0,
		'post-trashed'        => 0,
		'total_comments'      => 0,
		'all'                 => 0,
	);

	foreach ( $totals as $row ) {
		switch ( $row['comment_approved'] ) {
			case 'trash':
			$comment_count['trash'] = $row['total'];
			break;
			case 'post-trashed':
			$comment_count['post-trashed'] = $row['total'];
			break;
			case 'spam':
			$comment_count['spam'] = $row['total'];
			$comment_count['total_comments'] += $row['total'];
			break;
			case '1':
			$comment_count['approved'] = $row['total'];
			$comment_count['total_comments'] += $row['total'];
			$comment_count['all'] += $row['total'];
			break;
			case '0':
			$comment_count['awaiting_moderation'] = $row['total'];
			$comment_count['total_comments'] += $row['total'];
			$comment_count['all'] += $row['total'];
			break;
			default:
			break;
		}
	}

	$comment_count['moderated'] = $comment_count['awaiting_moderation'];
	unset( $comment_count['awaiting_moderation'] );

	$stats_object = (object) $comment_count;
	wp_cache_set( "reviews-{$post_id}", $stats_object, 'counts' );

	return $stats_object;
}

protected function comments_bubble( $post_id, $pending_comments ) {
	$approved_comments = get_comments_number();
	$approved_comments_number = number_format_i18n( $approved_comments );
	$pending_comments_number  = number_format_i18n( $pending_comments );
	$approved_only_phrase = sprintf( _n( '%s comment', '%s comments', $approved_comments ), $approved_comments_number );
	$approved_phrase      = sprintf( _n( '%s approved comment', '%s approved comments', $approved_comments ), $approved_comments_number );
	$pending_phrase       = sprintf( _n( '%s pending comment', '%s pending comments', $pending_comments ), $pending_comments_number );
	// No comments at all.
	if ( ! $approved_comments && ! $pending_comments ) {
		printf(
			'<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">%s</span>',
			__( 'No comments' )
		);
		// Approved comments have different display depending on some conditions.
	} elseif ( $approved_comments ) {
		printf( '<a href="%s" class="post-com-count post-com-count-approved"><span class="comment-count-approved" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
		esc_url(
			add_query_arg(
				array(
					'page'           => 'cr-reviews',
					'p'              => $post_id,
					'comment_status' => 'approved',
				),
				admin_url( 'admin.php' )
				)
			),
			$approved_comments_number,
			$pending_comments ? $approved_phrase : $approved_only_phrase
		);
	} else {
		printf(
			'<span class="post-com-count post-com-count-no-comments"><span class="comment-count comment-count-no-comments" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></span>',
			$approved_comments_number,
			$pending_comments ? __( 'No approved comments' ) : __( 'No comments' )
		);
	}
	if ( $pending_comments ) {
		printf(
			'<a href="%s" class="post-com-count post-com-count-pending"><span class="comment-count-pending" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
			esc_url(
				add_query_arg(
					array(
						'page'           => 'cr-reviews',
						'p'              => $post_id,
						'comment_status' => 'moderated',
					),
					admin_url( 'admin.php' )
					)
				),
				$pending_comments_number,
				$pending_phrase
			);
		} else {
			printf(
				'<span class="post-com-count post-com-count-pending post-com-count-no-pending"><span class="comment-count comment-count-no-pending" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></span>',
				$pending_comments_number,
				$approved_comments ? __( 'No pending comments' ) : __( 'No comments' )
			);
		}
	}

	public function filter_include_shop_reviews( $pieces ) {
		global $wpdb;
		$shop_page = self::get_shop_page();
		if( $shop_page ) {
			$in_shop_page = implode( ",", $shop_page );
			$pieces['join'] .= " JOIN $wpdb->posts AS crposts ON crposts.ID = $wpdb->comments.comment_post_ID";
			$pieces['where'] .= " AND ( crposts.post_type = 'product' OR comment_post_ID IN ( " . $in_shop_page . " ) )";
		} else {
			$pieces['join'] .= " JOIN $wpdb->posts AS crposts ON crposts.ID = $wpdb->comments.comment_post_ID";
			$pieces['where'] .= " AND ( crposts.post_type = 'product' )";
		}
		remove_filter( 'comments_clauses', array ( $this, 'filter_include_shop_reviews' ) );
		return $pieces;
	}

	public static function get_shop_page() {
		// normally WooCommerce has only one shop page
		// however, translation plugins can create additional translated version of the main shop page
		$shop_pages = array();
		$shop_page_id = intval( wc_get_page_id( 'shop' ) );
		if( $shop_page_id > 0 ) {
			$shop_pages = array( $shop_page_id );
			// Polylang integration
			if( function_exists( 'pll_get_post_translations' ) ) {
				$translated_shop_page_ids = pll_get_post_translations( $shop_page_id );
				if( $translated_shop_page_ids && is_array( $translated_shop_page_ids ) && count( $translated_shop_page_ids ) > 0 ) {
					$shop_pages = array_map( 'intval', $translated_shop_page_ids );
				}
			} else {
				// WPML integration
				if( has_filter( 'wpml_object_id' ) ) {
					$trid = apply_filters( 'wpml_element_trid', NULL, $shop_page_id, 'post_page' );
					if( $trid ) {
						$translations = apply_filters( 'wpml_get_element_translations', NULL, $trid, 'post_page' );
						if( $translations && is_array( $translations ) && count( $translations ) > 0 ) {
							$translated_shop_page_ids = array();
							foreach ($translations as $translation) {
								if( isset( $translation->element_id ) ) {
									$translated_shop_page_ids[] = intval( $translation->element_id );
								}
							}
							if( count( $translated_shop_page_ids ) > 0 ) {
								$shop_pages = $translated_shop_page_ids;
							}
						}
					}
				}
			}
		}
		return $shop_pages;
	}

}
