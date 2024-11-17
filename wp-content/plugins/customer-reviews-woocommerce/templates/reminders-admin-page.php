<div class="wrap">
    <?php
    if ( 'sent' === $current_tab ) {
      echo '<h1 class="wp-heading-inline">' . __( 'Sent Review Reminders', 'customer-reviews-woocommerce' ) . '</h1>';
    } else {
      echo '<h1 class="wp-heading-inline">' . __( 'Scheduled Review Reminders', 'customer-reviews-woocommerce' ) . '</h1>';
    }
    ?>

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

    $rtabs = array(
      'scheduled' => __( 'Scheduled', 'customer-reviews-woocommerce' ),
      'sent' => __( 'Sent', 'customer-reviews-woocommerce' )
    );

    $page_url = add_query_arg(
      array( 'page' => $this->menu_slug ),
      admin_url( 'admin.php' )
    );
    ?>

    <hr class="wp-header-end">

    <ul class="subsubsub">
      <?php
      $array_keys = array_keys( $rtabs );
      $last = end( $array_keys );

      foreach ( $rtabs as $tab => $label ) {
          echo '<li><a href="' . $page_url . '&tab=' . $tab . '" class="' . ( $current_tab === $tab ? 'current' : '' ) . '">' . $label . '</a> ' . ( $last === $tab ? '' : '|' ) . ' </li>';
      }
      ?>
    </ul>
    <br class="clear" />

    <div
      id="cr_reminders_top_charts"
      data-nonce="<?php echo wp_create_nonce( 'cr-reminders-top-row' ); ?>"
      data-tab="<?php echo esc_attr( $current_tab ); ?>"
      style="display:flex;margin:1em 0;"
      >
    </div>

    <?php
    if( 'cr' === get_option( 'ivole_scheduler_type', 'wp' ) ) {
      if( $list_table->get_pagination_arg( 'total_items' ) > 0 ) {
        echo '<div style="background-color:#90ee90;padding:3px 5px;margin:1em 0;font-weight:bold;">CR Cron is used for scheduling reminders (view <a href="' . admin_url( 'admin.php?page=cr-reviews-settings' ) . '" title="Plugin Settings">settings</a>). The table below might not display all reminders. Please log in to your account on <a href="https://www.cusrev.com/login.html" target="_blank" rel="noopener noreferrer">CR website</a> to view and manage the reminders.</div>';
      } else {
        echo '<div style="background-color:#90ee90;padding:3px 5px;margin:1em 0;font-weight:bold;">CR Cron is used for scheduling reminders.</div>';
      }
    }
    ?>

    <?php $list_table->views(); ?>

    <form id="reminders-form" method="get">
        <?php $list_table->search_box( __( 'Search Reminders', 'customer-reviews-woocommerce' ), 'reminders' ); ?>
        <input type="hidden" name="page" value="<?php echo esc_attr( $this->menu_slug ); ?>" />
        <input type="hidden" name="tab" value="<?php echo esc_attr( $current_tab ); ?>" />
        <input type="hidden" name="_total" value="<?php echo esc_attr( $list_table->get_pagination_arg( 'total_items' ) ); ?>" />
        <input type="hidden" name="_per_page" value="<?php echo esc_attr( $list_table->get_pagination_arg( 'per_page' ) ); ?>" />
        <input type="hidden" name="_page" value="<?php echo esc_attr( $list_table->get_pagination_arg( 'page' ) ); ?>" />

        <?php if ( isset( $_REQUEST['paged'] ) ) { ?>
            <input type="hidden" name="paged" value="<?php echo esc_attr( absint( $_REQUEST['paged'] ) ); ?>" />
        <?php } ?>

        <?php $list_table->display(); ?>
	</form>

	<script type="text/javascript">
		jQuery(function($) {
			let cr_confirm_cancel = function() {
				return confirm("<?php esc_attr_e( 'Are you sure you want to cancel the review reminder(s)?', 'customer-reviews-woocommerce' ); ?>");
			};

			let cr_confirm_send = function() {
				return confirm("<?php esc_attr_e( 'Are you sure you want to send the review reminder(s) now?', 'customer-reviews-woocommerce' ); ?>");
			};

			let cr_confirm_delete = function() {
				return confirm("<?php esc_attr_e( 'Are you sure you want to delete selected log record(s)?', 'customer-reviews-woocommerce' ); ?>");
			};

			$('.action-link.cancel').on('click', function(event) {
				if ( ! cr_confirm_cancel() ) {
					event.preventDefault();
				}
			});

			$('.action-link.send').on('click', function(event) {
				if ( ! cr_confirm_send() ) {
					event.preventDefault();
				}
			});

			$('#reminders-form').on('submit', function(event) {
				var action = $('#bulk-action-selector-top').val();
				var selected = $('input.reminder-checkbox:checked');

				if (selected.length) {
					if (action == 'cancel') {
						if ( ! cr_confirm_cancel() ) {
							event.preventDefault();
						}
					} else if (action == 'send') {
						if ( ! cr_confirm_send() ) {
							event.preventDefault();
						}
					} else if (action == 'delete') {
						if ( ! cr_confirm_delete() ) {
							event.preventDefault();
						}
					}
				}
			});
		});
	</script>
</div>
