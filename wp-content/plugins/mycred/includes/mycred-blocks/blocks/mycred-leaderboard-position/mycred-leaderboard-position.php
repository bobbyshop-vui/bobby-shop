<?php
namespace MG_Blocks;

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('mycred_leaderboard_position') ) :
    class mycred_leaderboard_position {

        public function __construct() {
            add_action('enqueue_block_editor_assets', array( $this, 'register_assets' ) );

            register_block_type( 
                'mycred-gb-blocks/mycred-leaderboard-position', 
                array( 'render_callback' => array( $this, 'render_block' ) )
            );
        
        }

        public function register_assets() {
            wp_enqueue_script(
                'mycred-leaderboard-position', 
                plugins_url('index.js', __FILE__), 
                array( 
                    'wp-blocks', 
                    'wp-element', 
                    'wp-components', 
                    'wp-block-editor', 
                    'wp-rich-text' 
                )
            );

            $mycred_types = mycred_get_types(true);
            $mycred_types = array_merge( array( '' => __('Select point type', 'mycred') ), $mycred_types );
            wp_localize_script('mycred-leaderboard-position', 'mycred_types', $mycred_types);
        }

        public function render_block( $attributes, $content ) {

            return "[mycred_leaderboard_position " . mycred_blocks_functions::mycred_extract_attributes( $attributes ) . "]";
        }

    }
endif;

new mycred_leaderboard_position();