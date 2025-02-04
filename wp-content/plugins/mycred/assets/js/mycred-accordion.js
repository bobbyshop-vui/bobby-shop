/**
 * Accordion
 * @since 0.1
 * @since 2.3 Added open in new tab
 * @version 1.1
 */
jQuery(function($) {

	var active_box = false;
	if ( typeof myCRED !== 'undefined' ) {
		if ( myCRED.active != '-1' )
			active_box = parseInt( myCRED.active, 10 );
	}

	$( "#accordion" ).accordion({ collapsible: true, header: ".mycred-ui-accordion-header", heightStyle: "content", active: active_box });

	$( document ).on( 'click', '.buycred-cashcred-more-tab-btn', function(){
		var $url = $( this ).data( 'url' );
		window.open( $url, '_blank');
	});

	$(document).on( 'click', '.mycred-activate-network-sites', function(){
		
		var id = $(this).attr('data-id'),
		closest_button = $(this).closest('.mycred-activate-network-sites'),
		closest_loader = $(this).closest('.mycred').find('.mycred-loader'),
		_this = $(this).closest('.mycred').find('.dashicons'),
		installed = $(this).closest('.mycred').find('.info').empty();
		$.ajax({
            url: ajaxurl,
            data: {
                action: 'mycred_active_network_site',
                id: id,
            },
            type: 'POST',
            beforeSend: function() {
                $(closest_button).css("display", "none");
                $(closest_loader).css("display","inherit");
            },
            success:function(response) {
                $(closest_loader).hide();
                $(_this).removeClass('dashicons-minus');
                $(_this).addClass('dashicons-yes').css('color', '#008000');
                $(installed).append( 'Installed' );
            }
        })
	});

});


