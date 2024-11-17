( function() {
	jQuery( document ).ready( function() {
		// rating buttons
		jQuery( ".cr-form-item-question-row2" ).on( "click", ".cr-form-item-rating-radio", function( t ) {
			jQuery( this ).parent( ".cr-form-item-question-row2" ).find( ".cr-form-item-rating-radio .cr-form-item-inner" ).removeClass( "cr-form-active-radio" );
			jQuery( this ).parents( ".cr-form-item-question" ).removeClass( "cr-form-error" );
			jQuery( this ).find( ".cr-form-item-inner" ).addClass( "cr-form-active-radio" );
		} );
		// customer name
		jQuery( ".cr-form-customer-name-options" ).on( "click", ".cr-form-customer-name-option", function( t ) {
			jQuery( ".cr-form-customer-name-options .cr-form-customer-name-option" ).removeClass( "cr-form-active-name" );
			jQuery( this ).addClass( "cr-form-active-name" );
			let activeName = jQuery( this ).text();
			jQuery( ".cr-form-customer-name-preview-name" ).text( activeName );
		} );
		// comment box auto size
		jQuery( ".cr-form-item-comment textarea" ).each( function( index ) {
			this.style.height = "auto";
			this.style.height = (this.scrollHeight) + "px";
		} )
		jQuery( ".cr-form-item-comment" ).on( "input", "textarea", function( t ) {
			this.style.height = "auto";
			this.style.height = (this.scrollHeight) + "px";
			if( 0 < jQuery( this ).val().trim().length ) {
				jQuery( this ).parents( ".cr-form-item-comment" ).removeClass( "cr-form-error" );
			}
		} );
		// submit form
		jQuery( ".cr-form-submit" ).on( "click", function( t ) {
			let firstError = null;
			// validate ratings
			jQuery( ".cr-form-item-question-row2" ).each( function( index ) {
				let inners = jQuery( this ).find( ".cr-form-active-radio" );
				if( 1 > inners.length ) {
					jQuery( this ).parents( ".cr-form-item-question" ).addClass( "cr-form-error" );
					let currentOffset = jQuery( this ).parents( ".cr-form-item" ).offset().top;
					if( null === firstError ) {
						firstError = currentOffset;
					} else {
						if( currentOffset < firstError ) {
							firstError = currentOffset;
						}
					}
				}
			} );
			// validate comments
			jQuery( ".cr-form-item-comment.cr-form-required textarea" ).each( function( index ) {
				if( 1 > jQuery( this ).val().trim().length ) {
					jQuery( this ).parents( ".cr-form-item-comment" ).addClass( "cr-form-error" );
					let currentOffset = jQuery( this ).parents( ".cr-form-item" ).offset().top;
					if( null === firstError ) {
						firstError = currentOffset;
					} else {
						if( currentOffset < firstError ) {
							firstError = currentOffset;
						}
					}
				}
			} );
			//
			jQuery( ".cr-form-item-media" ).removeClass( "cr-form-error" );
			// submit reviews
			if( null !== firstError ) {
				// scroll to the first error if necessary
				jQuery( "html,body" ).animate( { scrollTop: firstError } );
			} else {
				// post reviews to the backend
				let crItems = [];
				jQuery( ".cr-form-item" ).each( function() {
					crItems.push( {
						"id": jQuery( this ).data( "itemid" ),
						"rating": jQuery( this ).find( ".cr-form-item-question-row2 .cr-form-active-radio" ).data( "rating" ),
						"comment": jQuery( this ).find( ".cr-form-item-comment textarea" ).val().trim(),
						"media": jQuery( this ).find( ".cr-upload-images-containers input" ).map( function() {
							let mItem = JSON.parse( jQuery( this ).val() );
							return mItem.id;
						} ).get()
					} )
				} );
				let crData = {
					"action": "cr_local_forms_submit",
					"formId": jQuery( ".cr-form" ).data( "formid" ),
					"displayName": jQuery( ".cr-form-customer-name-preview-name" ).text().trim(),
					"items": crItems
				};
				jQuery( ".cr-form-submit" ).addClass( "cr-form-loading" );
				jQuery.post( {
					url: crAjaxURL,
					data: crData,
					context: this,
					success: function( response ) {
						jQuery( ".cr-form-submit" ).removeClass( "cr-form-loading" );
						jQuery( ".cr-form" ).addClass( "cr-form-edit-submit" );
					},
					dataType: "json"
				} );
			}
		} );
		// edit reviews
		jQuery( ".cr-form-edit" ).on( "click", function( t ) {
			jQuery( ".cr-form-submit" ).removeClass( "cr-form-loading" );
			jQuery( ".cr-form" ).removeClass( "cr-form-edit-submit" );
		} );
		// upload media trigger
		jQuery( ".cr-form-item-media-none" ).on( "click", function( t ) {
			jQuery( this ).parent().find( "input.cr-form-item-media-file" ).trigger( "click" );
		} );
		// upload media trigger
		jQuery( ".cr-form-item-media-preview" ).on( "click", ".cr-form-item-media-add", function( t ) {
			jQuery( this ).parents( ".cr-form-item-media" ).find( "input.cr-form-item-media-file" ).trigger( "click" );
		} );
		// upload media
		jQuery( ".cr-form-item-media .cr-form-item-media-file" ).on( "change", function () {
			let allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'video/mp4', 'video/mpeg', 'video/ogg', 'video/webm', 'video/quicktime', 'video/x-msvideo'],
			uploadFiles = jQuery( this ),
			countFiles = uploadFiles[0].files.length,
			lastIndex = 1,
			mediaPreview = jQuery( this ).parent().find( ".cr-form-item-media-preview" ),
			countUploaded = mediaPreview.children( ".cr-upload-images-containers" ).length;
			jQuery( this ).closest( ".cr-form-item-media" ).removeClass( "cr-form-error" );
			if( jQuery(this).attr("data-lastindex") ) {
				lastIndex = parseInt( jQuery( this ).attr( "data-lastindex" ) );
			}
			if( countFiles + countUploaded > crMediaUploadLimit ) {
				uploadFiles.val("");
				return;
			}
			for(let i = 0; i < countFiles; i++) {
				if(!allowedTypes.includes(uploadFiles[0].files[i].type) ) {
					uploadFiles.val("");
					jQuery( this ).closest( ".cr-form-item-media" ).find( ".cr-form-item-media-error" ).text( crErrorFileType );
					jQuery( this ).closest( ".cr-form-item-media" ).addClass( "cr-form-error" );
					return;
				} else if( uploadFiles[0].files[i].size && uploadFiles[0].files[i].size > crMediaUploadMaxSize ) {
					jQuery( this ).closest( ".cr-form-item-media" ).find( ".cr-form-item-media-error" ).text( crErrorMaxFileSize );
					jQuery( this ).closest( ".cr-form-item-media" ).addClass( "cr-form-error" );
					uploadFiles.val("");
					return;
				} else {
					let container = jQuery( "<div/>", {class:"cr-upload-images-containers cr-upload-images-container-" + (lastIndex + i)} );
					let progressBar = jQuery( "<div/>", {class:"cr-upload-images-pbar"} );
					progressBar.append(
						jQuery( "<div/>", {class:"cr-upload-images-pbarin"} )
					);
					if( -1 === uploadFiles[0].files[i].type.indexOf( 'image' ) ) {
						container.append(
							jQuery( "<svg class='cr-upload-video-thumbnail' viewBox='0 0 576 512'><path d='M336.2 64H47.8C21.4 64 0 85.4 0 111.8v288.4C0 426.6 21.4 448 47.8 448h288.4c26.4 0 47.8-21.4 47.8-47.8V111.8c0-26.4-21.4-47.8-47.8-47.8zm189.4 37.7L416 177.3v157.4l109.6 75.5c21.2 14.6 50.4-.3 50.4-25.8V127.5c0-25.4-29.1-40.4-50.4-25.8z'></path></svg>" )
						);
					} else {
						container.append(
							jQuery( "<img>", {class:"cr-upload-images-thumbnail", src:URL.createObjectURL(uploadFiles[0].files[i])} )
						);
					}
					container.append(
						progressBar
					);
					let removeButton = jQuery( "<button/>", {class:"cr-upload-images-delete"} );
					removeButton.append(
						'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path class="cr-no-icon" d="M12.12 10l3.53 3.53-2.12 2.12L10 12.12l-3.54 3.54-2.12-2.12L7.88 10 4.34 6.46l2.12-2.12L10 7.88l3.54-3.53 2.12 2.12z"/></g></svg>'
					);
					container.append(
						removeButton
					);
					container.append(
						jQuery( "<input>", {name:"cr-upload-images-ids[]",type:"hidden",value:""} )
					);
					container.append(
						jQuery( "<span/>", {class:"cr-upload-images-delete-spinner"} )
					);
					mediaPreview.find( ".cr-form-item-media-add" ).remove();
					mediaPreview.append( container );
					if( countFiles + countUploaded < crMediaUploadLimit ) {
						mediaPreview.append(
							jQuery( "<div class='cr-form-item-media-add'>+</div>" )
						);
					}
				}
			}
			if( 0 < mediaPreview.children( ".cr-upload-images-containers" ).length && ! mediaPreview.hasClass( "cr-form-visible" ) ) {
				mediaPreview.parents( ".cr-form-item-subcontainer" ).addClass( "cr-form-visible" );
			}
			for(let i = 0; i < countFiles; i++) {
				let formData = new FormData();
				formData.append( "action", "cr_local_forms_upload_media" );
				formData.append( "cr_file", uploadFiles[0].files[i] );
				formData.append( "cr_form", jQuery( ".cr-form" ).data( "formid" ) );
				formData.append( "cr_item", jQuery( this ).parents( ".cr-form-item" ).data( "itemid" ) );
				var currentFileInput = jQuery( this );
				jQuery.ajax({
					url: crAjaxURL,
					data: formData,
					processData: false,
					contentType: false,
					dataType: "json",
					type: "POST",
					context: this,
					beforeSend: function() {
					},
					xhr: function() {
						var myXhr = jQuery.ajaxSettings.xhr();
						if ( myXhr.upload ) {
							myXhr.upload.addEventListener( 'progress', function(e) {
								if ( e.lengthComputable ) {
									let perc = ( e.loaded / e.total ) * 100;
									perc = perc.toFixed(0);
									currentFileInput.parent().find( ".cr-form-item-media-preview .cr-upload-images-containers.cr-upload-images-container-" + (lastIndex + i) + " .cr-upload-images-pbar .cr-upload-images-pbarin" ).width(perc + "%");
								}
							}, false );
						}
						return myXhr;
					},
					success: function(response) {
						if(200 === response["code"]) {
							let idkey = JSON.stringify( { "id": response["attachment"]["id"], "key": response["attachment"]["key"] } );
							currentFileInput.parent().find( ".cr-form-item-media-preview .cr-upload-images-containers.cr-upload-images-container-" + (lastIndex + i) + " input").val(idkey);
							currentFileInput.parent().find( ".cr-form-item-media-preview .cr-upload-images-containers.cr-upload-images-container-" + (lastIndex + i)).addClass("cr-upload-ok");
						} else if(500 <= response["code"]) {
							currentFileInput.parent().find( ".cr-form-item-media-preview .cr-upload-images-containers.cr-upload-images-container-" + (lastIndex + i)).remove();
							let mediaPreview = jQuery(this).closest(".cr-form-item-media").find(".cr-form-item-media-preview");
							let countUploaded = mediaPreview.find( ".cr-upload-images-containers" ).length;
							if( 0 < countUploaded ) {
								if( 0 === mediaPreview.children( ".cr-form-item-media-add" ).length ) {
									mediaPreview.append(
										jQuery( "<div class='cr-form-item-media-add'>+</div>" )
									);
								}
							} else {
								mediaPreview.removeClass( "cr-form-visible" );
								mediaPreview.parents( ".cr-form-item-subcontainer" ).removeClass( "cr-form-visible" );
							}
							jQuery( this ).closest( ".cr-form-item-media" ).find( ".cr-form-item-media-error" ).text( response["message"] );
							jQuery( this ).closest( ".cr-form-item-media" ).addClass( "cr-form-error" );
						}
					}
				});
			}
			jQuery(this).attr("data-lastindex", lastIndex + countFiles);
			uploadFiles.val("");
		} );
		// delete uploaded image
		jQuery(".cr-form-item-media-preview").on("click", ".cr-upload-images-delete", function (e) {
			e.preventDefault();
			let imgContainer = jQuery(this).parent(),
			mediaPreview = imgContainer.parent();
			let ajaxData = {
				"action": "cr_local_forms_delete_media",
				"image": jQuery( this ).parent().children( "input" ).eq( 0 ).val(),
				"cr_form": jQuery( ".cr-form" ).data( "formid" ),
				"cr_item": jQuery( this ).parents( ".cr-form-item" ).data( "itemid" )
			}
			imgContainer.addClass( "cr-upload-delete-pending" );
			jQuery.post( crAjaxURL, ajaxData, function(response) {
				imgContainer.removeClass( "cr-upload-delete-pending" );
				if( 200 === response["code"] ) {
					imgContainer.remove();
					let countUploaded = mediaPreview.children( ".cr-upload-images-containers" ).length;
					if( 0 < countUploaded ) {
						if( 0 === mediaPreview.children( ".cr-form-item-media-add" ).length ) {
							mediaPreview.append(
								jQuery( "<div class='cr-form-item-media-add'>+</div>" )
							);
						}
					} else {
						mediaPreview.removeClass( "cr-form-visible" );
						mediaPreview.parents( ".cr-form-item-subcontainer" ).removeClass( "cr-form-visible" );
					}
				}
			}, "json" );
		} );
	} );
} )();
