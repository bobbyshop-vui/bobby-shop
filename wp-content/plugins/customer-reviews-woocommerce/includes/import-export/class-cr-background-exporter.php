<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Background_Exporter' ) ) :

	include_once 'class-cr-background-process.php';

	class CR_Background_Exporter extends CR_Background_Process {

		public function __construct() {
			$this->prefix = 'wp_' . get_current_blog_id();
			$this->action = 'cr_exporter';

			parent::__construct();
		}

		protected function task( $data ) {
			$item = $data['item'];

			$row = array();
			$row[] = $item->comment_content;
			$row[] = get_comment_meta ( $item->comment_ID, 'rating', true );
			$row[] = $item->comment_date;
			$row[] = $item->ID == $data['shop_page_id'] ? -1 : $item->ID;
			$row[] = $item->comment_author;
			$row[] = $item->comment_author_email;
			$row[] = get_comment_meta ( $item->comment_ID, 'ivole_order', true );

			$media = array();
			// export images attached to reviews
			$images = get_comment_meta ( $item->comment_ID, CR_Reviews::REVIEWS_META_LCL_IMG, false );
			if ( is_array( $images ) && 0 < count( $images ) ) {
				foreach( $images as $image ) {
					$image_url = wp_get_attachment_url( $image );
					if ( $image_url ) {
						$media[] = $image_url;
					}
				}
			}
			// export images attached to reviews
			$videos = get_comment_meta ( $item->comment_ID, CR_Reviews::REVIEWS_META_LCL_VID, false );
			if ( is_array( $videos ) && 0 < count( $videos ) ) {
				foreach( $videos as $video ) {
					$video_url = wp_get_attachment_url( $video );
					if ( $video_url ) {
						$media[] = $video_url;
					}
				}
			}
			// save URLs of images and videos into the 'media' column of a CSV file
			if ( 0 < count( $media ) ) {
				$row[] = implode( ',', $media );
			} else {
				$row[] = '';
			}

			fputcsv($data['file'], $row);
		}

		protected function get_post_args() {
			if ( property_exists( $this, 'post_args' ) ) {
				return $this->post_args;
			}

			// Pass cookies through with the request so nonces function.
			$cookies = array();

			foreach ( $_COOKIE as $name => $value ) {
				if ( session_name() === $name ) {
						continue;
				}
				$cookies[] = new WP_Http_Cookie( array(
						'name'  => $name,
						'value' => $value,
				) );
			}

			return array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'body'      => $this->data,
				'cookies'   => $cookies,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			);
		}

		protected function handle() {
			global $wpdb;

			$this->lock_process();

			do {
				$batch = $this->get_batch();

				if ( empty( $batch->data ) ) {
					break;
				}

				$progress = get_transient( $batch->data['progress_id'] );

				if ( ! $progress ) {
					$progress = array();
				}

				$cancelled = get_transient( 'cancel' . $batch->data['progress_id'] );
				if ( $cancelled ) {
					$this->delete( $batch->key );
					$progress['status'] = 'cancelled';
					$progress['finished'] = current_time( 'timestamp' );
					set_transient( $batch->data['progress_id'], $progress );
					//@unlink( $batch->data['file'] );
					@unlink( $batch->data['temp_file'] );
					continue;
				}

				if(!isset($batch->data['offset'])){
					$batch->data['offset'] = 0;
				}
				$offset = $batch->data['offset'];
				$file = fopen( $batch->data['temp_file'], 'a' );

				if ( $file === false || empty( $progress ) ) {
					// Export failed
					$this->delete( $batch->key );
					$progress['status'] = 'failed';
					if($file === false) $progress['msg'] = sprintf(__("Export failed: Could not create a file in %s. Please check folder permissions.", 'customer-reviews-woocommerce' ), '<code>'.dirname($batch->data['temp_file']).'</code>');
					else $progress['msg'] = __("Export failed: Cannot identify process/progress", 'customer-reviews-woocommerce' );
					$progress['finished'] = current_time( 'timestamp' );
					set_transient( $batch->data['progress_id'], $progress );
					@unlink( $batch->data['temp_file'] );
					continue;
				}

				$cancelled = get_transient( 'cancel' . $batch->data['progress_id'] );
				if ( $cancelled ) {
					$this->delete( $batch->key );
					$progress['status'] = 'cancelled';
					$progress['finished'] = current_time( 'timestamp' );
					set_transient( $batch->data['progress_id'], $progress );
					@unlink( $batch->data['temp_file'] );
					continue;
				}

				if ( $offset == 0 ) {
					fputcsv( $file, CR_Reviews_Exporter::get_columns() );
				}

				$cancel_query = $wpdb->prepare(
					"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
					'_transient_cancel' . $batch->data['progress_id']
				);

				$shop_page_id = wc_get_page_id( 'shop' );

				$query = "SELECT * FROM $wpdb->comments c " .
					"INNER JOIN $wpdb->posts p ON p.ID = c.comment_post_ID " .
					"INNER JOIN $wpdb->commentmeta m ON m.comment_id = c.comment_ID " .
					"WHERE c.comment_approved = '1' AND (p.post_type = 'product' OR p.ID = ".$shop_page_id.") AND m.meta_key ='rating'" .
					"LIMIT ".$offset.",".$batch->data['limit']
				;


				$result = $wpdb->get_results($query);

				$task_data = array();
				$task_data['file'] = $file;
				$task_data['shop_page_id'] = $shop_page_id;

				foreach($result as $item){

					$task_data['item'] = $item;

					$this->task($task_data);

					$progress['reviews']['exported']++;

					set_transient( $batch->data['progress_id'], $progress );

					$offset++;

				}

				if ( count( $result ) === 0) {
					// Export is complete
					$this->delete( $batch->key );
					$progress['status'] = 'complete';
					$progress['finished'] = current_time( 'timestamp' );
					set_transient( $batch->data['progress_id'], $progress );

					rename( $batch->data['temp_file'], $batch->data['file'] );
				} else {
					//$batch->data['offset'] += count( $result );
					$batch->data['offset'] = $offset;

					$this->update( $batch->key, $batch->data );
				}

				fclose( $file );
			} while ( ! $this->time_exceeded() && ! $this->memory_exceeded() && ! $this->is_queue_empty() );

			$this->unlock_process();

			if ( ! $this->is_queue_empty() ) {
				$this->dispatch();
			} else {
				$this->complete();
			}
		}

	}

endif;
