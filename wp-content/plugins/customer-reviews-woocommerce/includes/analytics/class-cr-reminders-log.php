<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Reminders_Log' ) ) :

class CR_Reminders_Log {
	const LOGS_TABLE = 'cr_reminders_log';
	private $logs_tbl_name = '';

	public function __construct() {
	}

	public function check_create_table() {
		// check if the reminders logs table exists
		global $wpdb;
		$table_name = $wpdb->prefix . self::LOGS_TABLE;
		$name_check = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
		if ( $name_check !== $table_name ) {
			// check if the database converted the table name to lowercase
			$table_name_l = strtolower( $table_name );
			if ( $name_check !== $table_name_l ) {
				if ( true !== $wpdb->query(
						"CREATE TABLE `$table_name` (
							`id` bigint unsigned NOT NULL AUTO_INCREMENT,
							`extId` varchar(16) DEFAULT NULL,
							`orderId` varchar(190) DEFAULT NULL,
							`customerEmail` varchar(1024) DEFAULT NULL,
							`customerName` varchar(1024) DEFAULT NULL,
							`status` varchar(20) DEFAULT NULL,
							`verification` varchar(20) DEFAULT NULL,
							`channel` varchar(20) DEFAULT NULL,
							`type` varchar(20) DEFAULT NULL,
							`dateCreated` datetime DEFAULT NULL,
							`dateSent` datetime DEFAULT NULL,
							`dateEmailOpened` datetime DEFAULT NULL,
							`dateFormOpened` datetime DEFAULT NULL,
							`dateReviewPosted` datetime DEFAULT NULL,
							`language` varchar(10) DEFAULT NULL,
							`reminder` json DEFAULT NULL,
							PRIMARY KEY (`id`),
							KEY `extId_index` (`extId`),
							KEY `orderId_index` (`orderId`),
							KEY `customerEmail_index` (`customerEmail`),
							KEY `dateCreated_index` (`dateCreated`),
							KEY `dateSent_index` (`dateSent`)
						) CHARACTER SET 'utf8mb4';" ) ) {
					// it is possible that Maria DB is used that does not support JSON type
					if( true !== $wpdb->query(
							"CREATE TABLE `$table_name` (
								`id` bigint unsigned NOT NULL AUTO_INCREMENT,
								`extId` varchar(16) DEFAULT NULL,
								`orderId` varchar(190) DEFAULT NULL,
								`customerEmail` varchar(1024) DEFAULT NULL,
								`customerName` varchar(1024) DEFAULT NULL,
								`status` varchar(20) DEFAULT NULL,
								`verification` varchar(20) DEFAULT NULL,
								`channel` varchar(20) DEFAULT NULL,
								`type` varchar(20) DEFAULT NULL,
								`dateCreated` datetime DEFAULT NULL,
								`dateSent` datetime DEFAULT NULL,
								`dateEmailOpened` datetime DEFAULT NULL,
								`dateFormOpened` datetime DEFAULT NULL,
								`dateReviewPosted` datetime DEFAULT NULL,
								`language` varchar(10) DEFAULT NULL,
								`reminder` text DEFAULT NULL,
								PRIMARY KEY (`id`),
								KEY `extId_index` (`extId`),
								KEY `orderId_index` (`orderId`),
								KEY `customerEmail_index` (`customerEmail`),
								KEY `dateCreated_index` (`dateCreated`),
								KEY `dateSent_index` (`dateSent`)
							) CHARACTER SET 'utf8mb4';" ) ) {
						return array( 'code' => 1, 'text' => 'Table ' . $table_name . ' could not be created' );
					}
				}
			} else {
				$table_name = $name_check;
			}
		}
		// add 'extId' column if it doesn't exist
		if( ! $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `$table_name` LIKE %s", 'extId' ) ) ) {
			if ( $wpdb->query( "ALTER TABLE `$table_name` ADD `extId` varchar(16) DEFAULT NULL AFTER `id`;" ) ) {
				$wpdb->query( "ALTER TABLE `$table_name` ADD INDEX `extId_index` (`extId`);" );
			}
		}
		//
		$this->logs_tbl_name = $table_name;
		return 0;
	}

