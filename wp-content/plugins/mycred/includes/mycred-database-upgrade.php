<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

if ( ! class_exists( 'myCRED_Database_Upgrader' ) ) :
    class myCRED_Database_Upgrader {

        public function __construct() {
            add_action( 'mycred_init', array( $this, 'mycred_init_database' ) );
        }

        public function mycred_init_database() {
            $db_version = mycred_get_option( 'mycred_version_db', false );

            if ( version_compare( myCRED_DB_VERSION, $db_version ) ) {
                add_action( 'admin_notices', array( $this, 'mycred_update_notice' ) );
                add_action( 'wp_ajax_mycred_update_database', array( $this, 'mycred_update_database' ) );
            }
        }

        public function mycred_update_notice() { ?>
            <div class="notice notice-warning is-dismissible">
                <h2 style="margin-bottom: 8px;">myCred DataBase Update Required</h2>
                <p class="mycred-update-description">We need to upgrade the database.<br />
                <a href="https://mycred.me/blog/database-update/" target="_blank">Why am I seeing this notice?</a></p>
                <p class="mycred-update-waiting" style="display: none">Please wait while database is upgrading.</p>
                <p class="mycred-update-success" style="display: none">Thank you.</p>
                <button class="mycred-update-database button button-primary" data-nonce="<?php echo wp_create_nonce('mycred_update_nonce'); ?>">Update Database Now</button>
            </div>
            <script type="text/javascript">
                jQuery('.mycred-update-database').click(function(){
                    var nonce = jQuery(this).data('nonce');
                    jQuery.ajax({
                        url: ajaxurl,
                        data: {
                            action: 'mycred_update_database',
                            security: nonce
                        },
                        type: 'POST',
                        beforeSend: function() {
                            jQuery('.mycred-update-description').hide();
                            jQuery('.mycred-update-waiting').show();
                        },
                        success:function(data) {
                            jQuery('.mycred-update-database').hide();
                            jQuery('.mycred-update-waiting').hide();
                            jQuery('.mycred-update-success').show();
                        }
                    });
                });
            </script>
            <?php
        }

        public function mycred_update_database() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( __( 'Unauthorized user', 'mycred' ) );
            }

            check_ajax_referer( 'mycred_update_nonce', 'security' );

            $this->add_indexes();
            wp_die();
        }

        public function add_indexes() {
            global $wpdb;
            $table = $wpdb->prefix . 'myCRED_log';
        
            function index_exists( $table, $index_name ) {
                global $wpdb;
                $query = $wpdb->prepare(
                    "SHOW INDEX FROM $table WHERE Key_name = %s",
                    $index_name
                );
                $result = $wpdb->get_results( $query );
                return ! empty( $result );
            }
        
            $indexes = array(
                'ref' => "CREATE INDEX `ref` ON `{$table}`(`ref`)",
                'user_id' => "CREATE INDEX `user_id` ON `{$table}`(`user_id`)",
                'ref_id' => "CREATE INDEX `ref_id` ON `{$table}`(`ref_id`)",
                'ctype' => "CREATE INDEX `ctype` ON `{$table}`(`ctype`)",
                'time' => "CREATE INDEX `time` ON `{$table}`(`time`)"
            );
        
            foreach ( $indexes as $index_name => $sql ) {
                if ( ! index_exists( $table, $index_name ) ) {
                    $wpdb->query( $sql );
                }
            }
        
            update_option( 'mycred_version_db', myCRED_DB_VERSION );
        }
        
    }
endif;

new myCRED_Database_Upgrader();