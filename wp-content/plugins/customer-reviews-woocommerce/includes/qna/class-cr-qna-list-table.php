<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . '/wp-admin/includes/class-wp-list-table.php';
}

class CR_Qna_List_Table extends WP_List_Table {

	public $checkbox = true;

	public $pending_count = array();

	public $extra_items;

	private $user_can;

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
		global $post_id, $comment_status, $search;

		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable, 'comment' ];

		$comment_status = isset( $_REQUEST['comment_status'] ) ? $_REQUEST['comment_status'] : 'all';
		if ( ! in_array( $comment_status, array( 'all', 'moderated', 'approved', 'spam', 'trash' ) ) ) {
			$comment_status = 'all';
		}

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
			'type' => 'cr_qna'
		);
		//add_filter( 'comments_clauses', array( $this, 'filter_include_shop_reviews' ), 10, 1 );
		$_comments = get_comments( $args );

		if ( is_array( $_comments ) ) {
			update_comment_cache( $_comments );

			$this->items = array_slice( $_comments, 0, $comments_per_page );
			$this->extra_items = array_slice( $_comments, $comments_per_page );

			$_comment_post_ids = array_unique( wp_list_pluck( $_comments, 'comment_post_ID' ) );

			$this->pending_count = get_pending_comments_num( $_comment_post_ids );
		}

		//add_filter( 'comments_clauses', array( $this, 'filter_include_shop_reviews' ), 10, 1 );
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
			_e( 'No questions / answers awaiting moderation.', 'customer-reviews-woocommerce' );
		} else {
			_e( 'No questions / answers found.', 'customer-reviews-woocommerce' );
		}
	}

	/**
	*
	* @global int $post_id
	* @global string $comment_status
	*/
	protected function get_views() {
		global $post_id, $comment_status;

		$status_links = array();
		$num_comments = ( $post_id ) ? $this->count_reviews( $post_id ) : $this->count_reviews();

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

		if ( !EMPTY_TRASH_DAYS )
		unset($stati['trash']);

		$link = admin_url( 'admin.php?page=cr-qna' );

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
			$status_links[ $status ] = "<a href='$link'$current_link_attributes>" . sprintf( translate_nooped_plural( $label, $num_comments->$status ),
			sprintf( '<span class="%s-count">%s</span>',
			( 'moderated' === $status ) ? 'pending' : $status,
			number_format_i18n( $num_comments->$status )
			)
			) . '</a>';
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

		return $actions;
	}

	/**
	*
	* @global string $comment_status
	*
	* @param string $which
	*/
	protected function extra_tablenav( $which ) {
		global $comment_status;
		static $has_items;

		if ( ! isset( $has_items ) ) {
			$has_items = $this->has_items();
		}
		echo '<div class="alignleft actions">';
		if ( 'top' === $which ) {
			/**
			* Fires just before the Filter submit button for comment types.
			*
			* @since 3.5.0
			*/
			do_action( 'restrict_manage_comments' );
			//submit_button( __( 'Filter' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
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
		$columns['qna_type'] = '';
		$columns['comment'] = __( 'Question / Answer', 'customer-reviews-woocommerce' );

		if ( ! $post_id ) {
			$columns['response'] = __( 'Product', 'customer-reviews-woocommerce' );
		}

		$columns['date'] = __( 'Submitted On', 'customer-reviews-woocommerce' );

		return $columns;
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
			$actions['reply'] = sprintf( $format, $comment->comment_ID, $comment->comment_post_ID, 'replyto', 'vim-r comment-inline', esc_attr__( 'Reply to this comment' ), __( 'Reply' ) );
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
		?>
		<div>
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
		</div>
		<?php
		comment_text( $comment );

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

		echo "<strong>"; comment_author( $comment ); echo '</strong><br />';
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

			$author_ip = get_comment_author_IP( $comment );
			if ( $author_ip ) {
				$author_ip_url = add_query_arg( array( 's' => $author_ip, 'mode' => 'detail' ), admin_url( 'admin.php?page=cr-qna' ) );
				if ( 'spam' === $comment_status ) {
					$author_ip_url = add_query_arg( 'comment_status', 'spam', $author_ip_url );
				}
				printf( '<a href="%1$s">%2$s</a>', esc_url( $author_ip_url ), esc_html( $author_ip ) );
			}
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

public function column_qna_type( $comment ) {
	echo '<div class="cr-qna-list-column-type">';
	if ( 0 == $comment->comment_parent ) {
		$question_svg = '<svg class="cr-qna-list-q-icon" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
		<circle cx="11" cy="11" r="10.25" stroke="#898F92" stroke-width="1.5"/>
		<path d="M11.7668 13.0628H10.1794V12.5437C10.1794 12.1066 10.2422 11.7468 10.3677 11.4645C10.4933 11.1821 10.7265 10.877 11.0673 10.5492L11.6726 9.96175C12.0852 9.57923 12.2915 9.17395 12.2915 8.7459C12.2915 8.3816 12.1749 8.08106 11.9417 7.84426C11.7175 7.60747 11.4126 7.48907 11.0269 7.48907C10.6233 7.48907 10.296 7.63024 10.0448 7.91257C9.80269 8.18579 9.67265 8.51821 9.65471 8.90984L8 8.75956C8.09865 7.90346 8.43498 7.22951 9.00897 6.7377C9.58296 6.2459 10.2915 6 11.1345 6C11.9507 6 12.6323 6.23224 13.1794 6.69672C13.7265 7.1612 14 7.80783 14 8.63661C14 9.16484 13.8969 9.5929 13.6906 9.92077C13.4843 10.2486 13.139 10.6266 12.6547 11.0546C12.287 11.3825 12.0448 11.6466 11.9283 11.847C11.8206 12.0383 11.7668 12.3251 11.7668 12.7076V13.0628ZM10.2332 15.6995C10.0179 15.4991 9.91031 15.2532 9.91031 14.9617C9.91031 14.6703 10.0135 14.4199 10.2197 14.2104C10.435 14.0009 10.6906 13.8962 10.9865 13.8962C11.2825 13.8962 11.5336 13.9964 11.7399 14.1967C11.9552 14.3971 12.0628 14.643 12.0628 14.9344C12.0628 15.2259 11.9552 15.4763 11.7399 15.6858C11.5336 15.8953 11.2825 16 10.9865 16C10.6996 16 10.4484 15.8998 10.2332 15.6995Z" fill="#898F92"/>
		</svg>';
		echo $question_svg;
	} else {
		$answer_svg = '<svg class="cr-qna-list-q-icon" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
		<g clip-path="url(#clip0)">
		<path d="M11.5386 1C11.533 1.00563 11.5217 1.00563 11.5049 1.00563C5.81081 1.00563 1.18018 5.63625 1.18018 11.3303C1.18018 13.654 1.97352 15.9103 3.42516 17.7276L1.92288 21.2161C1.79909 21.503 1.93413 21.835 2.21546 21.9532C2.31673 21.9982 2.42926 22.0094 2.53617 21.9925L8.04454 21.0248C9.14734 21.4243 10.3064 21.6268 11.4767 21.6212C17.1708 21.6212 21.8014 16.9906 21.8014 11.2965C21.8127 5.61937 17.2158 1.00563 11.5386 1ZM11.4823 20.5015C10.3964 20.5015 9.32176 20.3046 8.30336 19.922C8.20771 19.8826 8.10643 19.877 8.00515 19.8939L3.36889 20.7041L4.59548 17.8514C4.67988 17.6545 4.64612 17.4238 4.50545 17.2606C3.84152 16.4898 3.31263 15.6121 2.94128 14.6612C2.52492 13.5978 2.31111 12.4668 2.31111 11.3247C2.31111 6.24954 6.44098 2.1253 11.5105 2.1253C16.5687 2.11405 20.6761 6.20453 20.6817 11.2628C20.6817 11.274 20.6817 11.2853 20.6817 11.2965C20.6817 16.3773 16.5518 20.5015 11.4823 20.5015Z" fill="#898F92" stroke="#898F92" stroke-width="0.6"/>
		<rect x="7.29126" y="9.3999" width="8.4" height="1.575" fill="#898F92"/>
		<rect x="7.29102" y="12.0244" width="6.3" height="1.575" fill="#898F92"/>
		</g>
		<defs>
		<clipPath id="clip0">
		<rect width="22" height="22" fill="white"/>
		</clipPath>
		</defs>
		</svg>';
		echo $answer_svg;
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
protected function count_reviews( $post_id = 0 ) {
	global $wpdb;

	$post_id = (int) $post_id;

	$count = wp_cache_get( "reviews-{$post_id}", 'counts' );
	if ( false !== $count ) {
		return $count;
	}

	$where = '';
	if ( $post_id > 0 ) {
		$where = $wpdb->prepare( "WHERE n.comment_type = 'cr_qna' AND n.comment_post_ID = %d", $post_id );
	} else {
		$shop_page_id = wc_get_page_id( 'shop' );
		$shop_page_id = intval( wc_get_page_id( 'shop' ) );
		if( $shop_page_id > 0 ) {
			$in_shop_page = strval( $shop_page_id );
			// Polylang integration
			if( function_exists( 'pll_get_post_translations' ) ) {
				$translated_shop_page_ids = pll_get_post_translations( $shop_page_id );
				if( $translated_shop_page_ids && is_array( $translated_shop_page_ids ) && count( $translated_shop_page_ids ) > 0 ) {
					$in_shop_page = implode( ",", array_map( 'intval', $translated_shop_page_ids ) );
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
							if( count($translated_shop_page_ids ) > 0 ) {
								$in_shop_page = implode( ",", $translated_shop_page_ids );
							}
						}
					}
				}
			}
			$where = "WHERE ( n.comment_type = 'cr_qna' AND (m.post_type = 'product' OR n.comment_post_ID IN ( " . $in_shop_page . " ) ) )";
		} else {
			$where = "WHERE n.comment_type = 'cr_qna' AND m.post_type = 'product'";
		}
	}

	$totals = (array) $wpdb->get_results( "
	SELECT n.comment_approved, COUNT( * ) AS total
	FROM {$wpdb->comments} AS n
	LEFT JOIN {$wpdb->posts} AS m ON n.comment_post_ID = m.ID
	{$where}
	GROUP BY comment_approved
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
	$args = array(
		'status' => 'approve',
		'post_id' => $post_id,
		'type' => 'cr_qna',
		'count' => true
	);
	$approved_comments = get_comments( $args );
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
					'page'           => 'cr-qna',
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
						'page'           => 'cr-qna',
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
		$shop_page_id = intval( wc_get_page_id( 'shop' ) );
		if( $shop_page_id > 0 ) {
			$in_shop_page = strval( $shop_page_id );
			// Polylang integration
			if( function_exists( 'pll_get_post_translations' ) ) {
				$translated_shop_page_ids = pll_get_post_translations( $shop_page_id );
				if( $translated_shop_page_ids && is_array( $translated_shop_page_ids ) && count( $translated_shop_page_ids ) > 0 ) {
					$in_shop_page = implode( ",", array_map( 'intval', $translated_shop_page_ids ) );
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
							if( count($translated_shop_page_ids ) > 0 ) {
								$in_shop_page = implode( ",", $translated_shop_page_ids );
							}
						}
					}
				}
			}
			$pieces['join'] .= " JOIN $wpdb->posts AS crposts ON crposts.ID = $wpdb->comments.comment_post_ID";
			$pieces['where'] .= " AND ( crposts.post_type = 'product' OR comment_post_ID IN ( " . $in_shop_page . " ) )";
		} else {
			$pieces['join'] .= " JOIN $wpdb->posts AS crposts ON crposts.ID = $wpdb->comments.comment_post_ID";
			$pieces['where'] .= " AND ( crposts.post_type = 'product' )";
		}
		remove_filter( 'comments_clauses', array ( $this, 'filter_include_shop_reviews' ) );
		return $pieces;
	}

}