	public function add( $order_id, $type, $channel, $result ) {
		global $wpdb;
		if ( 0 === $this->check_create_table() ) {
			$customerEmail = '';
			$customerName = '';
			$status = '';
			$verification = '';
			$dateCreated = gmdate('Y-m-d H:i:s');
			$dateSent = gmdate('Y-m-d H:i:s');
			$language = '';
			$reminder = array();
			$extId = NULL;
			if (
				isset( $result[2] ) &&
				isset( $result[2]['data'] )
			) {
				$data = $result[2]['data'];
				if (
					isset( $data['email'] ) &&
					isset( $data['email']['to'] )
				) {
					$customerEmail = $data['email']['to'];
				}
				if ( isset( $data['customer'] ) ) {
					$customerName = $data['customer']['firstname'] . ' ' . $data['customer']['lastname'];
				}
				if ( isset( $data['verification'] ) ) {
					$verification = $data['verification'];
				}
				if ( isset( $data['language'] ) ) {
					$language = $data['language'];
				}
			}
			if (
				isset( $result[2] ) &&
				isset( $result[2]['email_id'] )
			) {
				$extId = $result[2]['email_id'];
			}
			if ( isset( $result[0] ) ) {
				if ( 0 === $result[0] ) {
					$status = 'sent';
				} elseif ( 200 === $result[0] ) {
					$status = 'canceled';
					$reminder['errorDetails'] = $result[1];
				} else {
					$status = 'error';
					$reminder['errorDetails'] = $result[1];
				}
			}

			$insert = array(
				'extId' => $extId,
				'orderId' => $order_id,
				'customerEmail' => $customerEmail,
				'customerName' => $customerName,
				'status' => $status,
				'verification' => $verification,
				'channel' => $channel,
				'type' => $type,
				'dateCreated' => $dateCreated,
				'dateSent' => $dateSent,
				'dateEmailOpened' => NULL,
				'dateFormOpened' => NULL,
				'dateReviewPosted' => NULL,
				'language' => $language,
				'reminder' => json_encode( $reminder )
			);
			$r = $wpdb->replace( $this->logs_tbl_name, $insert );
			if( false !== $r ) {
				return array( 'code' => 0, 'text' => '' );
			} else {
				return array( 'code' => 1, 'text' => 'Review Reminder could not be saved in the log. Error: ' . $wpdb->last_error );
			}
		}
	}

