<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('CR_Reviews_Exporter')) :

    require_once('class-cr-background-exporter.php');

    class CR_Reviews_Exporter {

        const FILE_PATH = 'export_reviews.csv';
        const TEMP_FILE_PATH = 'export_reviews_temp.csv';

        protected static $background_exporter;

        /**
         * @var string The path to the csv file
         */
        private $file_path;
        /**
         * @var string The path to the temp csv file
         */
        private $temp_file_path;

        private $limit = 100;

        public static $columns = array(
            'review_content',
            'review_score',
            'date',
            'product_id',
            'display_name',
            'email',
            'order_id',
            'media'
        );

        public function __construct(){
            add_action('wp_ajax_cr_start_reviews_export', array( $this, 'start_export' ));
            add_action('wp_ajax_cr_check_export_progress', array( $this, 'check_progress' ));
            add_action('wp_ajax_cr_cancel_reviews_export', array( $this, 'cancel_export' ));
        }

        /**
         * Start export
         */
        public function start_export(){
            global $wpdb;
						if( ! check_ajax_referer( 'cr-export-reviews', 'nonce', false ) ) {
							echo wp_json_encode(
								array(
									'success' => false,
									'data'    => array(
										'message'  => __( 'Error: nonce expired, please reload the page and try again', 'customer-reviews-woocommerce' )
									)
								)
							);
							wp_die();
						}

            $this->file_path = get_temp_dir() . self::FILE_PATH;
            $this->temp_file_path = get_temp_dir() . self::TEMP_FILE_PATH;

            //make sure that the folder exists
            $dirname = dirname( $this->temp_file_path );
            if ( ! is_dir( $dirname ) ) {
              $res = mkdir( $dirname, 0755 );
              if($res === false){
                echo wp_json_encode(
                  array(
                    'success' => false,
                    'data'    => array(
                      'message'  => sprintf( __( 'Export failed: Could not create a folder in %s. Please check folder permissions.', 'customer-reviews-woocommerce' ), '<code>' . dirname( $dirname ) . '</code>' ),
                    )
                  )
                );
                wp_die();
              }
            }

            $query = "SELECT COUNT(*) FROM $wpdb->comments c " .
                "INNER JOIN $wpdb->posts p ON p.ID = c.comment_post_ID " .
                "INNER JOIN $wpdb->commentmeta m ON m.comment_id = c.comment_ID " .
                "WHERE c.comment_approved = '1' AND (p.post_type = 'product' OR p.ID = ".wc_get_page_id( 'shop' ).") AND m.meta_key ='rating'"
            ;

            $count = $wpdb->get_var($query);

            $progress_id = 'export_reviews_progress_' . uniqid();
            $progress = array(
                'status'  => 'exporting',
                'started' => current_time('timestamp'),
                'reviews' => array(
                    'total'    => $count,
                    'exported' => 0,
                )
            );

            set_transient($progress_id, $progress, WEEK_IN_SECONDS);

            $batch = array(
                'file'        => $this->file_path,
                'temp_file'   => $this->temp_file_path,
                'offset'      => 0,
                'progress_id' => $progress_id,
                'limit'       => $this->limit,
            );

            self::$background_exporter->data($batch);

            $cookies = array();
            foreach ($_COOKIE as $name => $value) {
                if ( session_name() === $name ) {
                    continue;
                }
                $cookies[] = new WP_Http_Cookie(array(
                    'name'  => $name,
                    'value' => $value,
                ));
            }

            self::$background_exporter->post_args = array(
                'timeout'   => 10,
                'blocking'  => true,
                'body'      => $batch,
                'cookies'   => $cookies,
                'sslverify' => apply_filters('https_local_ssl_verify', false),
            );

            // We need to check to ensure that basic auth isn't blocking the background process
            $response = self::$background_exporter->save()->dispatch();
            $status = wp_remote_retrieve_response_code($response);

            if ($status === 401) {
                echo wp_json_encode(
                    array(
                        'success' => false,
                        'data'    => array(
                            'message'  => __( 'Failed to start background exporter, please disable Basic Auth and retry', 'customer-reviews-woocommerce' ),
                        )
                    )
                );
                wp_die();
            }

            echo wp_json_encode(
                array(
                    'success' => true,
                    'data'    => array(
                        'file_path'        => $this->file_path,
                        'temp_file_path'   => $this->temp_file_path,
                        'num_rows'         => $count,
                        'progress_id'      => $progress_id
                    )
                )
            );
            wp_die();
        }

        public function check_progress()
        {
          if( check_ajax_referer( 'cr-export-progress', 'nonce', false ) ) {
            $progress_id = $_POST['progress_id'];
            $progress = get_transient($progress_id);
            wp_send_json($progress, 200);
          }
          wp_die();
        }

        public function cancel_export()
        {
          if( check_ajax_referer( 'cr-export-cancel', 'nonce', false ) ) {
            $progress_id = $_POST['progress_id'];
            $progress = get_transient($progress_id);
            set_transient('cancel' . $progress_id, true, WEEK_IN_SECONDS);
            $progress['status'] = 'cancelled';
            $progress['finished'] = current_time( 'timestamp' );
            wp_send_json($progress, 200);
          }
          wp_die();
        }

        /**
         * Initialize the background exporter process
         */
        public static function init_background_exporter()
        {
            self::$background_exporter = new CR_Background_Exporter();
        }

        public static function get_columns()
        {
            return self::$columns;
        }
    }

endif;
