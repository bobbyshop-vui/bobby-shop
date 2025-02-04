<?php
namespace MG_Blocks;

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('myCRED_content_buyer_count') ) :
    class myCRED_content_buyer_count {

        public function __construct() {
            add_action('enqueue_block_editor_assets', array( $this, 'register_assets' ) );

            register_block_type( 
                'mycred-gb-blocks/mycred-content-buyer-count', 
                array( 'render_callback' => array( $this, 'render_block' ) )
            );
        
        }

        public function register_assets() {
            wp_enqueue_script(
                'mycred-content-buyer-count', 
                plugins_url('index.js', __FILE__), 
                array( 
                    'wp-blocks', 
                    'wp-element', 
                    'wp-components', 
                    'wp-block-editor', 
                    'wp-rich-text' 
                )
            );

        }

        public function render_block( $attributes, $content ) {
            
            // Extract the attributes
            $wrapper = isset($attributes['wrapper']) ? $attributes['wrapper'] : '';
            $post_id = isset($attributes['postID']) ? intval($attributes['postID']) : null;

            // Call the function to get the buyer count
            $buyer_count = mycred_get_content_buyers_count($post_id); // Replace with your actual function to fetch buyer count

            // Prepare the content
            $output = '';

            // Wrap the content if a wrapper is specified
            if (!empty($wrapper)) {
                $output .= '<' . esc_html($wrapper) . ' class="mycred-buyer-count">';
            }

            $output .= esc_html($buyer_count); // Escape the buyer count output

            // Close the wrapper if one was opened
            if (!empty($wrapper)) {
                $output .= '</' . esc_html($wrapper) . '>';
            }

            return $output; // Return the rendered output
        }


    }
endif;

new mycred_content_buyer_count();