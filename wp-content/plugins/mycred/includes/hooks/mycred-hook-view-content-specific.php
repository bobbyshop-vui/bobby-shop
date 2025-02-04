<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Hook for viewing specific content (Member)
 * 
 * 
 */

 if ( ! class_exists( 'myCRED_Hook_View_Contents_Specific' ) ) :
 	class myCRED_Hook_View_Contents_Specific extends myCRED_Hook {

        public $user_id = 0;

        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = 'mycred_default' ) {

            parent::__construct( array(
                'id' => 'view_contents_specific',
                'defaults' => array( 
                    'creds'         => array(),
                    'log'           => array(),
                    'post_types'    => array(), 
                    'select_posts'  => array()
                )
            ), $hook_prefs, $type );
        }

        /**
         * Run
         */
        public function run() {

            if ( is_user_logged_in() ) {
                $this->user_id = get_current_user_id();
                add_action( 'template_redirect', array( $this, 'specific_content_loading' ), 9990 );
            }

            add_action( 'wp_ajax_mycred_specific_posts_for_users', array( $this, 'mycred_specific_posts_for_users' ) );
            add_action( 'wp_ajax_nopriv_mycred_specific_posts_for_users', array( $this, 'mycred_specific_posts_for_users' ) );
            
        }

        
        public function mycred_specific_posts_for_users() {

            // Get the post type from the AJAX request
            $post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : '';

            // Check if the post type is valid
            if ( empty( $post_type ) ) {
                wp_send_json_error( 'Invalid post type.' );
            }

            // Get the available post types
            $types = get_post_types( ['public' => true], 'objects' );
            $exclude = array( 'attachment', 'elementor_library' );

            // Check if the provided post type is valid and not excluded
            if ( ! isset( $types[$post_type] ) || in_array( $post_type, $exclude ) ) {
                wp_send_json_error( 'Invalid or excluded post type.' );
            }

            // Fetch the posts for the given post type
            $posts = get_posts([
                'post_type'      => $post_type,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]);

            // If no posts are found, return an error
            if ( empty( $posts ) ) {
                wp_send_json_error( 'No posts found.' );
            }

            // Prepare the data for the response
            $formatted_posts = array_map( function( $post ) {
                return [
                    'ID'    => $post->ID,
                    'title' => get_the_title( $post->ID ),
                    'link'  => get_permalink( $post->ID ),
                ];
            }, $posts );

            // Send the successful response with the posts data
            wp_send_json_success( $formatted_posts );
        }


       public function specific_content_loading() {

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

            // Login is required
            if (empty($user_id)) return;

            // Post author cannot generate points for themselves
            if ($post_author == $user_id) return;

            // Check if post type is in preferences
            if (!empty($prefs['post_types']) && in_array($post_type, $prefs['post_types'])) {
                $hook_index = array_search($post_type, $prefs['post_types']);
                
                // Flatten the select_posts array to get all selected post IDs
                $flat_select_posts = array_reduce($prefs['select_posts'], function($carry, $item) {
                    return array_merge($carry, $item); // Flatten inner arrays
                }, []);

                // Check if the post ID is in the flattened select_posts array
                if (in_array($post_id, $flat_select_posts)) {

                    // Make sure the user is not excluded from earning points
                    if (!$this->core->exclude_user($user_id)) {

                        // Ensure points, log, and preferences are set
                        if (!empty($prefs['creds']) && 
                            !empty($prefs['log']) && 
                            !empty($prefs['select_posts']) && 
                            !empty($prefs['post_types'])) {

                            // Prevent awarding points multiple times for the same post and user
                            if (!$this->core->has_entry('view_content', $post_id, $user_id) &&
                                apply_filters('mycred_view_specific_content', true, $this) === true) {

                                // Award the points
                                $this->core->add_creds(
                                    'view_content',               // Action ID
                                    $user_id,                     // User (viewer) to receive points
                                    $prefs['creds'][$hook_index], // Points amount
                                    $prefs['log'][$hook_index],   // Log message
                                    $post_id,                     // Post ID
                                    $ref_type,                    // Reference type (or metadata)
                                    $this->mycred_type            // Points type
                                );
                            }
                        }
                    }
                }
            }
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

            global $post;

            $prefs = $this->prefs;

            // Check if the preferences have been set; otherwise, use default values
            $specific_view_content_data = ( count( $prefs['creds'] ) > 0 )
                ? $this->mycred_specific_view_content_arrange_data_member( $prefs )
                : [[
                    'creds'    => 10,
                    'log'      => __( '%plural% for viewing a specific content (Member)', 'mycred' ),
                    'post_types'   => '0',
                    'select_posts' => array()
                ]];

            ?>
            <div class="hook-instance" id="specific-hook">
                <?php foreach ( $specific_view_content_data as $hook => $label ) { ?>
                    <div class="content_custom_hook_class">
                        <div class="row">

                            <!-- Post Type Selection -->
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label><?php esc_html_e( 'Choose post type', 'mycred' ); ?></label>
                                    <select name="<?php echo esc_attr( $this->specific_field_name( ['view_contents_specific' => 'post_types'] ) ); ?>"
                                            id="specific_content_<?php echo esc_attr( $hook ); ?>"
                                            class="form-control specific-content">
                                        <option value="0" disabled <?php selected( $label['post_types'], '0' ); ?>><?php esc_html_e( 'Choose a post type', 'mycred' ); ?></option>
                                        <?php
                                        $types = get_post_types( [ 'public' => true ], 'objects' );
                                        $exclude = [ 'attachment', 'elementor_library' ];

                                        foreach ( $types as $type ) {
                                            if ( ! in_array( $type->name, $exclude ) ) {
                                                echo '<option value="' . esc_attr( $type->name ) . '" ' . selected( $label['post_types'], $type->name, false ) . '>' . esc_html( $type->label ) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Post Selection -->
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label><?php esc_html_e( 'User Option', 'mycred' ); ?></label>
                                    <?php 
                                    $posts = get_posts( [
                                        'post_type'   => esc_attr( $label['post_types'] ),
                                        'post_status' => 'publish',
                                        'numberposts' => -1                         
                                    ] );

                                    $selected_options_ids = isset($label['select_posts']) ? $label['select_posts'] : array();
                                    $selected_options = array_unique($selected_options_ids);

                                    $options_args = [
                                        'name'     => 'mycred_pref_hooks[hook_prefs][view_contents_specific][select_posts][' . esc_attr( $hook ) . '][]',
                                        'id'       => 'contents_specific_' . esc_attr( $hook ), 
                                        'class'    => 'form-control post-options-member',
                                        'multiple' => 'multiple'
                                    ];

                                    $options = [];
                                    foreach ( $posts as $post ) {
                                        $options[ $post->ID ] = esc_html( get_the_title( $post->ID ) );
                                    }

                                    echo wp_kses( mycred_create_select2( $options, $options_args, $selected_options, '100%' ), 
                                         array(
                                        'select' => array('id' => array(), 'name' => array(), 'class' => array(), 'style' => array(), 'multiple' => array()),
                                        'option' => array('value' => array(), 'selected' => array()),
                                        )
                                      );
                                    ?>
                                </div>
                            </div>

                            <!-- Creds Input -->
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="<?php echo esc_attr( $this->field_id( ['view_contents_specific' => 'creds'] ) ); ?>">
                                        <?php echo esc_html( $this->core->plural() ); ?>
                                    </label>
                                    <input type="text" name="<?php echo esc_attr( $this->specific_field_name( ['view_contents_specific' => 'creds'] ) ); ?>" 
                                           id="<?php echo esc_attr( $this->field_id( ['view_contents_specific' => 'creds'] ) ); ?>" 
                                           value="<?php echo esc_attr( $this->core->number( $label['creds'] ) ); ?>" 
                                           class="form-control mycred-content-specific-creds" />
                                </div>
                            </div>

                            <!-- Log Template -->
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="<?php echo esc_attr( $this->field_id( ['view_contents_specific' => 'log'] ) ); ?>">
                                        <?php esc_html_e( 'Log Template', 'mycred' ); ?>
                                    </label>
                                    <input type="text" name="<?php echo esc_attr( $this->specific_field_name( ['view_contents_specific' => 'log'] ) ); ?>" 
                                           id="<?php echo esc_attr( $this->field_id( ['view_contents_specific' => 'log'] ) ); ?>" 
                                           value="<?php echo esc_attr( $label['log'] ); ?>" 
                                           class="form-control mycred-content-specific-log" />
                                    <span class="description"><?php echo $this->available_template_tags( ['general', 'post'] ); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Add/Remove Hook Buttons -->
                        <div class="row">
                            <div class="col-lg-12 mb-4 field_wrapper">
                                <div class="form-group specific-hook-actions textright">
                                    <button class="button button-small mycred-add-specific-view-content-hook add_button" id="clone_btn" type="button">
                                        <?php esc_html_e( 'Add More', 'mycred' ); ?>
                                    </button>
                                    <button class="button button-small mycred-remove-specific-view-content-hook" type="button">
                                        <?php esc_html_e( 'Remove', 'mycred' ); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <?php
        }

    
        public function mycred_specific_view_content_arrange_data_member( $specific_hook_data ) {

            // Ensure that the required keys are present in $specific_hook_data
            if ( ! isset( $specific_hook_data['creds'] ) || ! isset( $specific_hook_data['log'] ) || ! isset( $specific_hook_data['post_types'] ) ) {
                return false; // Return false or handle error appropriately
            }

            $hook_data = array();

            foreach ( $specific_hook_data['creds'] as $key => $value ) {
                $hook_data[$key]['creds']        = $value;
                $hook_data[$key]['log']          = isset( $specific_hook_data['log'][$key] ) ? sanitize_text_field( $specific_hook_data['log'][$key] ) : '';
                $hook_data[$key]['post_types']   = isset( $specific_hook_data['post_types'][$key] ) ? sanitize_text_field( $specific_hook_data['post_types'][$key] ) : '';
                $hook_data[$key]['select_posts'] = isset( $specific_hook_data['select_posts'][$key] ) 
                    ? array_map( 'sanitize_text_field', (array) $specific_hook_data['select_posts'][$key] ) 
                    : array();
            }

            // Only update the option if the data has changed
            $current_data = mycred_get_option( 'view_contents_specific', array() );
            if ( $hook_data !== $current_data ) {
                mycred_update_option( 'view_contents_specific', $hook_data );
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
                        $new_data[$data_key][$key] = ( ! empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for viewing a specific content (Member)';
                    }
                    else if ( $data_key == 'post_types' ) {
                        $new_data[$data_key][$key] = ( ! empty( $value ) ) ? sanitize_text_field( $value ) : 0;
                    }
                    else if ( $data_key == 'select_posts' ) {
                        $new_data[$data_key][$key] = ( ! empty( $value ) ) ? array_map( 'sanitize_text_field', (array) $value ) : array();
                    }
                }
            }

            mycred_update_option( 'view_contents_specific', $new_data );
            return $new_data;
                            
        }

  }
endif;