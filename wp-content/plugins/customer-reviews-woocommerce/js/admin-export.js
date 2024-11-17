var crExporter;

jQuery(document).ready(function() {
    crExporter = {
        progress_id: null,

        init: function() {
            jQuery('#cr-export-button').on('click', function(event) {
                event.preventDefault();
                crExporter.begin(null);
                jQuery('#cr-export-cancel').prop('disabled', true);

                jQuery.post(
                    ajaxurl,
                    {
                        'action': 'cr_start_reviews_export',
												'nonce': jQuery(this).data('nonce')
                    }
                ).done(function(response) {

                    try {
                        response = JSON.parse( response );
                    } catch ( e ) {}

                    jQuery('#cr-export-cancel').prop('disabled', false);

                    if (!response.success) {
                        crExporter.set_status('error', response.data.message);
                        crExporter.failed(response.data.message);
                        return;
                    }

                    crExporter.progress_id = response.data.progress_id;

                    if (window.localStorage) {
                        localStorage.setItem('cr_export_data', JSON.stringify(response.data));
                    }

                    crExporter.begin(response.data);

                }).fail(function(response) {
                    crExporter.set_status('error', response.statusText);
                });
            });
            jQuery('#cr-export-cancel').on('click', function(event) {
                event.preventDefault();
                crExporter.cancel_export();
            });
						jQuery('#cr-export-download').on('click', function(event) {
							jQuery('#cr-export-progress-bar').val(0);
							jQuery("#cr-export-results").delay(3000).hide(0);
							jQuery("#cr-export-reviews").delay(3000).show(0);
						} );
						jQuery('.cr-export-reviews-download').on('click', function(event) {
							jQuery(this).delay(5000).hide();
						} );

            if (window.localStorage) {
                var data = localStorage.getItem('cr_export_data');
                if (data) {
                    try {
                        data = JSON.parse(data);
                    } catch (error) {}

                    if (typeof data === 'object') {
                        crExporter.progress_id = data.progress_id;
                        crExporter.check_progress();
                        crExporter.begin(data);
                    }
                }
            }
        },

        set_status: function(status, text) {
            var $status = jQuery('#cr-export-result-status');
            $status.html(text);
            $status.removeClass('status-error status-notice');

            switch (status) {
                case 'none':
                    $status.html('');
                    $status.hide();
                    jQuery("#cr-export-results").hide();
                    return;
                case 'error':
                    $status.addClass('status-error');
                    break;
                case 'notice':
                    $status.addClass('status-notice');
                    break;
            }

            jQuery("#cr-export-results").show();
            $status.show();
        },

        begin: function(data) {
            jQuery('#cr-export-reviews').hide();
            jQuery('#cr-export-progress').show();


            if(data){
                jQuery('#cr-export-text').html(CrExportStrings.exporting.replace('%s', '0').replace('%s', data.num_rows));

                crExporter.__progress_check_interval = setInterval(function() {
                    crExporter.check_progress();
                }, 1000);
            }
        },

        completed: function(data) {
            clearInterval(crExporter.__progress_check_interval);

            if (window.localStorage) {
                localStorage.removeItem('cr_export_data');
            }

            var start_date = new Date(data.started * 1000);
            var end_date   = new Date(data.finished * 1000);
            var delta      = end_date.getSeconds() - start_date.getSeconds();

            jQuery('#cr-export-result-started').html(CrExportStrings.result_started.replace('%s', start_date.toLocaleDateString() + ' ' + start_date.toLocaleTimeString()));
            jQuery('#cr-export-result-finished').html(CrExportStrings.result_finished.replace('%s', end_date.toLocaleDateString() + ' ' + end_date.toLocaleTimeString()));
            if(data.status !== "cancelled") jQuery('#cr-export-result-exported').html(CrExportStrings.result_imported.replace('%d', data.reviews.exported));

            setTimeout(function() {
                jQuery('#cr-export-progress').hide();
                jQuery('#cr-export-results').show();
            }, 1000);

            if(data.status === "complete") {
                jQuery("#cr-export-download").show();
            }
        },

        failed: function(msg) {
            clearInterval(crExporter.__progress_check_interval);

            if (window.localStorage) {
                localStorage.removeItem('cr_export_data');
            }

            if(msg != "") crExporter.set_status('error', msg);
            else crExporter.set_status('error', CrExportStrings.export_failed);

            jQuery('#cr-export-progress').hide();
        },

        cancelled: function(data) {
            jQuery('#cr-export-result-status').html(CrExportStrings.export_cancelled);
            crExporter.completed(data);
        },

        check_progress: function() {
            jQuery.post(
                ajaxurl,
                {
                    'action': 'cr_check_export_progress',
                    'progress_id': crExporter.progress_id,
                    'nonce': jQuery('#cr-export-progress-bar').data( 'nonce' )
                }
            ).success( function( response ) {
              if ( response ) {
                if (response.status) {
                    if (response.status === 'exporting') {
                        var processed = response.reviews.exported;
                        var percentage = Math.floor((processed / response.reviews.total) * 100);
                        jQuery('#cr-export-text').html(CrExportStrings.exporting.replace('%s', processed).replace('%s', response.reviews.total));
                        jQuery('#cr-export-progress-bar').val(percentage);
                    } else if(response.status === 'failed') {
                        if(typeof response.msg !== "undefined") crExporter.failed(response.msg);
                        else crExporter.failed("");
                    } else if (response.status === 'complete') {
                        var processed = response.reviews.exported;
                        var percentage = 100;
                        if ( 0 < response.reviews.total ) {
                          percentage = Math.floor((processed / response.reviews.total) * 100);
                        }
                        jQuery('#cr-export-progress-bar').val(percentage);
                        crExporter.completed(response);
                    } else if (response.status === 'cancelled') {
                        crExporter.cancelled(response);
                    }
                } else if (response === false) {
                    crExporter.failed("");
                }
              }
            } );
        },

        cancel_export: function() {
            var $cancel_button = jQuery('#cr-export-cancel');
            $cancel_button.prop('disabled', true);
            $cancel_button.html(CrExportStrings.cancelling);
            jQuery.post(
                ajaxurl,
                {
                    'action': 'cr_cancel_reviews_export',
                    'progress_id': crExporter.progress_id,
                    'nonce': $cancel_button.data('nonce')
                }
            ).success( function( response ) {
              if ( response ) {
                crExporter.cancelled( response );
              } else {
                $cancel_button.prop( 'disabled', false );
                $cancel_button.html( CrExportStrings.cancel );
              }
            } )
        }
    };

    crExporter.init();
});
