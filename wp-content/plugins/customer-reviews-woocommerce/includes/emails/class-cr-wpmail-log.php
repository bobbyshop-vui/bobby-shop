<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_WPMail_Log' ) ) :

	class CR_WPMail_Log {
		const LOGS_TABLE = 'cr_wpmail_log';

		public function __construct() {
			add_action( 'wp_mail_failed', array( $this, 'on_mail_error' ), 10, 1 );
		}

		public function on_mail_error( $wp_error ) {
			if ( is_wp_error( $wp_error ) ) {
				$error_data = $wp_error->get_error_data();
				if (
					isset( $error_data['headers'] ) &&
					$error_data['headers']
				) {
					if (
						isset( $error_data['headers'][CR_Email_Func::CR_MESSAGE_ID] ) &&
						$error_data['headers'][CR_Email_Func::CR_MESSAGE_ID]
					) {
						$this->log_error(
							$error_data['headers'][CR_Email_Func::CR_MESSAGE_ID],
							$wp_error->get_error_message()
						);
					}
				}
			}
		}

		private function log_error( $message_id, $error ) {
			// add information about wp_mail error to the database
			global $wpdb;
			$table = $this->check_create_table();
			if ( $table ) {
				$insert = array(
					'messageId' => $message_id,
					'errorMessage' => (string) $error,
					'date' => gmdate('Y-m-d H:i:s')
				);
				$r = $wpdb->replace( $table, $insert );
			}
		}

		private function check_create_table() {
			// check if wp_mail logs table exists
			global $wpdb;
			$table_name = $wpdb->prefix . self::LOGS_TABLE;
			$name_check = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
			if ( $name_check !== $table_name ) {
				// check if the database converted the table name to lowercase
				$table_name_l = strtolower( $table_name );
				if ( $name_check !== $table_name_l ) {
					if (
						true !== $wpdb->query(
							"CREATE TABLE `$table_name` (
								`id` bigint unsigned NOT NULL AUTO_INCREMENT,
								`messageId` varchar(16) DEFAULT NULL,
								`errorMessage` text DEFAULT NULL,
								`date` datetime DEFAULT NULL,
								PRIMARY KEY (`id`),
								KEY `messageId_index` (`messageId`),
								KEY `date_index` (`date`)
							) CHARACTER SET 'utf8mb4';"
						)
					) {
						// table could not be created
						return '';
					}
				}
			} else {
				$table_name = $name_check;
			}
			//
			return $table_name;
		}

		public static function get_error( $message_id ) {
			// get an error message by the email id
			global $wpdb;
			$table_name = $wpdb->prefix . self::LOGS_TABLE;
			$select_q = "SELECT * FROM `$table_name` WHERE `messageId` = '$message_id'";
			$records = $wpdb->get_results(
				$select_q,
				ARRAY_A
			);
			if ( is_array( $records ) ) {
				$cnt = count( $records );
				if ( 0 < $cnt ) {
					if ( isset( $records[$cnt - 1]['errorMessage'] ) ) {
						return $records[$cnt - 1]['errorMessage'];
					}
				}
			}
			return '';
		}

	}

endif;
