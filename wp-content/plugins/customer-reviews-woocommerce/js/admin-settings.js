(function() {
	jQuery(document).ready(function() {
		// Select all/none
		jQuery( '.ivole-new-settings' ).on( 'click', '.select_all', function() {
			jQuery( this ).closest( 'td' ).find( 'select option' ).prop( 'selected', true );
			jQuery( this ).closest( 'td' ).find( 'select' ).trigger( 'change' );
			return false;
		});

		jQuery( '.ivole-new-settings' ).on( 'click', '.select_none', function() {
			jQuery( this ).closest( 'td' ).find( 'select option' ).prop( 'selected', false );
			jQuery( this ).closest( 'td' ).find( 'select' ).trigger( 'change' );
			return false;
		});

		jQuery( '#cr_check_duplicate_site_url' ).on( 'click', function() {
			let button = jQuery( this );
			button.next( 'span' ).addClass( 'is-active' );
			button.prop( 'disabled', true );
			jQuery.ajax(
				{
				url: ajaxurl,
				data: {
					action: 'cr_check_duplicate_site_url',
					security: button.attr( 'data-nonce' )
				}
				}
			).done( function( response ) {
				button.next( 'span' ).removeClass( 'is-active' );
				button.prop( 'disabled', false );
				button.prev( 'span' ).text( response.result );
				if( response.is_duplicate === false ) {
					button.remove();
				}
			} ).fail( function( response ) {
					button.next( 'span' ).removeClass( 'is-active' );
					button.prop( 'disabled', false );
			} );
		} );

		jQuery(".cr-trustbadgea").each(function() {
			let badge = jQuery(this).find(".cr-badge").eq(0);
			let scale = jQuery(this).width() / badge.outerWidth();
			if( 1 > scale ) {
				badge.css("transform", "scale(" + scale + ")");
			}
			badge.css("visibility", "visible");
		});

		jQuery('.cr-test-email-button').on( "click", function() {
			var is_coupon = '';
			var q_language = -1;

			if (jQuery(this).hasClass("coupon_mail")) {
				is_coupon = '_coupon';
			}

			if (typeof qTranslateConfig !== 'undefined' && typeof qTranslateConfig.qtx !== 'undefined') {
				q_language = qTranslateConfig.qtx.getActiveLanguage();
			}

			if (is_coupon == "") {
				var data = {
					'action': 'ivole_send_test_email',
					'email': jQuery(this).parent().find('input[type=text]').val(),
					'type': jQuery(this).parent().find('input[type=text]').attr('class'),
					'q_language': q_language,
					'nonce': jQuery(this).data('nonce')
				};
			} else {
				var data = {
					'action': 'ivole_send_test_email' + is_coupon,
					'email': jQuery(this).parent().find('input[type=text]').val(),
					'media_count': jQuery('#cr_email_test_media_count').val(),
					'q_language': q_language,
					'nonce': jQuery(this).data('nonce')
				};
			}

			jQuery('#ivole_test_email_status').text(cr_settings_object.sending);
			jQuery('#ivole_test_email_status').css('visibility', 'visible');
			jQuery('.cr-test-email-button').prop('disabled', true);
			jQuery.post(ajaxurl, data, function(response) {
				jQuery('#ivole_test_email_status').css('visibility', 'visible');
				jQuery('.cr-test-email-button').prop('disabled', false);

				if ( response.code === 0 ) {
					jQuery('#ivole_test_email_status').text('Success: email has been successfully sent!');
				} else if ( response.code === 1 ) {
					jQuery('#ivole_test_email_status').text('Error: email could not be sent, please check if your settings are correct and saved.');
				} else if ( response.code === 2 ) {
					jQuery('#ivole_test_email_status').text('Error: cannot connect to the email server (' + response.message + ').');
				} else if ( response.code === 13 ) {
					jQuery('#ivole_test_email_status').text('Error: "Email Subject" is empty. Please enter a string for the subject line of emails.');
				} else if ( response.code === 97 ) {
					jQuery('#ivole_test_email_status').text('Error: "Shop Name" is empty. Please enter name of your shop in the corresponding field.');
				} else if ( response.code === 99 ) {
					jQuery('#ivole_test_email_status').text('Error: please enter a valid email address!');
				} else if ( response.code === 100 ) {
					jQuery('#ivole_test_email_status').text('Error: cURL library is missing on the server.');
				} else {
					jQuery('#ivole_test_email_status').text(response.message);
				}
			}, 'json' );
		} );

		jQuery('.cr-twocols-cont .cr-twocols-chkbox').on( "click", function() {
			const container = jQuery( this ).parents( ".cr-twocols-cont" );
			const columns = container.find( ".cr-twocols-cols" );
			columns.each( function( index ) {
				if( jQuery( this ).hasClass( "cr-twocols-sel" ) ) {
					jQuery( this ).removeClass( "cr-twocols-sel" );
				} else {
					jQuery( this ).addClass( "cr-twocols-sel" );
					if( jQuery( this ).hasClass( "cr-twocols-left" ) ) {
						container.find( "input" ).val( "no" );
					}
					if( jQuery( this ).hasClass( "cr-twocols-right" ) ) {
						container.find( "input" ).val( "yes" );
					}
				}
			} );
		} );

		// enable or disable Page URL field on the CusRev tab in the settings
		jQuery('.cr-twocols-cont.cr-cusrev-mode .cr-twocols-chkbox').on( "click", function() {
			if( 0 < jQuery( this ).parents( ".cr-twocols-right" ).length ) {
				jQuery( "#ivole_reviews_verified_page" ).prop( "disabled", false );
			}
			if( 0 < jQuery( this ).parents( ".cr-twocols-left" ).length ) {
				jQuery( "#ivole_reviews_verified_page" ).prop( "disabled", true );
			}
		} );

		jQuery('.cr-settings-download-button').on( "click", function() {
			jQuery(this).addClass( "cr-settings-button-spinner" );

			const data = {
				'action': 'cr_settings_download_addon'
			};
			jQuery.post(
				{
					url: ajaxurl,
					data: data,
					context: this,
					success: function(response) {
						jQuery(this).removeClass( "cr-settings-button-spinner" );
						if( response && response["url"] ) {
							window.open( response["url"], "_blank" );
						}
					}
				}
			);

		} );

		jQuery('.cr-features-bnr-hide').on( "click", function( e ) {
			e.preventDefault();
			jQuery(this).closest( '.cr-features-banner' ).fadeTo( 500, 0.6 );
			const data = {
				'action': 'cr_settings_hide_banner',
				'banner': jQuery(this).data( 'banner' )
			};
			jQuery.post(
				{
					url: ajaxurl,
					data: data,
					context: this,
					success: function( response ) {
						jQuery(this).closest( '.cr-features-banner' ).hide();
					}
				}
			);

		} );

		// display a modal to add a question
		jQuery('.cr-cus-atts-add-attr').on( 'click', function( e ) {
			jQuery('.cr-cus-atts-modal-internal .cr-cus-atts-prev-val' ).val( '' );
			jQuery('.cr-cus-atts-modal-cont').addClass( 'cr-cus-atts-modal-visible' );
		} );

		// display a modal to a add a rating
		jQuery('.cr-rtn-crta-add-rtn').on( 'click', function( e ) {
			jQuery('.cr-rtn-crta-modal-internal .cr-rtn-crta-prev-val' ).val( '' );
			jQuery('.cr-rtn-crta-modal-cont').addClass( 'cr-rtn-crta-modal-visible' );
		} );

		// prevent propogation of a click event (questions)
		jQuery('.cr-cus-atts-modal-internal, .cr-cus-atts-del-modal-internal').on( 'click', function( e ) {
			e.stopPropagation();
		} );

		// prevent propogation of a click event (ratings)
		jQuery('.cr-rtn-crta-modal-internal, .cr-rtn-crta-del-modal-internal').on( 'click', function( e ) {
			e.stopPropagation();
		} );

		// close a modal to add an attribute
		jQuery('.cr-cus-atts-modal-close-top, .cr-cus-atts-modal-cancel, .cr-cus-atts-modal-cont').on( 'click', function( e ) {
			jQuery('.cr-cus-atts-modal-cont').removeClass( 'cr-cus-atts-modal-visible' );
		} );

		// close a modal to add a rating
		jQuery('.cr-rtn-crta-modal-close-top, .cr-rtn-crta-modal-cancel, .cr-rtn-crta-modal-cont').on( 'click', function( e ) {
			jQuery('.cr-rtn-crta-modal-cont').removeClass( 'cr-rtn-crta-modal-visible' );
		} );

		// close a modal to delete an attribute
		jQuery('.cr-cus-atts-modal-close-top, .cr-cus-atts-modal-cancel, .cr-cus-atts-del-modal-cont').on( 'click', function( e ) {
			jQuery('.cr-cus-atts-del-modal-cont').removeClass( 'cr-cus-atts-modal-visible' );
		} );

		// close a modal to delete a rating
		jQuery('.cr-rtn-crta-modal-close-top, .cr-rtn-crta-modal-cancel, .cr-rtn-crta-del-modal-cont').on( 'click', function( e ) {
			jQuery('.cr-rtn-crta-del-modal-cont').removeClass( 'cr-rtn-crta-modal-visible' );
		} );

		// close a modal on ESC button
		jQuery(document).on( 'keyup', function( e ) {
			if( e.key === "Escape" ) {
				jQuery('.cr-cus-atts-modal-cont').removeClass( 'cr-cus-atts-modal-visible' );
				jQuery('.cr-cus-atts-del-modal-cont').removeClass( 'cr-cus-atts-modal-visible' );
				jQuery('.cr-rtn-crta-modal-cont').removeClass( 'cr-rtn-crta-modal-visible' );
				jQuery('.cr-rtn-crta-del-modal-cont').removeClass( 'cr-rtn-crta-modal-visible' );
			}
		} );

		// save attribute modal
		jQuery('.cr-cus-atts-modal-cont .cr-cus-atts-modal-save').on( 'click', function( e ) {
			const attribute = jQuery(this).closest( '.cr-cus-atts-modal-internal' ).find( '#cr_cus_att_input' );
			const label = jQuery(this).closest( '.cr-cus-atts-modal-internal' ).find( '#cr_cus_att_label' );
			const type = jQuery(this).closest( '.cr-cus-atts-modal-internal' ).find( '#cr_cus_att_type' ).find( ':selected' );
			const required = jQuery(this).closest( '.cr-cus-atts-modal-internal' ).find( '#cr_cus_att_required' );
			const requiredBool = required.is(':checked') ? true : false;
			const tdAttribute = '<td>' + attribute.val() + '</td>';
			const tdLabel = '<td>' + label.val() + '</td>';
			const tdType = '<td data-attype="' + type.val() + '">' + type.text() + '</td>';
			const tdRequired = '<td data-required="' + requiredBool +'">' + ( requiredBool ? cr_settings_object.yes : cr_settings_object.no ) + '</td>';
			const tdButtonManage = '<td class="cr-cus-atts-td-menu">' + cr_settings_object.button_manage + '</td>';
			const tbody = jQuery('.cr-cus-atts-table').find( 'tbody' );
			const prevVal = jQuery(this).closest( '.cr-cus-atts-modal-internal' ).find( '.cr-cus-atts-prev-val' ).val();

			// check that the attribute is not empty
			if( 0 >= attribute.val().length ) {
				attribute.closest( '.cr-cus-atts-modal-section-row-ctn' ).addClass( 'cr-cus-atts-error' );
				return false;
			} else {
				attribute.closest( '.cr-cus-atts-modal-section-row-ctn' ).removeClass( 'cr-cus-atts-error' );
			}

			// check if the attribute already exists in the table
			const attributeAlreadyExists = tbody.find( 'tr.cr-cus-atts-tr' ).filter( function( index ) {
				// in the edit scenario, there will be a previous value of an attribute that needs to be found in the table for update
				if( prevVal ) {
					return prevVal === jQuery('td', this).eq( 0 ).text();
				} else {
					return attribute.val() === jQuery('td', this).eq( 0 ).text();
				}
			} );
			if( 0 === attributeAlreadyExists.length ) {
				tbody.append( '<tr class="cr-cus-atts-tr">' + tdAttribute + tdLabel + tdType + tdRequired + tdButtonManage + '</tr>' );
			} else {
				const tdsToUpdate = attributeAlreadyExists.eq( 0 ).find( 'td' );
				tdsToUpdate.eq( 0 ).text( attribute.val() );
				tdsToUpdate.eq( 1 ).text( label.val() );
				tdsToUpdate.eq( 2 ).data( 'attype', type.val() );
				tdsToUpdate.eq( 2 ).text( type.text() );
				tdsToUpdate.eq( 3 ).data( 'required', requiredBool );
				tdsToUpdate.eq( 3 ).text( requiredBool ? cr_settings_object.yes : cr_settings_object.no );
			}

			// remove the placeholder row
			if( 0 < tbody.find( 'tr.cr-cus-atts-tr' ).length ) {
				tbody.find( 'tr.cr-cus-atts-table-empty' ).remove();
			}

			// enable or disable 'Add Attribute' button
			if( jQuery('.cr-cus-atts-table tbody tr.cr-cus-atts-tr').length >= cr_settings_object.max_cus_atts ) {
				jQuery(this).closest( '.forminp-cr_customer_attributes' ).addClass( 'cr-cus-atts-limit' );
			} else {
				jQuery(this).closest( '.forminp-cr_customer_attributes' ).removeClass( 'cr-cus-atts-limit' );
			}

			crUpdateCusAttsInput();

			jQuery('.cr-cus-atts-modal-cont').removeClass( 'cr-cus-atts-modal-visible' );
			attribute.val( '' );
			label.val( '' );
			required.prop( 'checked', false );
		} );

		// save rating modal
		jQuery('.cr-rtn-crta-modal-cont .cr-rtn-crta-modal-save').on( 'click', function( e ) {
			const attribute = jQuery(this).closest( '.cr-rtn-crta-modal-internal' ).find( '#cr_rtn_crt_input' );
			const label = jQuery(this).closest( '.cr-rtn-crta-modal-internal' ).find( '#cr_rtn_crt_label' );
			const required = jQuery(this).closest( '.cr-rtn-crta-modal-internal' ).find( '#cr_rtn_crt_required' );
			const requiredBool = required.is(':checked') ? true : false;
			const tdAttribute = '<td>' + attribute.val() + '</td>';
			const tdLabel = '<td>' + label.val() + '</td>';
			const tdRequired = '<td data-required="' + requiredBool +'">' + ( requiredBool ? cr_settings_object.yes : cr_settings_object.no ) + '</td>';
			const tdButtonManage = '<td class="cr-cus-atts-td-menu">' + cr_settings_object.button_manage + '</td>';
			const tbody = jQuery('.cr-rtn-crta-table').find( 'tbody' );
			const prevVal = jQuery(this).closest( '.cr-rtn-crta-modal-internal' ).find( '.cr-rtn-crta-prev-val' ).val();

			// check that the attribute is not empty
			if( 0 >= attribute.val().length ) {
				attribute.closest( '.cr-rtn-crta-modal-section-row-ctn' ).addClass( 'cr-rtn-crta-error' );
				return false;
			} else {
				attribute.closest( '.cr-rtn-crta-modal-section-row-ctn' ).removeClass( 'cr-rtn-crta-error' );
			}

			// check if the attribute already exists in the table
			const attributeAlreadyExists = tbody.find( 'tr.cr-rtn-crta-tr' ).filter( function( index ) {
				// in the edit scenario, there will be a previous value of an attribute that needs to be found in the table for update
				if( prevVal ) {
					return prevVal === jQuery('td', this).eq( 0 ).text();
				} else {
					return attribute.val() === jQuery('td', this).eq( 0 ).text();
				}
			} );
			if( 0 === attributeAlreadyExists.length ) {
				tbody.append( '<tr class="cr-rtn-crta-tr">' + tdAttribute + tdLabel + tdRequired + tdButtonManage + '</tr>' );
			} else {
				const tdsToUpdate = attributeAlreadyExists.eq( 0 ).find( 'td' );
				tdsToUpdate.eq( 0 ).text( attribute.val() );
				tdsToUpdate.eq( 1 ).text( label.val() );
				tdsToUpdate.eq( 2 ).data( 'required', requiredBool );
				tdsToUpdate.eq( 2 ).text( requiredBool ? cr_settings_object.yes : cr_settings_object.no );
			}

			// remove the placeholder row
			if( 0 < tbody.find( 'tr.cr-rtn-crta-tr' ).length ) {
				tbody.find( 'tr.cr-rtn-crta-table-empty' ).remove();
			}

			// enable or disable 'Add Rating' button
			if( jQuery('.cr-rtn-crta-table tbody tr.cr-rtn-crta-tr').length >= cr_settings_object.max_rtn_crta ) {
				jQuery(this).closest( '.forminp-cr_rating_criteria' ).addClass( 'cr-rtn-crta-limit' );
			} else {
				jQuery(this).closest( '.forminp-cr_rating_criteria' ).removeClass( 'cr-rtn-crta-limit' );
			}

			crUpdateRtnCrtaInput();

			jQuery('.cr-rtn-crta-modal-cont').removeClass( 'cr-rtn-crta-modal-visible' );
			attribute.val( '' );
			label.val( '' );
			required.prop( 'checked', false );
		} );

		// manage attribute settings
		jQuery('.cr-cus-atts-table, .cr-rtn-crta-table').on( 'click', '.cr-cus-atts-button-manage', function( e ) {
			jQuery(this).parents('.cr-cus-atts-table, .cr-rtn-crta-table').find('.cr-cus-atts-menu').addClass('cr-generic-hide');
			jQuery(this).parent().find('.cr-cus-atts-menu').removeClass('cr-generic-hide');
			e.stopPropagation();
		} );

		// hide the manage settings menu when clicked anywhere on the page
		jQuery(document).on( 'click', function( e ) {
			jQuery('.cr-cus-atts-menu').addClass('cr-generic-hide');
		} );

		// move an attribute up
		jQuery('.cr-cus-atts-table').on( 'click', '.cr-cus-atts-menu-up', function( e ) {
			let currentAtt = jQuery(this).closest('.cr-cus-atts-tr');
			currentAtt.prev().insertAfter(currentAtt);
			crUpdateCusAttsInput();
		} );

		// move a rating up
		jQuery('.cr-rtn-crta-table').on( 'click', '.cr-cus-atts-menu-up', function( e ) {
			let currentAtt = jQuery(this).closest('.cr-rtn-crta-tr');
			currentAtt.prev().insertAfter(currentAtt);
			crUpdateRtnCrtaInput();
		} );

		// move an attribute down
		jQuery('.cr-cus-atts-table').on( 'click', '.cr-cus-atts-menu-down', function( e ) {
			let currentAtt = jQuery(this).closest('.cr-cus-atts-tr');
			currentAtt.next().insertBefore(currentAtt);
			crUpdateCusAttsInput();
		} );

		// move a rating down
		jQuery('.cr-rtn-crta-table').on( 'click', '.cr-cus-atts-menu-down', function( e ) {
			let currentAtt = jQuery(this).closest('.cr-rtn-crta-tr');
			currentAtt.next().insertBefore(currentAtt);
			crUpdateRtnCrtaInput();
		} );

		// edit an attribute trigger
		jQuery('.cr-cus-atts-table').on( 'click', '.cr-cus-atts-menu-edit', function( e ) {

			// current values
			const currentTds = jQuery(this).closest( '.cr-cus-atts-tr' ).find( 'td' );
			const attribute = currentTds.eq( 0 ).text();
			const label = currentTds.eq( 1 ).text();
			const type = currentTds.eq( 2 ).data( 'attype' );
			const required = currentTds.eq( 3 ).data( 'required' );

			// update values in the modal box
			jQuery('.cr-cus-atts-modal-internal .cr-cus-atts-modal-title' ).text( cr_settings_object.modal_edit );
			jQuery('.cr-cus-atts-modal-internal #cr_cus_att_input' ).val( attribute );
			jQuery('.cr-cus-atts-modal-internal #cr_cus_att_label' ).val( label );
			jQuery('.cr-cus-atts-modal-internal #cr_cus_att_type' ).val( type );
			jQuery('.cr-cus-atts-modal-internal #cr_cus_att_required' ).prop( 'checked', required );
			jQuery('.cr-cus-atts-modal-internal .cr-cus-atts-prev-val' ).val( attribute );

			jQuery('.cr-cus-atts-modal-cont').addClass( 'cr-cus-atts-modal-visible' );
		} );

		// edit a rating trigger
		jQuery('.cr-rtn-crta-table').on( 'click', '.cr-cus-atts-menu-edit', function( e ) {

			// current values
			const currentTds = jQuery(this).closest( '.cr-rtn-crta-tr' ).find( 'td' );
			const rating = currentTds.eq( 0 ).text();
			const label = currentTds.eq( 1 ).text();
			const required = currentTds.eq( 2 ).data( 'required' );

			// update values in the modal box
			jQuery('.cr-rtn-crta-modal-internal .cr-rtn-crta-modal-title' ).text( cr_settings_object.modal_edit_rtn );
			jQuery('.cr-rtn-crta-modal-internal #cr_rtn_crt_input' ).val( rating );
			jQuery('.cr-rtn-crta-modal-internal #cr_rtn_crt_label' ).val( label );
			jQuery('.cr-rtn-crta-modal-internal #cr_rtn_crt_required' ).prop( 'checked', required );
			jQuery('.cr-rtn-crta-modal-internal .cr-rtn-crta-prev-val' ).val( rating );

			jQuery('.cr-rtn-crta-modal-cont').addClass( 'cr-rtn-crta-modal-visible' );
		} );

		// delete an attribute trigger
		jQuery('.cr-cus-atts-table').on( 'click', '.cr-cus-atts-menu-delete', function( e ) {

			const attribute = jQuery(this).closest( '.cr-cus-atts-tr' ).find( 'td' ).eq( 0 );
			jQuery('.cr-cus-atts-del-modal-cont .cr-cus-atts-modal-title').text( attribute.text() );
			jQuery('.cr-cus-atts-del-modal-cont .cr-cus-atts-modal-save').data( 'attribute', attribute.text() );

			jQuery('.cr-cus-atts-del-modal-cont').addClass( 'cr-cus-atts-modal-visible' );
		} );

		// delete a rating trigger
		jQuery('.cr-rtn-crta-table').on( 'click', '.cr-cus-atts-menu-delete', function( e ) {

			const rating = jQuery(this).closest( '.cr-rtn-crta-tr' ).find( 'td' ).eq( 0 );
			jQuery('.cr-rtn-crta-del-modal-cont .cr-rtn-crta-modal-title').text( rating.text() );
			jQuery('.cr-rtn-crta-del-modal-cont .cr-rtn-crta-modal-save').data( 'rating', rating.text() );

			jQuery('.cr-rtn-crta-del-modal-cont').addClass( 'cr-rtn-crta-modal-visible' );
		} );

		// delete attribute modal
		jQuery('.cr-cus-atts-del-modal .cr-cus-atts-modal-save').on( 'click', function( e ) {
			const attribute = jQuery(this).data( 'attribute' );
			jQuery('.cr-cus-atts-table tbody tr.cr-cus-atts-tr').each( function( i ) {
				if( jQuery(this).find( 'td' ).eq( 0 ).text() === attribute ) {
					jQuery(this).remove();
					return false;
				}
			} );

			crUpdateCusAttsInput();

			// enable or disable 'Add Attribute' button
			if( jQuery('.cr-cus-atts-table tbody tr.cr-cus-atts-tr').length >= cr_settings_object.max_cus_atts ) {
				jQuery(this).closest( '.forminp-cr_customer_attributes' ).addClass( 'cr-cus-atts-limit' );
			} else {
				jQuery(this).closest( '.forminp-cr_customer_attributes' ).removeClass( 'cr-cus-atts-limit' );
			}

			// display the placeholder row
			if( 0 >= jQuery('.cr-cus-atts-table tbody tr.cr-cus-atts-tr').length ) {
				jQuery('.cr-cus-atts-table tbody' ).append( cr_settings_object.no_attributes );
			}

			jQuery('.cr-cus-atts-del-modal-cont').removeClass( 'cr-cus-atts-modal-visible' );
		} );

		// delete rating modal
		jQuery('.cr-rtn-crta-del-modal .cr-rtn-crta-modal-save').on( 'click', function( e ) {
			const rating = jQuery(this).data( 'rating' );
			jQuery('.cr-rtn-crta-table tbody tr.cr-rtn-crta-tr').each( function( i ) {
				if( jQuery(this).find( 'td' ).eq( 0 ).text() === rating ) {
					jQuery(this).remove();
					return false;
				}
			} );

			crUpdateRtnCrtaInput();

			// enable or disable 'Add Rating' button
			if( jQuery('.cr-rtn-crta-table tbody tr.cr-rtn-crta-tr').length >= cr_settings_object.max_rtn_crta ) {
				jQuery(this).closest( '.forminp-cr_rating_criteria' ).addClass( 'cr-rtn-crta-limit' );
			} else {
				jQuery(this).closest( '.forminp-cr_rating_criteria' ).removeClass( 'cr-rtn-crta-limit' );
			}

			// display the placeholder row
			if( 0 >= jQuery('.cr-rtn-crta-table tbody tr.cr-rtn-crta-tr').length ) {
				jQuery('.cr-rtn-crta-table tbody' ).append( cr_settings_object.no_ratings );
			}

			jQuery('.cr-rtn-crta-del-modal-cont').removeClass( 'cr-rtn-crta-modal-visible' );
		} );

		jQuery('.cr-test-wa-button').on( "click", function(e) {
			let data = {
				'action': 'cr_send_test_wa',
				'phone': jQuery(this).closest('td').find('input[type=text]').val(),
				'nonce': jQuery(this).data('nonce')
			};

			jQuery(this).parent().addClass('cr-test-wa-cont-validation');
			jQuery(this).closest('td').find('.cr-test-wa-status').text(cr_settings_object.wa_prepare_test);
			jQuery(this).closest('td').find('.cr-test-wa-status').css('visibility', 'visible');

			jQuery.post(
				{
					url: ajaxurl,
					data: data,
					context: this,
					success: function(response) {
						jQuery(this).parent().removeClass('cr-test-wa-cont-validation');
						if ( response.code === 0 ) {
							jQuery(this).closest('td').find('.cr-test-wa-status').text(cr_settings_object.wa_ready_test);
							jQuery(this).attr('href', response.link);
							jQuery(this).attr('target', "_blank");
							jQuery(this).parent().addClass('cr-test-wa-cont-send');
							jQuery(this).closest('td').find('.cr-test-wa-input').prop('disabled',true);
						} else {
							jQuery(this).closest('td').find('.cr-test-wa-status').text(response.message);
						}
					}
				}
			);
			if ( ! jQuery(this).parent().hasClass('cr-test-wa-cont-send') ) {
				return false;
			}
		} );

		jQuery('.cr-test-waapi-button').on( "click", function(e) {
			let data = {
				'action': 'cr_send_test_waapi',
				'test_type': jQuery(this).data('testtype'),
				'phone': jQuery(this).closest('td').find('input[type=text]').val(),
				'media_count': jQuery('#cr_wa_test_media_count').val(),
				'nonce': jQuery(this).data('nonce')
			};

			jQuery(this).parent().addClass('cr-test-wa-cont-validation');
			jQuery(this).closest('td').find('.cr-test-waapi-status').text(cr_settings_object.sending);
			jQuery(this).closest('td').find('.cr-test-waapi-status').css('visibility', 'visible');
			jQuery(this).prop('disabled', true);

			jQuery.post(
				{
					url: ajaxurl,
					data: data,
					context: this,
					success: function(response) {
						jQuery(this).parent().removeClass('cr-test-wa-cont-validation');
						jQuery(this).prop('disabled', false);
						jQuery(this).closest('td').find('.cr-test-waapi-status').text(response.message);
					}
				}
			);

			return false;
		} );

		function crUpdateCusAttsInput() {
			let cusAttsInput = [];
			jQuery('.cr-cus-atts-table tbody tr.cr-cus-atts-tr').each( function( i ) {
				const tds = jQuery(this).find( 'td' );
				cusAttsInput.push(
					{
						'attribute': tds.eq( 0 ).text(),
						'label': tds.eq( 1 ).text(),
						'type': tds.eq( 2 ).data( 'attype' ),
						'required': tds.eq( 3 ).data( 'required' )
					}
				);
			} );
			jQuery('#ivole_customer_attributes').val( JSON.stringify( cusAttsInput ) );
		}

		function crUpdateRtnCrtaInput() {
			let rtnCrtaInput = [];
			jQuery('.cr-rtn-crta-table tbody tr.cr-rtn-crta-tr').each( function( i ) {
				const tds = jQuery(this).find( 'td' );
				rtnCrtaInput.push(
					{
						'rating': tds.eq( 0 ).text(),
						'label': tds.eq( 1 ).text(),
						'required': tds.eq( 2 ).data( 'required' )
					}
				);
			} );
			jQuery('#ivole_rating_criteria').val( JSON.stringify( rtnCrtaInput ) );
		}

		if ( 0 < jQuery('#ivole_license_status').length ) {
			let data = {
				'action': 'ivole_check_license_ajax'
			};

			jQuery('#ivole_license_status').val( cr_settings_object.checking );

			jQuery.post( ajaxurl, data, function(response) {
				jQuery('#ivole_license_status').val( response.message );
				jQuery('.cr-settings-download-button').removeClass( 'cr-settings-button-spinner' );
				if( 1 !== response.code ) {
					jQuery('.cr-settings-download-button').addClass( 'disabled' );
				}
			} );
		}

		// Load of Review Reminder page and check of From Email verification
		if( jQuery('#ivole_email_from.cr-email-from-input').length > 0 || jQuery('#ivole_form_rating_bar_status').length > 0 ) {
			var data = {
				'action': 'ivole_check_license_email_ajax'
			};
			jQuery('#ivole_email_from_status').text( cr_settings_object.checking_license );
			jQuery('#ivole_email_from_name_status').text( cr_settings_object.checking_license );
			jQuery('#ivole_email_footer_status').text( cr_settings_object.checking_license );
			jQuery('#ivole_form_rating_bar_status').text( cr_settings_object.checking_license );
			jQuery('#ivole_form_geolocation_status').text( cr_settings_object.checking_license );
			jQuery.post(ajaxurl, data, function(response) {
				jQuery('#ivole_email_footer_status').css('visibility', 'visible');

				if (1 === response.license) {
					jQuery('#ivole_email_from').val( response.fromEmail );
					jQuery('#ivole_email_from').show();
					jQuery('.cr-email-verify-status').show();
					jQuery('#ivole_email_from_name').show();
					jQuery('#ivole_email_from_name').val( response.fromName );
					jQuery('#ivole_email_from_name_status').hide();
					jQuery('#ivole_email_footer').show();
					jQuery('#ivole_email_footer').val( response.emailFooter );
					jQuery('#ivole_email_footer_status').text( cr_settings_object.footer_status );
					jQuery('#ivole_form_rating_bar_fs').show();
					jQuery('#ivole_form_rating_bar_status').hide();
					jQuery('#ivole_form_geolocation_fs').show();
					jQuery('#ivole_form_geolocation_status').hide();

					if (1 === response.email) {
						jQuery('.cr-email-verify-status-ind').css('background', '#00FF00');
						jQuery('.cr-email-verify-status-ind').text( 'Verified' );
						jQuery('#ivole_email_from_status').text( '' );
						jQuery('#ivole_email_from_status').hide();
						jQuery('.cr-dkim-verify-status').show();
						jQuery('.cr-dkim-verify-status-ind').css('background', '#FA8072');
						jQuery('.cr-dkim-verify-status-ind').text( cr_settings_object.dns_disabled );
						if ( response.dkim ) {
							if ( 1 === response.dkim.code ) {
								jQuery('.cr-dkim-verify-status-ind').css('background', '#00FF00');
								jQuery('.cr-dkim-verify-status-ind').text( cr_settings_object.dns_enabled );
							} else if ( 2 === response.dkim.code ) {
								jQuery('.cr-dkim-verify-status-ind').css('background', '#FFDC00');
								jQuery('.cr-dkim-verify-status-ind').text( cr_settings_object.dns_pending );
								jQuery('#ivole_email_from_status').text( 'DKIM verification is pending. Publish DNS records from the table below to your domain’s DNS provider. Detection of these records may take up to 72 hours.' );
								jQuery('#ivole_email_from_status').show();
							} else {
								jQuery('#ivole_email_from_status').text( 'DKIM is disabled. It is recommended to enable DKIM to improve deliverability of emails.' );
								jQuery('#ivole_email_from_status').show();
								jQuery('.cr-dkim-enable-button').show();
							}
							crDNSRecordsTableAdd( response.dkim.tokens, false );
						}
					} else {
						jQuery('.cr-email-verify-status-ind').css('background', '#FA8072');
						jQuery('.cr-email-verify-status-ind').text( 'Unverified' );
						jQuery('#ivole_email_from_verify_button').show();
						jQuery('#ivole_email_from_status').text( 'This email address is unverified. You must verify it to send emails.' );
					}
				} else {
					jQuery('#ivole_email_from').val( '' );
					jQuery('#ivole_email_from_status').html( cr_settings_object.info_from );
					jQuery('#ivole_email_from_name_status').html( cr_settings_object.info_from_name );
					jQuery('#ivole_email_footer_status').html( cr_settings_object.info_footer );
					jQuery('#ivole_form_rating_bar_status').html( cr_settings_object.info_rating_bar );
					jQuery('#ivole_form_geolocation_status').html( cr_settings_object.info_geolocation );
				}
				// integration with qTranslate-X - add translation for elements that are loaded with a delay
				if (typeof qTranslateConfig !== 'undefined' && typeof qTranslateConfig.qtx !== 'undefined') {
					qTranslateConfig.qtx.addContentHook( document.getElementById( 'ivole_email_from_name' ), null, null );
					qTranslateConfig.qtx.addContentHook( document.getElementById( 'ivole_email_footer' ), null, null );
				}
			});
		}

		jQuery('#ivole_email_from_verify_button').click(function() {
			let data = {
				'action': 'cr_verify_email_ajax',
				'email': jQuery('#ivole_email_from').val()
			};
			jQuery('#ivole_email_from_verify_button').prop('disabled', true);
			jQuery('#ivole_email_from_status').text( 'Sending a verification email...' );
			jQuery.post(ajaxurl, data, function(response) {
				if ( 1 === response.verification ) {
					jQuery('#ivole_email_from_status').text( 'A verification email from Amazon Web Services has been sent to \'' + response.email + '\'. Please open the email and click on the verification URL to confirm that you are the owner of this email address. After verification, reload this page to see an updated status of the email verification.' );
					jQuery('#ivole_email_from_verify_button').css('visibility', 'hidden');
				} else if ( 2 === response.verification ) {
					jQuery('#ivole_email_from_status').text( 'Verification error: ' + response.message + '.' );
					jQuery('#ivole_email_from_verify_button').prop('disabled', false);
				} else if ( 3 === response.verification ) {
					jQuery('#ivole_email_from_status').text( 'Verification error: ' + response.message + '. Please refresh the page to see the updated verification status.' );
					jQuery('#ivole_email_from_verify_button').prop('disabled', false);
				} else if ( 99 === response.verification ) {
					jQuery('#ivole_email_from_status').text( 'Verification error: please enter a valid email address.' );
					jQuery('#ivole_email_from_verify_button').prop('disabled', false);
				} else {
					jQuery('#ivole_email_from_status').text( 'Verification error.' );
					jQuery('#ivole_email_from_verify_button').prop('disabled', false);
				}
			});
		});

		jQuery('.cr-dkim-enable-button').on( 'click', function(){
			jQuery(this).prop('disabled', true);
			jQuery('#ivole_email_from_status').text( 'Requesting DNS records to enable DKIM authentication...' );
			jQuery('#ivole_email_from_status').show();
			let data = {
				'action': 'cr_verify_dkim_ajax',
				'email': jQuery('#ivole_email_from').val()
			};
			jQuery.post(ajaxurl, data, function(response) {
				if ( 1 === response.verification ) {
					crDNSRecordsTableAdd( response.tokens, 0 );
					jQuery('#ivole_email_from_status').text( 'DNS records for DKIM authentication have been generated. Publish DNS records from the table below to your domain’s DNS provider. Detection of these records may take up to 72 hours. Reload this page to see an updated status of the DKIM authentication.' );
					jQuery('.cr-dkim-enable-button').css('visibility', 'hidden');
				} else {
					jQuery('#ivole_email_from_status').text( 'An error occured while requesting DNS records for DKIM authentication.' );
					jQuery('.cr-dkim-enable-button').prop('disabled', false);
				}
			});
		} );

		jQuery('.cr-help-tip' ).tipTip( {
			attribute: 'data-tip',
			fadeIn: 50,
			fadeOut: 50,
			delay: 200,
			keepAlive: false,
		} );

		function crDNSRecordsTableAdd( tokens, actv ) {
			tokens.forEach ( token => {
				let newRow = jQuery('.cr-dns-template-row').clone();
				newRow.removeClass('cr-dns-template-row');
				newRow.find('.cr-dns-cell-name .cr-dns-cell-text').text(token.name);
				newRow.find('.cr-dns-cell-value .cr-dns-cell-text').text(token.value)
				newRow.appendTo('.cr-dns-table tbody');
			} );
			if ( 0 < tokens.length ) {
				jQuery('.cr-dns-records-acc').show();
				jQuery('.cr-dns-records-acc').accordion({
					collapsible: true,
					active: actv
				});
				jQuery('.cr-dns-records-acc .cr-dns-table td .dashicons').tipTip( {
					activation: 'click',
					content: cr_settings_object.dns_copied,
					defaultPosition: 'top',
					delay: 100
				} );
				jQuery('.cr-dns-records-acc .cr-dns-table td .dashicons').on( 'click', function(e) {
					let value = jQuery(this).closest('.cr-dns-cell-cont').find('.cr-dns-cell-text').text();
					navigator.clipboard.writeText(value);
				} );
			}
		}

	} );
} () );
