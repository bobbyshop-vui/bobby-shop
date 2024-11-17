<div class="wrap">
    <h1 class="wp-heading-inline">
    <?php
      _e( 'Questions and Answers', 'customer-reviews-woocommerce' );
    ?>
    </h1>

    <?php
    if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) {
        echo '<span class="subtitle">';
        /* translators: %s: search keywords */
        printf(
            __( 'Search results for &#8220;%s&#8221;', 'customer-reviews-woocommerce' ),
            wp_html_excerpt( esc_html( wp_unslash( $_REQUEST['s'] ) ), 50, '&hellip;' )
        );
        echo '</span>';
    }
    ?>

    <hr class="wp-header-end">

    <?php
    if ( isset( $_REQUEST['error'] ) ) {
        $error     = (int) $_REQUEST['error'];
        $error_msg = '';

        switch ( $error ) {
            case 1:
                $error_msg = __( 'Invalid question / answer ID.', 'customer-reviews-woocommerce' );
                break;
            case 2:
                $error_msg = __( 'Sorry, you are not allowed to edit questions / answers for this product.', 'customer-reviews-woocommerce' );
                break;
        }

        if ( $error_msg ) {
            echo '<div id="moderated" class="error"><p>' . $error_msg . '</p></div>';
        }
    }

    if ( isset( $_REQUEST['approved'] ) || isset( $_REQUEST['deleted'] ) || isset( $_REQUEST['trashed'] ) || isset( $_REQUEST['untrashed'] ) || isset( $_REQUEST['spammed'] ) || isset( $_REQUEST['unspammed'] ) || isset( $_REQUEST['same'] ) ) {
        $approved  = isset( $_REQUEST['approved'] ) ? (int) $_REQUEST['approved'] : 0;
        $deleted   = isset( $_REQUEST['deleted'] ) ? (int) $_REQUEST['deleted'] : 0;
        $trashed   = isset( $_REQUEST['trashed'] ) ? (int) $_REQUEST['trashed'] : 0;
        $untrashed = isset( $_REQUEST['untrashed'] ) ? (int) $_REQUEST['untrashed'] : 0;
        $spammed   = isset( $_REQUEST['spammed'] ) ? (int) $_REQUEST['spammed'] : 0;
        $unspammed = isset( $_REQUEST['unspammed'] ) ? (int) $_REQUEST['unspammed'] : 0;
        $same      = isset( $_REQUEST['same'] ) ? (int) $_REQUEST['same'] : 0;

        if ( $approved > 0 || $deleted > 0 || $trashed > 0 || $untrashed > 0 || $spammed > 0 || $unspammed > 0 || $same > 0 ) {
            if ( $approved > 0 ) {
                /* translators: %s: number of comments approved */
                $messages[] = sprintf( _n( '%s question / answer approved', '%s questions / answers approved', $approved ), $approved );
            }

            if ( $spammed > 0 ) {
                $ids = isset( $_REQUEST['ids'] ) ? $_REQUEST['ids'] : 0;
                /* translators: %s: number of comments marked as spam */
                $messages[] = sprintf( _n( '%s question / answer marked as spam.', '%s questions / answers marked as spam.', $spammed ), $spammed ) . ' <a href="' . esc_url( wp_nonce_url( "admin.php?page=cr-qna&doaction=undo&action=unspam&ids=$ids", 'bulk-comments' ) ) . '">' . __( 'Undo' ) . '</a><br />';
            }

            if ( $unspammed > 0 ) {
                /* translators: %s: number of comments restored from the spam */
                $messages[] = sprintf( _n( '%s question / answer restored from the spam', '%s questions / answers restored from the spam', $unspammed ), $unspammed );
            }

            if ( $trashed > 0 ) {
                $ids = isset( $_REQUEST['ids'] ) ? $_REQUEST['ids'] : 0;
                /* translators: %s: number of comments moved to the Trash */
                $messages[] = sprintf( _n( '%s question / answer moved to the Trash.', '%s questions / answers moved to the Trash.', $trashed ), $trashed ) . ' <a href="' . esc_url( wp_nonce_url( "admin.php?page=cr-qna&doaction=undo&action=untrash&ids=$ids", 'bulk-comments' ) ) . '">' . __( 'Undo' ) . '</a><br />';
            }

            if ( $untrashed > 0 ) {
                /* translators: %s: number of comments restored from the Trash */
                $messages[] = sprintf( _n( '%s question / answer restored from the Trash', '%s questions / answers restored from the Trash', $untrashed ), $untrashed );
            }

            if ( $deleted > 0 ) {
                /* translators: %s: number of comments permanently deleted */
                $messages[] = sprintf( _n( '%s question / answer deleted', '%s questions / answers deleted', $deleted ), $deleted );
            }

            if ( $same > 0 && $comment = get_comment( $same ) ) {
                switch ( $comment->comment_approved ) {
                    case '1':
                        $messages[] = __( 'This question / answer is already approved.' ) . ' <a href="' . esc_url( admin_url( "comment.php?action=editcomment&c=$same" ) ) . '">' . __( 'Edit question / answer', 'customer-reviews-woocommerce' ) . '</a>';
                        break;
                    case 'trash':
                        $messages[] = __( 'This question / answer is already in the Trash.' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=cr-qna&comment_status=trash' ) ) . '"> ' . __( 'View Trash' ) . '</a>';
                        break;
                    case 'spam':
                        $messages[] = __( 'This question / answer is already marked as spam.' ) . ' <a href="' . esc_url( admin_url( "comment.php?action=editcomment&c=$same" ) ) . '">' . __( 'Edit question / answer', 'customer-reviews-woocommerce' ) . '</a>';
                        break;
                }
            }

            echo '<div id="moderated" class="updated notice is-dismissible"><p>' . implode( "<br/>\n", $messages ) . '</p></div>';
        }
    }
    ?>

    <?php $list_table->views(); ?>

    <form id="comments-form" method="get">
        <?php $list_table->search_box( __( 'Search Q & A', 'customer-reviews-woocommerce' ), 'comment' ); ?>
        <input type="hidden" name="page" value="cr-qna" />
        <?php if ( $post_id ) : ?>
            <input type="hidden" name="p" value="<?php echo esc_attr( intval( $post_id ) ); ?>" />
        <?php endif; ?>
        <input type="hidden" name="comment_status" value="<?php global $comment_status; echo esc_attr($comment_status); ?>" />
        <input type="hidden" name="pagegen_timestamp" value="<?php echo esc_attr(current_time('mysql', 1)); ?>" />

        <input type="hidden" name="_total" value="<?php echo esc_attr( $list_table->get_pagination_arg( 'total_items' ) ); ?>" />
        <input type="hidden" name="_per_page" value="<?php echo esc_attr( $list_table->get_pagination_arg( 'per_page' ) ); ?>" />
        <input type="hidden" name="_page" value="<?php echo esc_attr( $list_table->get_pagination_arg( 'page' ) ); ?>" />

        <?php if ( isset( $_REQUEST['paged'] ) ) { ?>
            <input type="hidden" name="paged" value="<?php echo esc_attr( absint( $_REQUEST['paged'] ) ); ?>" />
        <?php } ?>

        <?php $list_table->display(); ?>
    </form>
</div>

<div id="ajax-response"></div>

<?php
do_action( 'cr_admin_qna_reply_form', '-1', true, 'detail' );
//wp_comment_reply('-1', true, 'detail');
wp_comment_trashnotice();
?>
