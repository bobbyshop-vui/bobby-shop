jQuery(document).ready(() => {
    jQuery('.post-options-author').select2();
    jQuery('.post-options-member').select2();

    jQuery(document).on('specific-content-select2', function(e) {
        jQuery('.post-options-author').select2();
        jQuery('.post-options-member').select2();
    });

      jQuery('#specific_content_author_0 option[value="0"], #specific_content_0 option[value="0"]').prop('disabled', true);
   
    jQuery(document).on( 'click', '.mycred-add-specific-view-content-hook-author', function() {
        var closest = jQuery(this).closest('.content_custom_hook_class_author');
        closest.find('.post-options-author').select2('destroy');
        var hook = closest.clone();
        var index = jQuery('#widgets-right').find('.content_custom_hook_class_author').length;
        jQuery(this).closest('.content_custom_hook_class_author').after( hook );
        hook.find('.post-options-author').attr( 'name', 'mycred_pref_hooks[hook_prefs][view_contents_specific_author][select_posts][' + index + '][]' );
        hook.find('.post-options-author').attr( 'id', 'contents_specific_author_' + index );
        hook.find('select.specific-content-author').val('0');
        hook.find('input.mycred-content-specific-creds-author').val('10');
        hook.find('input.mycred-content-specific-log-author').val('%plural% for viewing a specific content (Author)');
        hook.find('select.post-options-author').val('');

        jQuery('.post-options-author').select2();
        jQuery('select.post-options-author').trigger('change');
    });

    jQuery(document).on('click', '.mycred-add-specific-view-content-hook', function() {
        var closest = jQuery(this).closest('.content_custom_hook_class');
        closest.find('.post-options-member').select2('destroy');
        var hook = closest.clone();
        var index = jQuery('#widgets-right').find('.content_custom_hook_class').length;
        jQuery(this).closest('.content_custom_hook_class').after(hook);

        hook.find('.post-options-member').attr('name', 'mycred_pref_hooks[hook_prefs][view_contents_specific][select_posts][' + index + '][]');
        hook.find('.post-options-member').attr('id', 'contents_specific_' + index);
        hook.find('select.specific_content').val('0');
        hook.find('input.mycred-content-specific-creds').val('10');
        hook.find('input.mycred-content-specific-log').val('%plural% for viewing specific content (Member)');
        hook.find('select.post-options-member').val('');

        jQuery('.post-options-member').select2();
        jQuery('select.post-options-member').trigger('change');
    });

    jQuery(document).on( 'click', '.mycred-remove-specific-view-content-hook-author', function() {
        var container = jQuery(this).closest('.hook-instance');
        if ( container.find('.content_custom_hook_class_author').length > 1 ) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog == true) {
                jQuery(this).closest('.content_custom_hook_class_author').remove();
                jQuery('select.post-options-author').trigger('change');
            } 
        }
    }); 

     jQuery(document).on('click', '.mycred-remove-specific-view-content-hook', function() {
        var container = jQuery(this).closest('.hook-instance');
        if (container.find('.content_custom_hook_class').length > 1) {
            var dialog = confirm("Are you sure you want to remove this hook?");
            if (dialog === true) {
                jQuery(this).closest('.content_custom_hook_class').remove();
                jQuery('select.post-options-member').trigger('change');
            }
        }
    });

    jQuery(document).on('change', 'select.post-options-author', function(){
        user_content_enable_disable_options_author( jQuery(this) );
    });

    jQuery(document).on('change', 'select.post-options-member', function(){
        user_content_enable_disable_options_member( jQuery(this) );
    });

    jQuery(document).on('change', 'select.specific-content-author', function() {
    var _this = jQuery(this);
    var value = _this.val();

    // Prevent empty requests
    if (!value) {
        return; // Exit if no value is selected
    }

    var data = {
        'action': 'mycred_specific_posts_for_users_author',
        'post_type_author': value,
    };

    // Clear existing options and show "Loading..." while waiting for response
    var ele = _this.closest('.content_custom_hook_class_author').find('.post-options-author');
    ele.find('option').remove();
    ele.append("<option value=''>Loading...</option>");

    // Make AJAX request
    jQuery.post(ajaxurl, data)
        .done(function(response) {
            ele.find('option').remove(); // Clear options

            // Dynamically set the default option text based on the selected post type
            var defaultOptionText = "Select " + _this.find("option:selected").text(); // Use selected option's text
            ele.append("<option value='' disabled>" + defaultOptionText + "</option>");

            if (response && response.length > 0) {
                // Populate with posts from response
                jQuery.each(response, function(index, val) {
                    ele.append("<option value='" + val.ID + "'>" + val.title + "</option>");
                });
            } else {
                // Add fallback if no posts are available
                ele.append("<option value=''>No posts available</option>");
            }
        })
        .fail(function() {
            ele.find('option').remove(); // Clear options
            ele.append("<option value=''>Error loading posts</option>"); // Show error message
            alert('An error occurred while fetching posts. Please try again.');
        });
});


   jQuery(document).on('change', 'select.specific-content', function () {
    var _this = jQuery(this);
    var selectedPostTypeText = _this.find('option:selected').text(); // Get the text of the selected post type

    // Find the "User Selected Option" dropdown
    var postOptionsDropdown = _this
        .closest('.content_custom_hook_class')
        .find('.post-options-member');

    // Clear current options and set a dynamic placeholder
    postOptionsDropdown.empty();
    postOptionsDropdown.append("<option value='' disabled>Select " + selectedPostTypeText + "</option>");

    // Prepare data for the AJAX request
    var data = {
        'action': 'mycred_specific_posts_for_users',
        'post_type': _this.val(),
    };

    // Add a loading indicator while fetching posts
    postOptionsDropdown.append("<option value=''>Loading...</option>");

    // Perform the AJAX request
    jQuery.post(ajaxurl, data, function (response) {
        postOptionsDropdown.empty(); // Clear options again after response

        if (response.success && response.data.length > 0) {
            // Add the dynamic default option
            postOptionsDropdown.append(
                "<option value=''>Select " + selectedPostTypeText + "</option>"
            );

            // Populate the dropdown with returned posts
            jQuery.each(response.data, function (index, post) {
                postOptionsDropdown.append(
                    "<option value='" + post.ID + "'>" + post.title + "</option>"
                );
            });
        } else if (response.success && response.data.length === 0) {
            // If no posts are found
            postOptionsDropdown.append(
                "<option value=''>No " + selectedPostTypeText + " available</option>"
            );
        } else {
            // Handle error
            postOptionsDropdown.append("<option value=''>Error loading posts</option>");
            alert('An error occurred while retrieving posts.');
        }
    }).fail(function () {
        // Handle AJAX failure
        postOptionsDropdown.empty();
        postOptionsDropdown.append("<option value=''>Error loading posts</option>");
        alert('An error occurred. Please try again.');
    });
});


	
});

function user_content_enable_disable_options_author( ele ) {
    var selected = [];
    var container = ele.closest('.hook-instance');
    container.find('select.specific-content-author').each(function () {
        container.find('select.specific-content-author').not(jQuery(this)).find('option[value="'+jQuery(this).val()+'"]').attr('disabled', 'disabled');
        selected.push( jQuery(this).val() );
    });
    container.find('option').each(function () { 
        if( ! selected.includes( jQuery(this).attr('value')) ) {
            container.find('select.specific-content-author').find('option[value="'+jQuery(this).val()+'"]').removeAttr('disabled');
        }
    });
}

function user_content_enable_disable_options_member( ele ) {
    var selected = [];
    var container = ele.closest('.hook-instance');
    container.find('select.specific-content').each(function () {
        container.find('select.specific-content').not(jQuery(this)).find('option[value="'+jQuery(this).val()+'"]').attr('disabled', 'disabled');
        selected.push( jQuery(this).val() );
    });
    container.find('option').each(function () { 
        if( ! selected.includes( jQuery(this).attr('value')) ) {
            container.find('select.specific-content').find('option[value="'+jQuery(this).val()+'"]').removeAttr('disabled');
        }
    });
}
