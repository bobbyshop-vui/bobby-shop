jQuery(document).ready(function($) {
	jQuery('.ivole-order, [class^=ivole-o-]').click(function(e) {
		e.preventDefault();
		let classList = $(this).attr('class').split(/\s+/);
		let order_id = -1;
		for (var i = 0; i < classList.length; i++) {
			if (classList[i].startsWith('ivole-o-')) {
				order_id = parseInt(classList[i].substring(8), 10);
			}
		}
		if (order_id > -1) {
			// check if WhatsApp enabled
			if ( jQuery(this).hasClass( 'cr-whatsapp-act' ) ) {
				// if there are any previous menu elements, remove them
				let prevMenus = jQuery(this).parent().find('.cr-send-menu');
				if ( 0 < prevMenus.length ) {
					jQuery(this).parent().find('.cr-send-menu').remove();
					return;
				}
				// also remove any previous menu elements from other orders
				jQuery('.cr-send-menu').remove();
				// add the menu
				jQuery(this).after( CrManualStrings.send_button );
				let menu = jQuery(this).parent().find('.cr-send-menu');
				// position it unless the screen size is too small
				if ( 450 < jQuery( window ).width() ) {
					jQuery(this).parent().css({'position':'relative'});
					let selfPosition = jQuery(this).position();
					let selfHeight = parseFloat(jQuery(this).css('height'));
					let selfTopMargin = parseFloat(jQuery(this).css('marginTop'));
					menu.css({'top':(selfPosition.top + selfTopMargin + selfHeight/2),'left':selfPosition.left - 8});
				}
				// add a special class when sending via CR cron
				if ( jQuery(this).hasClass( 'ivole-order-cr' ) ) {
					menu.addClass('cr-send-menu-cr');
				}
				// save order ID
				menu.data( 'orderid', order_id );
				// save nonce
				const urlParams = new URLSearchParams( jQuery(this).attr('href') );
				if ( urlParams.has( 'cr_manual_reminder' ) ) {
					menu.data( 'nonce', urlParams.get( 'cr_manual_reminder' ) );
				}
				// display a special tooltip when no valid phone number is available in an order
				if ( jQuery(this).hasClass( 'cr-no-phone' ) ) {
					jQuery(this).parent().find( '.cr-send-whatsapp' ).tipTip( {
						fadeIn: 50,
						fadeOut: 50,
						delay: 200,
						keepAlive: true,
						attribute: 'data-tip',
					} );
					jQuery(this).parent().find( '.cr-send-whatsapp' ).addClass( 'cr-wa-no-phone' );
				}
				// add a special class when sending via WhatsApp API
				if ( jQuery(this).hasClass( 'cr-whatsapp-api' ) ) {
					menu.addClass('cr-send-menu-wa-api');
				}
				return;
			}
			// if there is no WhatsApp option, get nonce from the URL
			const urlParams = new URLSearchParams( jQuery(this).attr('href') );
			let nonce = '';
			if ( urlParams.has( 'cr_manual_reminder' ) ) {
				nonce = urlParams.get( 'cr_manual_reminder' );
			}
			//
			crSendReminderEmail( this, order_id, nonce );
		}
	});
	jQuery( '.wp-list-table' ).on( 'mouseleave', '.cr-send-menu:not(.cr-send-menu-wa,.cr-send-menu-wa2,.cr-send-menu-wa3,.cr-send-menu-wa4)', function() {
		jQuery( this ).remove();
	} );
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-whatsapp', function(e) {
		e.preventDefault();
		if ( ! jQuery( this ).hasClass( 'cr-wa-no-phone' ) ) {
			jQuery( this ).parent().addClass( 'cr-send-menu-wa' );
		}
		return false;
	} );
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-wa-cons', function(e) {
		e.preventDefault();
		return false;
	} );
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-wa-link', function(e) {
		if ( ! jQuery(e.target).hasClass('cr-send-wa-link-yes') ) {
			e.preventDefault();
			return false;
		}
	} );
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-wa-fbck', function(e) {
		e.preventDefault();
		return false;
	} );
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-wa-error', function(e) {
		e.preventDefault();
		return false;
	} );
	// send a WhatsApp message via App
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-wa-link .cr-send-wa-link-yes', function(e) {
		const refThis = jQuery( this );
		setTimeout(
			function() {
				crReminderWaFeedback( refThis );
			},
			5000
		);
	} );
	// send a WhatsApp message via API
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-wa-link .cr-send-wa-link-yes-api', function(e) {
		e.preventDefault();
		let orderID = jQuery(this).closest( '.cr-send-menu' ).data( 'orderid' );
		let nonce = jQuery(this).closest( '.cr-send-menu' ).data( 'nonce' );
		if ( orderID ) {
			jQuery(this).closest( '.cr-send-wa-link-btn' ).addClass( 'cr-send-wa-btn-spnr' );
			let data = {
				'action': 'cr_manual_review_reminder_wa_api',
				'order_id': orderID,
				'nonce': nonce
			};
			jQuery.post( {
				url: ajaxurl,
				data: data,
				context: this,
				success: function( response ) {
					jQuery(this).closest( '.cr-send-wa-link-btn' ).removeClass( 'cr-send-wa-btn-spnr' );
					if ( 0 === response.code ) {
						jQuery(this).closest( '.cr-send-menu' ).removeClass( 'cr-send-menu-wa2' );
						jQuery(this).closest( '.cr-send-menu' ).addClass( 'cr-send-menu-wa4' );
						jQuery(this).closest( '.cr-send-menu' ).find( '.cr-send-wa-error-msg' ).html( response.message );
						jQuery( '#post-' + response.order_id + ',#order-' + response.order_id ).find( '.ivole-review-reminder' ).text( response.reminders );
					} else {
						jQuery(this).closest( '.cr-send-menu' ).removeClass( 'cr-send-menu-wa2' );
						jQuery(this).closest( '.cr-send-menu' ).addClass( 'cr-send-menu-wa4' );
						jQuery(this).closest( '.cr-send-menu' ).find( '.cr-send-wa-error-msg' ).html( response.message );
					}
				},
				dataType: "json"
			} );
		}
		return false;
	} );
	// do not send a WhatsApp message
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-wa-link .cr-send-wa-link-no', function(e) {
		jQuery( this ).closest( '.cr-send-menu' ).remove();
	} );
	// dismiss the error message
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-wa-error .cr-send-wa-error-ok', function(e) {
		jQuery( this ).closest( '.cr-send-menu' ).remove();
	} );
	// send by email from the dropdown menu
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-email', function(e) {
		e.preventDefault();
		let orderID = jQuery(this).closest( '.cr-send-menu' ).data( 'orderid' );
		let nonce = jQuery(this).closest( '.cr-send-menu' ).data( 'nonce' );
		if ( orderID ) {
			crSendReminderEmail( this, orderID, nonce );
		}
		jQuery( this ).closest( '.cr-send-menu' ).remove();
		return false;
	} );
	// yes response to the WhatsApp consent
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-wa-cons-yes', function(e) {
		e.preventDefault();
		let orderID = jQuery(this).closest( '.cr-send-menu' ).data( 'orderid' );
		let nonce = jQuery(this).closest( '.cr-send-menu' ).data( 'nonce' );
		if ( orderID ) {
			jQuery(this).closest( '.cr-send-wa-cons-btn' ).addClass( 'cr-send-wa-btn-spnr' );
			let type = 'app';
			if ( 0 < jQuery(this).closest( '.cr-send-menu-wa-api' ).length ) {
				type = 'api';
			}
			let data = {
				'action': 'cr_manual_review_reminder_wa',
				'order_id': orderID,
				'nonce': nonce,
				'type': type
			};
			jQuery.post( {
				url: ajaxurl,
				data: data,
				context: this,
				success: function( response ) {
					jQuery(this).closest( '.cr-send-wa-cons-btn' ).removeClass( 'cr-send-wa-btn-spnr' );
					if ( 0 === response.code ) {
						jQuery(this).closest( '.cr-send-menu' ).removeClass( 'cr-send-menu-wa' );
						jQuery(this).closest( '.cr-send-menu' ).addClass( 'cr-send-menu-wa2' );
						jQuery(this).closest( '.cr-send-menu' ).find( '.cr-send-wa-link-msg' ).html( response.phone );
						jQuery(this).closest( '.cr-send-menu' ).find( '.cr-send-wa-link-yes' ).attr( 'href', response.link );
					} else if ( 100 === response.code ) {
						jQuery(this).closest( '.cr-send-menu' ).removeClass( 'cr-send-menu-wa' );
						jQuery(this).closest( '.cr-send-menu' ).addClass( 'cr-send-menu-wa2' );
						jQuery(this).closest( '.cr-send-menu' ).find( '.cr-send-wa-link-msg' ).html( response.phone );
						let yesButton = jQuery(this).closest( '.cr-send-menu' ).find( '.cr-send-wa-link-yes' );
						if ( 0 < yesButton.length ) {
							yesButton.addClass( 'cr-send-wa-link-yes-api' );
							yesButton.removeClass( 'cr-send-wa-link-yes' );
						}
					} else {
						jQuery(this).closest( '.cr-send-menu' ).removeClass( 'cr-send-menu-wa' );
						jQuery(this).closest( '.cr-send-menu' ).addClass( 'cr-send-menu-wa4' );
						jQuery(this).closest( '.cr-send-menu' ).find( '.cr-send-wa-error-msg' ).html( response.message );
					}
				},
				dataType: "json"
			} );
		}
		return false;
	} );
	// no response to the WhatsApp consent
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-wa-cons-no', function(e) {
		e.preventDefault();
		jQuery( this ).closest( '.cr-send-menu' ).remove();
		return false;
	} );
	// yes response to the feedback question
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-wa-fbck-yes', function(e) {
		jQuery(this).closest( '.cr-send-wa-fbck-btn' ).addClass( 'cr-send-wa-btn-spnr' );
		let orderID = jQuery(this).closest( '.cr-send-menu' ).data( 'orderid' );
		let nonce = jQuery(this).closest( '.cr-send-menu' ).data( 'nonce' );
		let data = {
			'action': 'cr_manual_review_reminder_conf',
			'order_id': orderID,
			'nonce': nonce
		};
		jQuery.post( {
			url: ajaxurl,
			data: data,
			context: this,
			success: function( response ) {
				jQuery(this).closest( '.cr-send-wa-fbck-btn' ).removeClass( 'cr-send-wa-btn-spnr' );
				if ( 0 === response.code ) {
					jQuery( '#post-' + response.order_id + ',#order-' + response.order_id ).find( '.ivole-review-reminder' ).text( response.message );
				}
				jQuery( this ).closest( '.cr-send-menu' ).remove();
			},
			dataType: "json"
		} );
	} );
	// no response to the feedback question
	jQuery( '.wp-list-table' ).on( 'click', '.cr-send-wa-fbck-no', function(e) {
		e.preventDefault();
		jQuery( this ).closest( '.cr-send-menu' ).remove();
		return false;
	} );
	// a function to trigger sending of an email
	function crSendReminderEmail( ref, orderID, nonce ) {
		let sending = CrManualStrings.sending;
		if(
			jQuery(this).hasClass( 'ivole-order-cr' ) ||
			0 < jQuery(this).closest( '.cr-send-menu-cr' ).length
		) {
			sending = CrManualStrings.syncing;
		}
		if (
			sending !== jQuery(ref).closest( '#post-' + orderID + ',#order-' + orderID ).find( '.ivole-review-reminder' ).text()
		) {
			jQuery(ref).closest( '#post-' + orderID + ',#order-' + orderID ).find( '.ivole-review-reminder' ).text( sending );
			let data = {
				'action': 'ivole_manual_review_reminder',
				'order_id': orderID,
				'nonce': nonce
			};
			jQuery.post(ajaxurl, data, function(response) {
				if ( response.code === 1 ) {
					jQuery( '#post-' + response.order_id + ',#order-' + response.order_id ).find( '.ivole-review-reminder' ).text( CrManualStrings.error_code_1 );
				} else if (response.code === 2) {
					jQuery( '#post-' + response.order_id + ',#order-' + response.order_id ).find( '.ivole-review-reminder' ).text( CrManualStrings.error_code_2.replace( '%s', response.message ) );
				} else {
					jQuery( '#post-' + response.order_id + ',#order-' + response.order_id ).find( '.ivole-review-reminder' ).html( response.message );
				}
			}, 'json');
		}
	}
	// a function to ask for feedback after sending a WhatsApp message
	function crReminderWaFeedback( ref ) {
		ref.closest( '.cr-send-menu' ).removeClass( 'cr-send-menu-wa2' );
		ref.closest( '.cr-send-menu' ).addClass( 'cr-send-menu-wa3' );
	}
});