	public function get( $start, $per_page, $orderby, $order, $search, $status ) {
		$order = strtoupper( $order );
		$order = ( $order === 'DESC' ) ? $order : 'ASC';

		switch ($orderby) {
			case 'order':
				$orderby = 'orderId';
				break;
			case 'customer':
				$orderby = 'customerName';
				break;
			case 'sent':
				$orderby = 'dateSent';
				break;
			default:
				$orderby = 'dateSent';
				break;
		}

		switch ($status) {
			case 'rmd_canceled':
				$status = 'canceled';
				break;
			case 'rmd_error':
				$status = 'error';
				break;
			case 'rmd_sent':
				$status = 'sent';
				break;
			case 'rmd_opened':
				$status = 'rmd_opened';
				break;
			case 'frm_opened':
				$status = 'frm_opened';
				break;
			default:
				$status = '';
				break;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . self::LOGS_TABLE;
		if ( $search ) {
			if ( $status ) {
				$select_q = "SELECT * FROM `$table_name` WHERE `status` = '$status' AND ( `customerName` LIKE '%$search%' OR `customerEmail` LIKE '%$search%' OR `orderId` LIKE '%$search%' ) ORDER BY `$orderby` $order LIMIT $start, $per_page";
				$select_t = "SELECT COUNT(*) FROM `$table_name` WHERE `status` = '$status' AND ( `customerName` LIKE '%$search%' OR `customerEmail` LIKE '%$search%' OR `orderId` LIKE '%$search%' ) ORDER BY `$orderby` $order";
			} else {
				$select_q = "SELECT * FROM `$table_name` WHERE `customerName` LIKE '%$search%' OR `customerEmail` LIKE '%$search%' OR `orderId` LIKE '%$search%' ORDER BY `$orderby` $order LIMIT $start, $per_page";
				$select_t = "SELECT COUNT(*) FROM `$table_name` WHERE `customerName` LIKE '%$search%' OR `customerEmail` LIKE '%$search%' OR `orderId` LIKE '%$search%' ORDER BY `$orderby` $order";
			}
		} else {
			if ( $status ) {
				$select_q = "SELECT * FROM `$table_name` WHERE `status` = '$status' ORDER BY `$orderby` $order LIMIT $start, $per_page";
				$select_t = "SELECT COUNT(*) FROM `$table_name` WHERE `status` = '$status' ORDER BY `$orderby` $order";
			} else {
				$select_q = "SELECT * FROM `$table_name` ORDER BY `$orderby` $order LIMIT $start, $per_page";
				$select_t = "SELECT COUNT(*) FROM `$table_name` ORDER BY `$orderby` $order";
			}
		}
		$records = $wpdb->get_results(
			$select_q,
			ARRAY_A
		);

		$total = $wpdb->get_var( $select_t );
		if ( ! $total ) {
			$total = 0;
		}

		if ( is_array( $records ) ) {
			return array(
				'records' => $records,
				'total' => intval( $total )
			);
		} else {
			return array(
				'records' => array(),
				'total' => 0
			);
		}
	}

	public function delete( $reminders ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::LOGS_TABLE;
		$ids = implode( ',', array_map( 'absint', $reminders ) );
		$wpdb->query( "DELETE FROM `$table_name` WHERE id IN($ids)" );
	}

	public function email_opened( $extId ) {
		global $wpdb;
		if ( 0 === $this->check_create_table() ) {
			$extId = sanitize_text_field( $extId );

			// get the current status of the reminder
			$records = $wpdb->get_results(
				"SELECT * FROM `$this->logs_tbl_name` WHERE `extId` = '$extId';",
				ARRAY_A
			);

			if ( is_array( $records ) && 0 < count( $records ) ) {
				if ( in_array( $records[0]['status'], array( 'sent', 'error' ) )  ) {
					// update 'status' and 'dateEmailOpened' columns
					$update_result = $wpdb->update(
						$this->logs_tbl_name,
						array(
							'status' => 'rmd_opened',
							'dateEmailOpened' => gmdate('Y-m-d H:i:s')
						),
						array( 'extId' => $extId )
					);
				}
			}
		}
	}

	public static function count_reminders( $search ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::LOGS_TABLE;
		if ( $search ) {
			$select = "SELECT status, COUNT(*) AS total FROM `$table_name` WHERE `customerName` LIKE '%$search%' OR `customerEmail` LIKE '%$search%' OR `orderId` LIKE '%$search%' GROUP BY `status`";
		} else {
			$select = "SELECT status, COUNT(*) AS total FROM `$table_name` GROUP BY `status`";
		}
		$totals = $wpdb->get_results(
			$select,
			ARRAY_A
		);
		if ( is_array( $totals ) ) {
			return $totals;
		} else {
			return array();
		}
	}

	public function form_opened( $extId ) {
		global $wpdb;
		if ( 0 === $this->check_create_table() ) {
			$extId = sanitize_text_field( $extId );
			if ( $extId ) {
				// get the current status of the reminder
				$records = $wpdb->get_results(
					"SELECT * FROM `$this->logs_tbl_name` WHERE `extId` = '$extId';",
					ARRAY_A
				);

				if ( is_array( $records ) && 0 < count( $records ) ) {
					if ( in_array( $records[0]['status'], array( 'sent', 'error', 'rmd_opened' ) )  ) {
						// update 'status' and 'dateFormOpened' columns
						$update_result = $wpdb->update(
							$this->logs_tbl_name,
							array(
								'status' => 'frm_opened',
								'dateFormOpened' => gmdate('Y-m-d H:i:s')
							),
							array( 'extId' => $extId )
						);
					}
				}
			}
		}
	}

	public function get_details( $id ) {
		global $wpdb;
		$id = intval( $id );
		if ( $id ) {
			$table_name = $wpdb->prefix . self::LOGS_TABLE;
			$record = $wpdb->get_row(
				"SELECT * FROM `$table_name` WHERE `id` = '$id';",
				ARRAY_A
			);
			if ( is_array( $record ) ) {
				return $record;
			}
		}
		return false;
	}

	public static function get_status_description( $status ) {
		$description = '';
		switch ($status) {
			case 'sent':
				$description = __( 'Sent', 'customer-reviews-woocommerce' );
				break;
			case 'error':
				$description = __( 'Error', 'customer-reviews-woocommerce' );
				break;
			case 'rmd_opened':
				$description = __( 'Reminder Opened', 'customer-reviews-woocommerce' );
				break;
			case 'frm_opened':
				$description = __( 'Form Opened', 'customer-reviews-woocommerce' );
				break;
			case 'canceled':
				$description = __( 'Canceled', 'customer-reviews-woocommerce' );
				break;
			default:
				break;
		}
		return $description;
	}

	public static function get_verification_description( $verification ) {
		$description = '';
		switch ($verification) {
			case 'verified':
				$description = __( 'Yes', 'customer-reviews-woocommerce' );
				break;
			case 'local':
				$description = __( 'No', 'customer-reviews-woocommerce' );
				break;
			default:
				break;
		}
		return $description;
	}

	public static function get_channel_description( $channel ) {
		$description = '';
		switch ($channel) {
			case 'email':
				$description = __( 'Email', 'customer-reviews-woocommerce' );
				break;
			case 'wa':
				$description = __( 'WhatsApp', 'customer-reviews-woocommerce' );
				break;
			default:
				break;
		}
		return $description;
	}

	public static function get_type_description( $type ) {
		$description = '';
		switch ($type) {
			case 'm':
				$description = __( 'Manual', 'customer-reviews-woocommerce' );
				break;
			case 'a':
				$description = __( 'Automatic', 'customer-reviews-woocommerce' );
				break;
			default:
				break;
		}
		return apply_filters(
			'cr_reminders_log_type_desc',
			$description,
			$type
		);
	}

}

endif;
