(function(){
	jQuery(function($) {
		jQuery(".cr-comment-a").click(function(t) {
			t.preventDefault();
			const oo = jQuery(".pswp");
			if ( 0 < oo.length ) {
				const o = oo[0];
				var pics = jQuery(this).parent().parent().find(".cr-comment-a img");
				var this_pic = jQuery(this).find("img");
				var inx = 0;
				if(pics.length > 0 && this_pic.length > 0) {
					var a = [];
					for(i=0; i<pics.length; i++) {
						a.push({
							src: pics[i].src,
							w: pics[i].naturalWidth,
							h: pics[i].naturalHeight,
							title: pics[i].alt
						});
						if(this_pic[0].src == pics[i].src) {
							inx = i;
						}
					}
					var r = {
						index: inx
					};
					new PhotoSwipe(o,PhotoSwipeUI_Default,a,r).init();
				}
			}
		});

		//show lightbox when click on images attached to reviews
		jQuery(".cr-comment-images, .cr-comment-videos").on("click", ".cr-video-a, .cr-comment-videoicon", function (t) {
			if( ! jQuery(this).closest(".cr-comment-videos").hasClass( "cr-comment-videos-modal" ) ) {
				let tt = jQuery(this).closest("[class*='cr-comment-video-']");
				jQuery(this).closest(".cr-comment-videos").addClass( "cr-comment-videos-modal" );
				tt.addClass( "cr-comment-video-modal" );
				tt.find( "video" ).prop( "controls", true );
				tt.find( ".cr-comment-videoicon" ).hide();
				tt.find( "video" ).get(0).play();
			}
			return false;
		});

		//close video lightbox
		jQuery(".cr-comment-videos").click(function(t) {
			if( jQuery(this).hasClass( "cr-comment-videos-modal" ) ) {
				jQuery(this).removeClass( "cr-comment-videos-modal" );
				jQuery(this).find("[class*='cr-comment-video-']").each(function(index, element){
					if( jQuery(element).hasClass( "cr-comment-video-modal" ) ) {
						jQuery(element).removeClass( "cr-comment-video-modal" );
						jQuery(element).find( "video").get(0).pause();
						jQuery(element).find( "video" ).prop( "controls", false );
						jQuery(element).find( ".cr-comment-videoicon" ).show();
						jQuery(element).removeAttr("style");
					}
				});
				return false;
			}
		});

		//close video lightbox
		jQuery(".cr-comment-images, .cr-comment-videos").on("click", ".cr-comment-video", function (t) {
			if( jQuery(this).hasClass( "cr-comment-video-modal" ) ) {
				jQuery(this).removeClass( "cr-comment-video-modal" );
				jQuery(this).closest(".cr-comment-videos").removeClass( "cr-comment-videos-modal" );
				jQuery(this).find( "video").get(0).pause();
				jQuery(this).find( "video" ).prop( "controls", false );
				jQuery(this).find( ".cr-comment-videoicon" ).show();
				jQuery(this).removeAttr("style");
				return false;
			}
		});

		//show a div with a checkbox to send a copy of reply to CR
		jQuery("#the-comment-list").on("click", ".comment-inline", function (e) {
			var $el = $( this ), action = 'replyto';
			if ( 'undefined' !== typeof $el.data( 'action' ) ) {
				action = $el.data( 'action' );
			}
			if ( action == 'replyto' ) {
				if ( $el.hasClass( 'ivole-comment-inline' ) || $el.hasClass( 'ivole-reply-inline' ) ) {
					jQuery('#ivole_replyto_cr_checkbox').val('no');
					jQuery('#ivole_replyto_cr_checkbox').prop( 'checked', false );
					jQuery( '#ivole_replytocr' ).show();
				} else {
					jQuery( '#ivole_replytocr' ).hide();
				}
			}
			return false;
		});

		//feature or unfeature a review
		jQuery("#the-comment-list").on("click", ".cr-feature-review-link", function (e) {
			e.preventDefault();
			var review_id = jQuery(this).attr("data-reviewid");
			var cr_data = {
				"review_id": review_id,
				"cr_nonce": jQuery(this).attr("data-nonce"),
				"action": "cr-feature-review"
			};
			jQuery("#the-comment-list #comment-" + review_id + " .cr-feature-review-link").addClass("cr-feature-review-link-disabled");
			jQuery.post(cr_ajax_object.ajax_url, cr_data, function(response) {
				jQuery("#the-comment-list #comment-" + response.review_id + " .cr-feature-review-link").removeClass("cr-feature-review-link-disabled");
				if(response.result){
					if( response.display_badge ) {
						jQuery("#the-comment-list #comment-" + response.review_id + " .cr-featured-badge-admin").removeClass("cr-featured-badge-admin-hidden");
					} else {
						jQuery("#the-comment-list #comment-" + response.review_id + " .cr-featured-badge-admin").addClass("cr-featured-badge-admin-hidden");
					}
					jQuery("#the-comment-list #comment-" + response.review_id + " .cr-feature-review-link").text(response.label);
				}
			}, "json");
		});

		//
		jQuery("#ivole_replyto_cr_checkbox").change(function() {
			if(jQuery(this).prop('checked')) {
				jQuery(this).val('yes');
			} else {
				jQuery(this).val('no');
			}
		});

		jQuery(".cr-upload-local-images-btn").on("click", function(e){
			e.preventDefault();
			var upload_files = jQuery("#review_image");
			var count_files = upload_files[0].files.length;
			if(0 < count_files) {
				var i = 0;
				var form_data = new FormData();
				form_data.append("action", "cr_upload_local_images_admin");
				form_data.append("post_id", jQuery(this).attr("data-postid"));
				form_data.append("comment_id", jQuery(this).attr("data-commentid"));
				form_data.append("cr_nonce", jQuery(this).attr("data-nonce"));
				form_data.append("count_files", jQuery(".cr-comment-images").find(".cr-comment-image").length);
				for( i = 0; i < count_files; i++ ) {
					form_data.append("cr_files_" + i, upload_files[0].files[i]);
				}
				jQuery.ajax({
					url: cr_ajax_object.ajax_url,
					data: form_data,
					processData: false,
					contentType: false,
					dataType: "json",
					type: "POST",
					beforeSend: function() {
						jQuery(".cr-upload-local-images-status").removeClass("cr-upload-local-images-status-ok");
						jQuery(".cr-upload-local-images-status").removeClass("cr-upload-local-images-status-warning");
						jQuery(".cr-upload-local-images-status").removeClass("cr-upload-local-images-status-error");
						jQuery(".cr-upload-local-images-status").text(cr_ajax_object.cr_uploading);
						jQuery(".cr-upload-local-images-btn").addClass("disabled cr-upload-local-images-btn-disable");
						jQuery("#review_image").addClass("disabled cr-upload-local-images-btn-disable");
					},
					xhr: function() {
						var myXhr = jQuery.ajaxSettings.xhr();
						if ( myXhr.upload ) {
							myXhr.upload.addEventListener( 'progress', function(e) {
								if ( e.lengthComputable ) {
									var perc = ( e.loaded / e.total ) * 100;
									perc = perc.toFixed(0);
									jQuery(".cr-upload-local-images-status").text(cr_ajax_object.cr_uploading + " (" + perc + "%)");
								}
							}, false );
						}
						return myXhr;
					},
					success: function(response) {
						// update status message color
						if( 200 === response["code"] ) {
							jQuery(".cr-upload-local-images-status").addClass("cr-upload-local-images-status-ok");
						} else if( 201 === response["code"] ) {
							jQuery(".cr-upload-local-images-status").addClass("cr-upload-local-images-status-warning");
						} else {
							jQuery(".cr-upload-local-images-status").addClass("cr-upload-local-images-status-error");
						}
						// update status message text
						jQuery(".cr-upload-local-images-status").text("");
						jQuery.each(response["message"], function(index, message) {
							if( 0 < index ) {
								jQuery(".cr-upload-local-images-status").append("<br>");
							}
							jQuery(".cr-upload-local-images-status").append(message);
						});
						// reset the file upload input
						jQuery("#review_image").val("");
						jQuery(".cr-upload-local-images-btn").removeClass("disabled cr-upload-local-images-btn-disable");
						jQuery("#review_image").removeClass("disabled cr-upload-local-images-btn-disable");
						// display uploaded images (if any)
						if( "files" in response && response["files"].length > 0 ) {
							jQuery.each(response["files"], function(index, file) {
								let file_html = '';
								if ( 'video' === file["type"] ) {
									file_html = '<div class="cr-comment-video cr-comment-video-' + file["id"] + '">';
								} else {
									file_html = '<div class="cr-comment-image cr-comment-image-' + file["id"] + '">';
								}
								file_html += '<div class="cr-comment-image-detach"><div class="cr-comment-image-detach-controls">';
								file_html += '<p>' + cr_ajax_object.detach + '</p>';
								file_html += '<p><span class="cr-comment-image-detach-no">' + cr_ajax_object.detach_no + '</span>';
								file_html += '<span class="cr-comment-image-detach-yes" data-attachment="' + file["id"] + '" data-nonce="' + file["nonce"] + '">' + cr_ajax_object.detach_yes + '</span></p>';
								file_html += '<span class="cr-comment-image-detach-spinner"></span></div>';
								if ( 'video' === file["type"] ) {
									file_html += '<div class="cr-video-cont">';
									file_html += '<video preload="metadata" class="cr-video-a" src="' + file["url"] + '"></video>';
									file_html += '<img class="cr-comment-videoicon" src="' + cr_ajax_object.videoicon + '">';
									file_html += '<button class="cr-comment-video-close"><span class="dashicons dashicons-no"></span></button>';
									file_html += '</div></div>';
								} else {
									file_html += '<img src="' + file["url"] + '" alt="' + file["author"] + '"></div>';
								}
								file_html += '<button class="cr-comment-image-close"><span class="dashicons dashicons-no"></span></button></div>';
								jQuery("#cr_reviews_media_meta_box .cr-comment-images .cr-comment-images-clear").before(file_html);
							});
						}
					}
				});
			}
		});

		// the 1st step to detach a picture
		jQuery(".cr-comment-images").on("click", ".cr-comment-image .cr-comment-image-close", function(e){
			e.preventDefault();
			jQuery(this).closest(".cr-comment-image").find("img").css("visibility","hidden");
			jQuery(this).closest(".cr-comment-image").find(".cr-comment-image-detach").addClass("cr-comment-image-detach-active");
			jQuery(this).closest(".cr-comment-image").find(".cr-comment-image-close").hide();
			jQuery(this).closest(".cr-comment-image").find(".cr-comment-image-detach-controls").show();
			var controlsHeight = jQuery(this).closest(".cr-comment-image").find(".cr-comment-image-detach-controls").height();
			if( jQuery(this).closest(".cr-comment-image").find(".cr-comment-image-detach").height() < controlsHeight ) {
				jQuery(this).closest(".cr-comment-image").find(".cr-comment-image-detach").height( controlsHeight );
			}
		});

		// the 1st step to detach a video
		jQuery(".cr-comment-images").on("click", ".cr-comment-video .cr-comment-image-close", function(e){
			e.preventDefault();
			jQuery(this).closest(".cr-comment-video").find("video").css("visibility","hidden");
			jQuery(this).closest(".cr-comment-video").find("img").css("visibility","hidden");
			jQuery(this).closest(".cr-comment-video").find(".cr-comment-image-detach").addClass("cr-comment-image-detach-active");
			jQuery(this).closest(".cr-comment-video").find(".cr-comment-image-close").hide();
			jQuery(this).closest(".cr-comment-video").find(".cr-comment-image-detach-controls").show();
			var controlsHeight = jQuery(this).closest(".cr-comment-video").find(".cr-comment-image-detach-controls").height();
			if( jQuery(this).closest(".cr-comment-video").find(".cr-comment-image-detach").height() < controlsHeight ) {
				jQuery(this).closest(".cr-comment-video").find(".cr-comment-image-detach").height( controlsHeight );
			}
		});

		// cancel the 1st step to detach a picture
		jQuery(".cr-comment-images").on("click", ".cr-comment-image .cr-comment-image-detach .cr-comment-image-detach-no", function(e){
			e.preventDefault();
			jQuery(this).closest(".cr-comment-image").find(".cr-comment-image-detach").removeClass("cr-comment-image-detach-active");
			jQuery(this).closest(".cr-comment-image").find(".cr-comment-image-detach-controls").hide();
			jQuery(this).closest(".cr-comment-image").find("img").css("visibility","visible");
			jQuery(this).closest(".cr-comment-image").find(".cr-comment-image-detach").css("height","auto");
			jQuery(this).closest(".cr-comment-image").find(".cr-comment-image-close").show();
		});

		// cancel the 1st step to detach a video
		jQuery(".cr-comment-images").on("click", ".cr-comment-video .cr-comment-image-detach .cr-comment-image-detach-no", function(e){
			e.preventDefault();
			jQuery(this).closest(".cr-comment-video").find(".cr-comment-image-detach").removeClass("cr-comment-image-detach-active");
			jQuery(this).closest(".cr-comment-video").find(".cr-comment-image-detach-controls").hide();
			jQuery(this).closest(".cr-comment-video").find("video").css("visibility","visible");
			jQuery(this).closest(".cr-comment-video").find("img").css("visibility","visible");
			jQuery(this).closest(".cr-comment-video").find(".cr-comment-image-detach").css("height","auto");
			jQuery(this).closest(".cr-comment-video").find(".cr-comment-image-close").show();
		});

		// confirm the 1st step to detach a picture
		jQuery(".cr-comment-images").on("click", ".cr-comment-image .cr-comment-image-detach .cr-comment-image-detach-yes", function(e){
			e.preventDefault();
			var cr_data = {
				"action": "cr_detach_images_admin",
				"cr_nonce": jQuery(this).attr("data-nonce"),
				"comment_id": jQuery(".cr-upload-local-images-btn").attr("data-commentid"),
				"attachment_id": jQuery(this).attr("data-attachment"),
				"media_type": 1
			};
			jQuery(this).closest(".cr-comment-image").find(".cr-comment-image-detach-controls p").hide();
			jQuery(this).closest(".cr-comment-image").find(".cr-comment-image-detach-spinner").css("display","block");
			jQuery.post(cr_ajax_object.ajax_url, cr_data, function(response) {
				if( response["code"] ) {
					jQuery(".cr-comment-images .cr-comment-image-" + response["attachment"] ).remove();
				} else {
					jQuery(".cr-comment-image-" + response["attachment"]).find(".cr-comment-image-detach-spinner").css("display","none");
					jQuery(".cr-comment-image-" + response["attachment"]).find(".cr-comment-image-detach-controls p").show();
					jQuery(".cr-comment-image-" + response["attachment"]).find(".cr-comment-image-detach").removeClass("cr-comment-image-detach-active");
					jQuery(".cr-comment-image-" + response["attachment"]).find(".cr-comment-image-detach-controls").hide();
					jQuery(".cr-comment-image-" + response["attachment"]).find("img").css("visibility","visible");
					jQuery(".cr-comment-image-" + response["attachment"]).find(".cr-comment-image-detach").css("height","auto");
					jQuery(".cr-comment-image-" + response["attachment"]).find(".cr-comment-image-close").show();
				}
			});
		});

		// confirm the 1st step to detach a video
		jQuery(".cr-comment-images").on("click", ".cr-comment-video .cr-comment-image-detach .cr-comment-image-detach-yes", function(e){
			e.preventDefault();
			var cr_data = {
				"action": "cr_detach_images_admin",
				"cr_nonce": jQuery(this).attr("data-nonce"),
				"comment_id": jQuery(".cr-upload-local-images-btn").attr("data-commentid"),
				"attachment_id": jQuery(this).attr("data-attachment"),
				"media_type": 2
			};
			jQuery(this).closest(".cr-comment-video").find(".cr-comment-image-detach-controls p").hide();
			jQuery(this).closest(".cr-comment-video").find(".cr-comment-image-detach-spinner").css("display","block");
			jQuery.post(cr_ajax_object.ajax_url, cr_data, function(response) {
				if( response["code"] ) {
					jQuery(".cr-comment-images .cr-comment-video-" + response["attachment"] ).remove();
				} else {
					jQuery(".cr-comment-video-" + response["attachment"]).find(".cr-comment-image-detach-spinner").css("display","none");
					jQuery(".cr-comment-video-" + response["attachment"]).find(".cr-comment-image-detach-controls p").show();
					jQuery(".cr-comment-video-" + response["attachment"]).find(".cr-comment-image-detach").removeClass("cr-comment-image-detach-active");
					jQuery(".cr-comment-video-" + response["attachment"]).find(".cr-comment-image-detach-controls").hide();
					jQuery(".cr-comment-video-" + response["attachment"]).find("video").css("visibility","visible");
					jQuery(".cr-comment-video-" + response["attachment"]).find("img").css("visibility","visible");
					jQuery(".cr-comment-video-" + response["attachment"]).find(".cr-comment-image-detach").css("height","auto");
					jQuery(".cr-comment-video-" + response["attachment"]).find(".cr-comment-image-close").show();
				}
			});
		});

		jQuery( document ).ajaxSuccess(function( event, xhr, settings, data ) {
			if ( "dim-comment" == settings.action ) {
				let diff = jQuery('#' + settings.element).is('.' + settings.dimClass) ? 1 : -1;
				cr_updatePending( diff );
			} else if ( "delete-comment" == settings.action ) {
				let parsedResponse = wpAjax.parseAjaxResponse( xhr.responseXML, settings.response, settings.element ),
					targetParent = jQuery( settings.target ).parent(),
					commentRow = jQuery('#' + settings.element),
					response = true === parsedResponse ? {} : parsedResponse.responses[0],
					commentStatus = true === parsedResponse ? '' : response.supplemental.status,
					approved = commentRow.hasClass( 'approved' ) && ! commentRow.hasClass( 'unapproved' ),
					unapproved = commentRow.hasClass( 'unapproved' ),
					spammed = commentRow.hasClass( 'spam' ),
					trashed = commentRow.hasClass( 'trash' ),
					undoing = false,
					spamDiff, trashDiff, pendingDiff, approvedDiff;

				if ( targetParent.is( 'span.undo' ) ) {
					// The comment was spammed.
					if ( targetParent.hasClass( 'unspam' ) ) {
						spamDiff = -1;

						if ( 'trash' === commentStatus ) {
							trashDiff = 1;
						} else if ( '1' === commentStatus ) {
							approvedDiff = 1;
						} else if ( '0' === commentStatus ) {
							pendingDiff = 1;
						}

					// The comment was trashed.
					} else if ( targetParent.hasClass( 'untrash' ) ) {
						trashDiff = -1;

						if ( 'spam' === commentStatus ) {
							spamDiff = 1;
						} else if ( '1' === commentStatus ) {
							approvedDiff = 1;
						} else if ( '0' === commentStatus ) {
							pendingDiff = 1;
						}
					}

					undoing = true;

				// User clicked "Spam".
				} else if ( targetParent.is( 'span.spam' ) ) {
					// The comment is currently approved.
					if ( approved ) {
						approvedDiff = -1;
					// The comment is currently pending.
					} else if ( unapproved ) {
						pendingDiff = -1;
					// The comment was in the Trash.
					} else if ( trashed ) {
						trashDiff = -1;
					}
					// You can't spam an item on the Spam screen.
					spamDiff = 1;

				// User clicked "Unspam".
				} else if ( targetParent.is( 'span.unspam' ) ) {
					if ( approved ) {
						pendingDiff = 1;
					} else if ( unapproved ) {
						approvedDiff = 1;
					} else if ( trashed ) {
						// The comment was previously approved.
						if ( targetParent.hasClass( 'approve' ) ) {
							approvedDiff = 1;
						// The comment was previously pending.
						} else if ( targetParent.hasClass( 'unapprove' ) ) {
							pendingDiff = 1;
						}
					} else if ( spammed ) {
						if ( targetParent.hasClass( 'approve' ) ) {
							approvedDiff = 1;

						} else if ( targetParent.hasClass( 'unapprove' ) ) {
							pendingDiff = 1;
						}
					}
					// You can unspam an item on the Spam screen.
					spamDiff = -1;

				// User clicked "Trash".
				} else if ( targetParent.is( 'span.trash' ) ) {
					if ( approved ) {
						approvedDiff = -1;
					} else if ( unapproved ) {
						pendingDiff = -1;
					// The comment was in the spam queue.
					} else if ( spammed ) {
						spamDiff = -1;
					}
					// You can't trash an item on the Trash screen.
					trashDiff = 1;

				// User clicked "Restore".
				} else if ( targetParent.is( 'span.untrash' ) ) {
					if ( approved ) {
						pendingDiff = 1;
					} else if ( unapproved ) {
						approvedDiff = 1;
					} else if ( trashed ) {
						if ( targetParent.hasClass( 'approve' ) ) {
							approvedDiff = 1;
						} else if ( targetParent.hasClass( 'unapprove' ) ) {
							pendingDiff = 1;
						}
					}
					// You can't go from Trash to Spam.
					// You can untrash on the Trash screen.
					trashDiff = -1;

				// User clicked "Approve".
				} else if ( targetParent.is( 'span.approve:not(.unspam):not(.untrash)' ) ) {
					approvedDiff = 1;
					pendingDiff = -1;

				// User clicked "Unapprove".
				} else if ( targetParent.is( 'span.unapprove:not(.unspam):not(.untrash)' ) ) {
					approvedDiff = -1;
					pendingDiff = 1;

				// User clicked "Delete Permanently".
				} else if ( targetParent.is( 'span.delete' ) ) {
					if ( spammed ) {
						spamDiff = -1;
					} else if ( trashed ) {
						trashDiff = -1;
					}
				}

				if ( pendingDiff ) {
					cr_updatePending( pendingDiff );
				}
			// Reviews - Approve and Reply
		} else if ( typeof settings.data === 'string' && settings.data.indexOf( "ivole-replyto-comment" ) !== -1 && settings.data.indexOf( "approve_parent=1" ) !== -1 ) {
				cr_updatePending( -1 );
			// Q&A - Approve and Reply
		} else if ( typeof settings.data === 'string' &&  settings.data.indexOf( "cr-replyto-qna" ) !== -1 && settings.data.indexOf( "approve_parent=1" ) !== -1 ) {
				cr_updatePending( -1 );
			}
		});

		// Download of media files to the local Media Library
		jQuery(".cr-notice-auto-download").on("click", ".cr-button-auto-init", function(e) {
			e.preventDefault();
			cr_autoDownload(this, false);
		});

		// Cancel download of media files to the local Media Library
		jQuery(".cr-notice-auto-download").on("click", ".cr-button-auto-cancel", function(e) {
			e.preventDefault();
			jQuery(this).text(cr_ajax_object.cr_cancelling);
			jQuery(this).prop("disabled", true);
			jQuery(this).addClass("cr-button-auto-cancelling");
		});

		// Download of media files to the local Media Library was completed, dismiss the notification
		jQuery(".cr-notice-auto-download").on("click", ".cr-button-auto-okay", function(e) {
			e.preventDefault();
			var cr_data = {
				"action": "cr_auto_download_okay",
				"cr_nonce": jQuery(this).attr("data-nonce")
			};
			jQuery(this).prop("disabled", true);
			jQuery.ajax({
				url: cr_ajax_object.ajax_url,
				data: cr_data,
				context: this,
				success: function(response) {
					jQuery(this).parent().hide();
				}
			});
		});

		// Initialize tags
		jQuery(".cr_tags").select2({
			tags: true,
			tokenSeparators: [',', ' ', ';'],
			width: '100%',
			ajax: {
				url: cr_ajax_object.ajax_url,
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return {
						action: 'cr_select_fetch_tags',
						query: params.term,
						page: params.page || 1
					}
				},
				processResults: function(data) {
					var options = [];
					if(data['return']) {
						jQuery.each( data['return'], function( index, term ) {
							options.push( { id: term[0], text: term[1]  } );
						});
					}
					return {
						results: options,
						pagination: {
							more: data['pagination']
						}
					};
				},
				cache: true
			},
			minimumInputLength: 1
		});

		jQuery("select").on("select2:select", function (evt) {
			var element = evt.params.data.element;
			var $element = $(element);

			$element.detach();
			jQuery(this).append($element);
			jQuery(this).trigger("change");
		});

		// Add a new tag
		jQuery(".cr-reviews-list-table").on("click", ".cr-new-tag, .cr-tag-a", function(e) {
			e.preventDefault();
			jQuery(this).parents("td.tags").find(".cr-tags-assigned-new").addClass("cr-tags-hidden");
			jQuery(this).parents("td.tags").find(".cr-tags-edit").removeClass("cr-tags-hidden");
		});

		// Update tags
		jQuery(".cr-reviews-list-table").on("click", ".cr-button-primary", function(e) {
			e.preventDefault();
			var cr_data = {
				"action": "cr_update_tags",
				"review_id": jQuery(this).data("reviewid"),
				"tags": JSON.stringify(jQuery(this).parents("td.tags").find("select.cr_tags").eq(0).select2("data")),
				"cr_nonce": jQuery(this).data("nonce")
			};
			jQuery(this).parents("td.tags").find(".cr-tags-edit").addClass("cr-update-in-progress");
			jQuery(this).parents("td.tags").find(".cr-button-primary span.dashicons").removeClass("dashicons-saved");
			jQuery(this).parents("td.tags").find(".cr-button-primary span.dashicons").addClass("dashicons-update");
			jQuery.ajax({
				url: cr_ajax_object.ajax_url,
				data: cr_data,
				context: this,
				method: "POST",
				dataType: "json",
				success: function(response) {
					jQuery(this).parents("td.tags").find(".cr-tags-edit").removeClass("cr-update-in-progress");
					jQuery(this).parents("td.tags").find(".cr-tags-edit").addClass("cr-tags-hidden");
					jQuery(this).parents("td.tags").find(".cr-button-primary span.dashicons").removeClass("dashicons-update");
					jQuery(this).parents("td.tags").find(".cr-button-primary span.dashicons").addClass("dashicons-saved");
					jQuery(this).parents("td.tags").find(".cr-tags-assigned").addClass("cr-tags-hidden");
					jQuery(this).parents("td.tags").find(".cr-tags-new").addClass("cr-tags-hidden");
					jQuery(this).parents("td.tags").find(".cr-tags-assigned").empty();
					if( response && 'object' === typeof response && response.hasOwnProperty("tags") && 0 < response.tags.length ) {
						let appendString = "";
						for(let i = 0; i < response.tags.length; i++ ) {
							appendString = "<a class=\"cr-tag-a\" href=\"\">" + response.tags[i] + "</a>";
							if( 0 < i ) {
								appendString = ", " + appendString;
							}
							jQuery(this).parents("td.tags").find(".cr-tags-assigned").append(appendString);
						}
						jQuery(this).parents("td.tags").find(".cr-tags-assigned").removeClass("cr-tags-hidden");
					} else {
						jQuery(this).parents("td.tags").find(".cr-tags-new").removeClass("cr-tags-hidden");
					}
					jQuery(this).parents("td.tags").find(".cr-tags-assigned-new").removeClass("cr-tags-hidden");
				}
			});
		});

		// Cancel updating tags
		jQuery(".cr-reviews-list-table").on("click", ".cr-button-cancel", function(e) {
			e.preventDefault();
			jQuery(this).parents("td.tags").find(".cr-tags-edit").addClass("cr-tags-hidden");
			jQuery(this).parents("td.tags").find(".cr-tags-assigned-new").removeClass("cr-tags-hidden");
		});

		// unverify a review - (1st step)
		jQuery("#the-comment-list").on("click", ".cr-del-verif-link", function (e) {
			e.preventDefault();
			let review_id = jQuery(this).attr("data-reviewid");
			jQuery("#the-comment-list #comment-" + review_id + " .row-actions").addClass("cr-del-verif-confirm");
			jQuery("#the-comment-list #comment-" + review_id + " .row-actions").append(
				"<span class='cr-del-confirm'>" +
				"<span>" + cr_ajax_object.cr_confirm_unverify + "</span>" +
				"<a class='cr-del-confirm-a cr-del-no' href='#'>" + cr_ajax_object.unverify_no + "</a>" +
				"<a class='cr-del-confirm-a cr-del-yes' href='#'>" + cr_ajax_object.unverify_yes + "</a>" +
				"</span>"
			);
		});

		// cancel unverification (2nd step)
		jQuery("#the-comment-list").on("click", ".cr-del-no", function (e) {
			e.preventDefault();
			jQuery(this).parents(".row-actions").removeClass("cr-del-verif-confirm");
			jQuery(this).parents(".row-actions").children(".cr-del-confirm").remove();
		});

		// confirm unverification (2nd step)
		jQuery("#the-comment-list").on("click", ".cr-del-yes", function (e) {
			e.preventDefault();
			let del_verif_link = jQuery(this).parents(".row-actions").find(".cr-del-verif-link");
			let review_id = del_verif_link.attr("data-reviewid");
			let cr_data = {
				"review_id": review_id,
				"cr_nonce": del_verif_link.attr("data-nonce"),
				"action": "cr-unverify-review"
			};
			jQuery("#the-comment-list #comment-" + review_id + " .cr-del-confirm-a").addClass("cr-feature-review-link-disabled");

			jQuery.post(cr_ajax_object.ajax_url, cr_data, function(response) {
				jQuery("#the-comment-list #comment-" + response.review_id + " .cr-del-confirm-a").removeClass("cr-feature-review-link-disabled");
				if ( response.result ) {
					jQuery("#the-comment-list #comment-" + response.review_id + " .row-actions").children(".del_verif").remove();
					jQuery("#the-comment-list #comment-" + response.review_id + " .comment").find(".ivole-verified-badge-icon").remove();
				}
				jQuery("#the-comment-list #comment-" + response.review_id + " .row-actions").children(".cr-del-confirm").remove();
				jQuery("#the-comment-list #comment-" + response.review_id + " .row-actions").removeClass("cr-del-verif-confirm");
			}, "json");
		});

	});

	function cr_updatePending( diff ) {
		let bubbleClass = jQuery(".cr_qna").length ? "pending-count-qna" : "pending-count-rev";
		jQuery( "span." + bubbleClass ).each(function() {
			var a = jQuery(this), n = cr_getCount(a) + diff;
			if ( n < 1 )
				n = 0;
			a.closest('.awaiting-mod')[ 0 === n ? 'addClass' : 'removeClass' ]('count-0');
			cr_updateCount( a, n );
		});
	}

	function cr_updateCount(el, n) {
		var n1 = '';
		if ( isNaN(n) ) {
			return;
		}
		n = n < 1 ? '0' : n.toString();
		if ( n.length > 3 ) {
			while ( n.length > 3 ) {
				n1 = thousandsSeparator + n.substr(n.length - 3) + n1;
				n = n.substr(0, n.length - 3);
			}
			n = n + n1;
		}
		el.html(n);
	}

	function cr_getCount(el) {
		var n = parseInt( el.html().replace(/[^0-9]+/g, ''), 10 );
		if ( isNaN(n) ) {
			return 0;
		}
		return n;
	}

	function cr_autoDownload(ref, repeatCall = false) {
		var cr_data = {
			"action": "cr_auto_download_media",
			"cr_nonce": jQuery(ref).attr("data-nonce")
		};
		if(!repeatCall) {
			jQuery(ref).text(cr_ajax_object.cr_cancel);
			jQuery(ref).removeClass("cr-button-auto-init");
			jQuery(ref).addClass("cr-button-auto-cancel");
			jQuery(ref).parent().children("p").text(cr_ajax_object.cr_downloading);
		}
		jQuery.ajax({
			url: cr_ajax_object.ajax_url,
			data: cr_data,
			context: ref,
			success: function(response) {
				// update status message color
				if( 200 === response["code"] ) {
					jQuery(this).parent().children("p").html(response["msg"]);
					if( jQuery(this).hasClass("cr-button-auto-cancelling") ) {
						jQuery(this).removeClass("cr-button-auto-cancelling");
						jQuery(this).removeClass("cr-button-auto-cancel");
						jQuery(this).addClass("cr-button-auto-init");
						jQuery(this).text(cr_ajax_object.cr_try_again);
						jQuery(this).prop("disabled", false);
						jQuery(this).parent().children("p").html(cr_ajax_object.cr_download_cancelled);
					} else {
						cr_autoDownload(this, true);
					}
				} else if( 406 === response["code"] ) {
					jQuery(this).text(cr_ajax_object.cr_ok);
					jQuery(this).parent().children("p").html(response["msg"]);
					jQuery(this).removeClass("cr-button-auto-cancel");
					jQuery(this).addClass("cr-button-auto-okay");
					if( jQuery(this).hasClass("cr-button-auto-cancelling") ) {
						jQuery(this).removeClass("cr-button-auto-cancelling");
						jQuery(this).prop("disabled", false);
					}
				} else {
					jQuery(this).text(cr_ajax_object.cr_try_again);
					jQuery(this).parent().children("p").html(response["msg"]);
					jQuery(this).removeClass("cr-button-auto-cancel");
					jQuery(this).addClass("cr-button-auto-init");
					if( jQuery(this).hasClass("cr-button-auto-cancelling") ) {
						jQuery(this).removeClass("cr-button-auto-cancelling");
						jQuery(this).prop("disabled", false);
					}
				}
			}
		});
	}
}());
