<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Hook for viewing specific content (Author)
 * 
 * 
 */
 if ( ! class_exists( 'myCRED_Hook_View_Contents_Specific_Author' ) ) :
 	class myCRED_Hook_View_Contents_Specific_Author extends myCRED_Hook {

        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

            parent::__construct( array(
                'id' => 'view_contents_specific_author',
                'defaults' => array(
                    'creds'         => array(),
                    'log'           => array(),
                    'post_types'    => array(), 
                    'select_posts'  => array()
                ),
            ), $hook_prefs, $type );
        }

        /**
         * 
        * Run
        * 
        */
        public function run() {

            add_action( 'template_redirect', array( $this, 'specific_content_loading_author' ), 9999 );
            add_action( 'wp_ajax_mycred_specific_posts_for_users_author', array( $this, 'mycred_specific_posts_for_users_author' ) );
            add_action( 'wp_ajax_nopriv_mycred_specific_posts_for_users_author', array( $this, 'mycred_specific_posts_for_users_author' ) );

        }

        public function specific_content_loading_author() {

            global $post;

            $user_id    = get_current_user_id();
            $post_type  = $post->post_type;
            $ref_type   = array('ref_type' => $post->post_type);
            $prefs      = $this->prefs;
            $post_author = absint($post->post_author);

            // Treat shop page as 'page' if WooCommerce shop page is being viewed
            if (function_exists('is_shop') && is_shop()) {
                $post_type = 'page';
                $post_id = wc_get_page_id('shop'); // Get the actual shop page ID
            } else {
                $post_id = $post->ID; // Default to the current post ID
            }

            // Check if the user is logged in and if they are the post author
            if (is_user_logged_in() && $post_author == $user_id) {

                // Check if post type is in preferences
                if (!empty($prefs['post_types']) && in_array($post_type, $prefs['post_types'])) {
                    
                    $hook_index = array_search($post_type, $prefs['post_types']);
                    
                    // Flatten the select_posts array to get all selected post IDs
                    $flat_select_posts = array_reduce($prefs['select_posts'], function($carry, $item) {
                        return array_merge($carry, $item); // Flatten inner arrays
                    }, []);

                    // Check if the post ID is in the flattened select_posts array
                    if (in_array($post_id, $flat_select_posts)) {

                        // Check if points have already been awarded for this post and author
                        if ($this->core->has_entry('view_content_author', $post_id, $post_author)) {
                            return; // Exit if points were already awarded
                        }

                        // Make sure the author is not excluded from earning points
                        if (!$this->core->exclude_user($post->post_author)) {
                            
                            // Ensure that the points, log entry, and other preferences are set
                            if (!empty($prefs['creds'][$hook_index]) && 
                                !empty($prefs['log'][$hook_index]) && 
                                !empty($prefs['select_posts'][$hook_index]) && 
                                !empty($prefs['post_types'][$hook_index])) {

                                // Allow for filtering and conditionally awarding points
                                if (apply_filters('mycred_view_content_specific_author', true, $this) === true) {
                                    // Award the points
                                    $this->core->add_creds(
                                        'view_content_author', // Action ID
                                        $post_author,          // User (author) to receive points
                                        $prefs['creds'][$hook_index],  // Points amount
                                        $prefs['log'][$hook_index],    // Log message
                                        $post_id,                      // Post ID
                                        $ref_type,                     // Reference type
                                        $this->mycred_type             // Points type
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        
        public function mycred_specific_posts_for_users_author() {

            $post_type_author = isset( $_POST['post_type_author'] ) ? sanitize_text_field( $_POST['post_type_author'] ) : '';

            $prefs = $this->prefs;
            $posts_complete_data = $this->mycred_specific_view_content_arrange_data( $prefs );
            $types = get_post_types( ['public' => true], 'objects' );
            $exclude = array( 'attachment', 'elementor_library' );
            $selected_posts = array();

            foreach ( $types as $key => $type ) {
                if ( ! in_array( $type->name, $exclude ) ) {
                    // Compare sanitized post type.
                    if ( $post_type_author === $type->name ) {

                        $post_type_name = $type->name;

                        // Fetch posts
                        $posts = get_posts([
                            'post_type'         => $post_type_name,
                            'post_status'       => 'publish',
                            'posts_per_page'    => -1,
                            'orderby'           => 'title',
                            'order'             => 'ASC',
                        ]);

                        // Create a simplified array of posts with only required data
                        $formatted_posts = array_map( function( $post ) {
                            return [
                                'ID'    => $post->ID,
                                'title' => get_the_title( $post->ID ),
                                'link'  => get_permalink( $post->ID ),
                            ];
                        }, $posts );

                        // Send JSON response and terminate script
                        wp_send_json( $formatted_posts );
                    }
                }
            }

            // In case no valid post type is found, return an error.
            wp_send_json_error( 'Invalid post type or no posts found.' );
            wp_die();
        }


        public function specific_field_name( $field = '' ) {

            if ( is_array( $field ) ) {
                $array = array();
                foreach ( $field as $parent => $child ) {
                    if ( ! is_numeric( $parent ) )
                        $array[] = $parent;

                    if ( ! empty( $child ) && ! is_array( $child ) )
                        $array[] = $child;
                }

                $field = '[' . implode( '][', $array ) . ']';
            }
            else {
                $field = '[' . $field . ']';
            }

            $option_id = 'mycred_pref_hooks';
            if ( ! $this->is_main_type )
            $option_id = $option_id . '_' . $this->mycred_type;

            return $option_id . '[hook_prefs]' . $field . '[]';

        }

        public function preferences() {

            $prefs = $this->prefs;

            // If preferences exist, arrange data; otherwise, set default values
            if ( count( $prefs['creds'] ) > 0 ) {  
                $specific_view_content_data = $this->mycred_specific_view_content_arrange_data( $prefs );
            } else {
                $specific_view_content_data = array(
                    array(
                        'creds' => 10,
                        'log' => __('%plural% for viewing a specific content (Author)', 'mycred'),
                        'post_types' => '0', // Use string to match HTML select value
                        'select_posts' => array()
                    )
                );
            }
            ?>
            <div class="hook-instance" id="specific-hook">
                <?php 
                foreach( $specific_view_content_data as $hook => $label ) { ?>
                <div class="content_custom_hook_class_author">
                    <div class="row">
                        <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                            <div class="form-group">
                                <label><?php esc_html_e('Choose post type', 'mycred'); ?></label>
                                <select name="<?php echo esc_attr($this->specific_field_name(array('view_contents_specific_author' => 'post_types'))); ?>" 
                                        id="specific_content_author_<?php echo esc_attr($hook); ?>" 
                                        class="form-control specific-content-author">
                                    <option value="0" disabled <?php selected($label['post_types'], '0'); ?>>Choose a post type</option>
                                    <?php
                                    $types = get_post_types(['public' => true], 'objects');
                                    $exclude = array('attachment', 'elementor_library');

                                    foreach ($types as $type) {
                                        if (!in_array($type->name, $exclude)) {
                                            $selected = selected($label['post_types'], $type->name, false);
                                            echo '<option value="' . esc_attr($type->name) . '" ' . $selected . '>' . esc_html($type->label) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                            <div class="form-group">
                                <label><?php _e('User Option', 'mycred'); ?></label>
                                <?php 
                                $posts = get_posts([
                                    'post_type' => esc_attr($label['post_types']),
                                    'post_status' => 'publish',
                                    'numberposts' => -1                         
                                ]);

                                $selected_options_ids = isset($label['select_posts']) ? $label['select_posts'] : array();
                                $selected_options = array_unique($selected_options_ids);

                                $options_args = array(
                                    'name' => 'mycred_pref_hooks[hook_prefs][view_contents_specific_author][select_posts][' . esc_attr($hook) . '][]',
                                    'id'   => 'contents_specific_author_' . esc_attr($hook),
                                    'class' => 'form-control post-options-author',
                                    'multiple' => 'multiple'
                                );

                                $options = array();
                                if (!empty($posts)) {
                                    foreach ($posts as $post) {
                                        $options[$post->ID] = esc_html(get_the_title($post->ID));
                                    }
                                }

                                echo wp_kses(
                                    mycred_create_select2($options, $options_args, $selected_options, '100%'),
                                    array(
                                        'select' => array('id' => array(), 'name' => array(), 'class' => array(), 'style' => array(), 'multiple' => array()),
                                        'option' => array('value' => array(), 'selected' => array()),
                                    )
                                ); 
                                ?>
                            </div>
                        </div>

                        <!-- Remaining fields for creds and log -->
                        <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id(array('view_contents_specific_author' => 'creds'))); ?>">
                                    <?php echo esc_html($this->core->plural()); ?>
                                </label>
                                <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('view_contents_specific_author' => 'creds'))); ?>" 
                                       id="<?php echo esc_attr($this->field_id(array('view_contents_specific_author' => 'creds'))); ?>" 
                                       value="<?php echo esc_attr($this->core->number($label['creds'])); ?>" 
                                       class="form-control mycred-content-specific-creds-author" /> 
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id(array('view_contents_specific_author' => 'log'))); ?>">
                                    <?php esc_html_e('Log Template', 'mycred'); ?>
                                </label>
                                <input type="text" name="<?php echo esc_attr($this->specific_field_name(array('view_contents_specific_author' => 'log'))); ?>" 
                                       id="<?php echo esc_attr($this->field_id(array('view_contents_specific_author' => 'log'))); ?>" 
                                       value="<?php echo esc_attr($label['log']); ?>" 
                                       class="form-control mycred-content-specific-log-author" />
                                <span class="description"><?php echo $this->available_template_tags(array('general', 'post')); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add and remove buttons -->
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mb-4 field_wrapper">
                            <div class="form-group specific-hook-actions textright">
                                <button class="button button-small mycred-add-specific-view-content-hook-author add_button" id="clone_btn" type="button"><?php _e('Add More','mycred'); ?></button>
                                <button class="button button-small mycred-remove-specific-view-content-hook-author" type="button"><?php _e('Remove','mycred'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                } ?>
            </div>
            <?php
        }

       
        public function mycred_specific_view_content_arrange_data( $specific_hook_data ) {

            // Ensure that the required keys are present in $specific_hook_data
            if ( ! isset( $specific_hook_data['creds'] ) || ! isset( $specific_hook_data['log'] ) || ! isset( $specific_hook_data['post_types'] ) ) {

                return false; // Return false or handle error appropriately
            }

            $hook_data = array();

            foreach ( $specific_hook_data['creds'] as $key => $value ) {
                $hook_data[$key]['creds']        = $value;
                $hook_data[$key]['log']          = isset( $specific_hook_data['log'][$key] ) ? $specific_hook_data['log'][$key] : '';
                $hook_data[$key]['post_types']   = isset( $specific_hook_data['post_types'][$key] ) ? $specific_hook_data['post_types'][$key] : '';
                $hook_data[$key]['select_posts'] = isset( $specific_hook_data['select_posts'][$key] ) ? $specific_hook_data['select_posts'][$key] : array();
            }

            // Only update the option if the data has changed
            $current_data = mycred_get_option( 'view_contents_specific_author', array() );
            if ( $hook_data !== $current_data ) {
                mycred_update_option( 'view_contents_specific_author', $hook_data );
            }

            return $hook_data;
        }


        public function sanitise_preferences( $data ) {

            $new_data = array();
            foreach ( $data as $data_key => $data_value ) {
                foreach ( $data_value as $key => $value ) {
                    if ( $data_key == 'creds' ) {
                        $new_data[$data_key][$key] = ( ! empty( $value ) ) ? floatval( $value ) : 10;
                    }
                    else if ( $data_key == 'log' ) {
                        $new_data[$data_key][$key] = ( ! empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for viewing a specific content (Author)';
                    }
                    else if ( $data_key == 'post_types' ) {
                        $new_data[$data_key][$key] = ( ! empty( $value ) ) ? sanitize_text_field( $value ) : 0;
                    }
                    else if ( $data_key == 'select_posts' ) {
                        $new_data[$data_key][$key] = ( ! empty( $value ) ) ? array_map( 'sanitize_text_field', (array) $value ) : array();
                    }
                }
            }

            mycred_update_option( 'view_contents_specific_author', $new_data );
            return $new_data;
        }
    }

endif;