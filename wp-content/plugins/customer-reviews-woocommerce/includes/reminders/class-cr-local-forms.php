<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Local_Forms' ) ) :

	class CR_Local_Forms {
		private $form_id;
		private $items;
		private $customer_email;
		private $customer_name;
		private $display_name;
		private $form_header;
		private $form_body;
		private $cr_form_color1;
		private $cr_form_color2;
		private $cr_form_color3;
		private $language;
		private $extra;

		const HEADER_TEMPLATE = 'form-header.php';
		const ITEM_BLOCK_TEMPLATE = 'form-block-item.php';
		const CUSTOMER_TEMPLATE = 'form-customer.php';
		const FOOTER_TEMPLATE = 'form-footer.php';
		const FORMS_TABLE = 'cr_local_forms';
		const FORMS_SLUG = 'cusrev';
		const TEST_FORM = 'test';
		const PIXEL_SLUG = 'cusrev/pix';
		const PIXEL_DIV = '<div style="line-height:0;"><img src="%s" style="padding:0;margin:0;height:1px;width:1px;line-height:0;"/></div>';

		public function __construct( $id, $email_id ) {
			$this->form_id = $id;

			global $wpdb;
			$table_name = $wpdb->prefix . self::FORMS_TABLE;
			$record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table_name` WHERE `formId` = %s", $this->form_id ) );
			if( null !== $record ) {
				$this->customer_email = $record->customerEmail;
				$this->customer_name = trim( $record->customerName );
				$this->display_name = $record->displayName;
				$this->form_header = $record->formHeader;
				$this->form_body = $record->formBody;
				$this->items = json_decode( $record->items );
				$this->cr_form_color1 = get_option( 'ivole_form_color_bg', '#2C5E66' );
				$this->cr_form_color2 = get_option( 'ivole_form_color_text', '#FFFFFF' );
				$this->cr_form_color3 = get_option( 'ivole_form_color_el', '#1AB394' );
				if( property_exists( $record, 'language' ) ) {
					$this->language = $record->language;
				} else {
					$this->language = '';
				}
				if( property_exists( $record, 'extra' ) ) {
					$this->extra = $record->extra;
				} else {
					$this->extra = '';
				}
				// delete media files uploaded with test reviews
				if( self::TEST_FORM === $this->form_id ) {
					foreach( $this->items as $key => $item ) {
						if( property_exists( $item, 'media' ) ) {
							if( $item->media && is_array( $item->media ) ) {
								foreach( $item->media as $media ) {
									$attachmentId = intval( $media );
									if( 'attachment' === get_post_type( $attachmentId ) ) {
										wp_delete_attachment( $attachmentId, true );
									}
								}
							}
							$this->items[$key]->media = [];
						}
					}
					$db_items = json_encode( $this->items );
					$update_result = $wpdb->update( $wpdb->prefix . self::FORMS_TABLE, array(
						'items' => $db_items
					), array( 'formId' => $this->form_id ) );
				}
				// log a view of the form
				$log = new CR_Reminders_Log();
				$log->form_opened( $email_id );
			} else {
				$this->form_id = 0;
			}
		}

		public function output() {
			if( is_array( $this->items ) && 0 < count( $this->items ) ) {
				$filtered_output = apply_filters( 'cr_local_form_output', '', array(
					'cr_form_header' => $this->form_header,
					'cr_form_extra' => $this->extra
				) );
				if( $filtered_output ) {
					echo $filtered_output;
					return;
				}
				if( $this->language ) {
					// WPML integration
					if ( has_filter( 'wpml_translate_single_string' ) ) {
						do_action( 'wpml_switch_language', strtolower( $this->language ) );
						load_plugin_textdomain( 'customer-reviews-woocommerce' );
					}
					// TranslatePress integration
					global $TRP_LANGUAGE;
					if( $TRP_LANGUAGE ) {
						$languages = get_available_languages();
						$order_lang = strtolower( $this->language );
						$full_lang_codes = array_filter( $languages, function( $value ) use( $order_lang ) {
							return strpos( strtolower( $value ), $order_lang ) !== false;
						} );
						if( 0 < count( $full_lang_codes ) ) {
							$full_lang_codes = array_values( $full_lang_codes );
							$TRP_LANGUAGE = $full_lang_codes[0];
						}
					}
				}
				$this->form_header();
				foreach( $this->items as $item ) {
					$this->form_block( $item );
				}
				$this->customer_block();
				$this->form_footer();
			}
		}

		private function form_header() {
			$template = wc_locate_template(
				self::HEADER_TEMPLATE,
				'customer-reviews-woocommerce',
				__DIR__ . '/../../templates/'
			);
			$output = '';
			$cr_form_css = plugins_url( '/css/form.css', dirname( dirname( __FILE__ ) ) ) . '?ver=' . Ivole::CR_VERSION;
			$cr_form_js = plugins_url( '/js/form.js', dirname( dirname( __FILE__ ) ) ) . '?ver=' . Ivole::CR_VERSION;
			$cr_form_id = $this->form_id;
			$cr_form_header = $this->form_header;
			$cr_form_desc = $this->form_body;
			$cr_form_required = __( '* Required', 'customer-reviews-woocommerce' );
			$cr_form_ajax = admin_url( 'admin-ajax.php' );
			$cr_form_subm_header = __( 'Thank you for submitting a review!', 'customer-reviews-woocommerce' );
			$cr_form_subm_desc = __( 'Your response has been recorded.', 'customer-reviews-woocommerce' );
			$cr_form_edit_label = __( 'Edit your review', 'customer-reviews-woocommerce' );
			$cr_form_edit = $this->display_name ? true : false;
			$cr_form_color1 = $this->cr_form_color1;
			$cr_form_color2 = $this->cr_form_color2;
			$cr_form_color3 = $this->cr_form_color3;
			$cr_form_media_upload_limit = get_option( 'ivole_attach_image_quantity', 5 );
			$attach_image_size = get_option( 'ivole_attach_image_size', 25 );
			$cr_form_media_upload_max_size = 1024 * 1024 * $attach_image_size;
			$cr_form_error_max_file_size = sprintf( __( 'The file cannot be uploaded because its size exceeds the limit of %d MB', 'customer-reviews-woocommerce' ), $attach_image_size );
			$cr_form_error_file_type = __( 'Error: accepted file types are PNG, JPG, JPEG, GIF, MP4, MPEG, OGG, WEBM, MOV, AVI', 'customer-reviews-woocommerce' );
			ob_start();
			include( $template );
			$output = ob_get_clean();
			echo $output;
		}

		private function form_block( $item ) {
			$template = wc_locate_template(
				self::ITEM_BLOCK_TEMPLATE,
				'customer-reviews-woocommerce',
				__DIR__ . '/../../templates/'
			);
			$output = '';

			$cr_form_item_name = $item->name;
			$cr_form_item_comment_name = __( 'Comment', 'customer-reviews-woocommerce' );
			$cr_form_item_comment_placeholder = __( 'Your comment', 'customer-reviews-woocommerce' );
			$cr_form_item_id = $item->id;
			$cr_form_item_media_array = array();
			if( -1 === $item->id ) {
				// special case - store item
				$cr_form_item_rating_name = __( 'Rate website, customer service and delivery', 'customer-reviews-woocommerce' );
				$cr_form_item_image = '';
				$cr_form_item_price = '';
				$cr_form_media_enabled = false;
			} else {
				// otherwise product item
				$cr_form_item_rating_name = __( 'Rating', 'customer-reviews-woocommerce' );
				$cr_form_item_image = $item->image;
				if ( isset( $item->pricePerItem ) ) {
					$cr_form_item_price = CR_Email_Func::cr_price( $item->pricePerItem );
				} else {
					$cr_form_item_price = CR_Email_Func::cr_price( $item->price );
				}
				$cr_form_media_enabled = ( 'yes' === get_option( 'ivole_form_attach_media', 'no' ) ? true : false );
				if( property_exists( $item, 'media' ) ) {
					if( is_array(  $item->media ) ) {
						foreach( $item->media as $m_item ) {
							if( false !== get_post_type( $m_item ) ) {
								$cr_form_item_media_array[] = array(
									'url' => wp_get_attachment_url( $m_item ),
									'id' => (int) $m_item,
									'key' => get_post_meta( $m_item, 'cr-upload-temp-key', true )
								);
							}
						}
					}
				}
			}
			$cr_form_item_comment_req = 'yes' === get_option( 'ivole_form_comment_required', 'no' ) ? true : false;
			$cr_form_rating = ( isset( $item->rating ) && ( 1 <= $item->rating && 5 >= $item->rating ) ) ? intval( $item->rating ) : 0;
			$cr_form_comment = isset( $item->comment ) ? $item->comment : '';
			$cr_form_item_media_name = __( 'Upload Photos/Video', 'customer-reviews-woocommerce' );
			$cr_form_item_media_desc = __( 'Add photos or video to your review', 'customer-reviews-woocommerce' );
			$cr_form_media_upload_limit = get_option( 'ivole_attach_image_quantity', 5 );
			ob_start();
			include( $template );
			$output = ob_get_clean();
			echo $output;
		}

		private function customer_block() {
			$template = wc_locate_template(
				self::CUSTOMER_TEMPLATE,
				'customer-reviews-woocommerce',
				__DIR__ . '/../../templates/'
			);
			$output = '';
			$cr_form_cust_title = __( 'Display name', 'customer-reviews-woocommerce' );
			$cr_form_cust_anonymous = __( 'Anonymous', 'customer-reviews-woocommerce' );
			$cr_form_cust_name = $this->customer_name;
			$cr_form_cust_preview_name = $this->display_name ? $this->display_name : $this->customer_name;
			$cr_form_cust_preview_name = $cr_form_cust_preview_name ? $cr_form_cust_preview_name : $cr_form_cust_anonymous;
			$cr_form_cust_name_w_dot = '';
			$cr_form_cust_f_name = '';
			if( strpos( $this->customer_name, ' ' ) !== false ) {
				$parts = explode( ' ', $this->customer_name );
				if( count( $parts ) > 1 ) {
					$lastname  = array_pop( $parts );
					$firstname = $parts[0];
					$cr_form_cust_name_w_dot = $firstname . ' ' . mb_substr( $lastname, 0, 1 ) . '.';
					$cr_form_cust_f_name = $firstname;
				}
			}
			$wc_terms_page = wc_get_page_id( 'terms' );
			if( $wc_terms_page ) {
				$wc_terms_page = get_permalink( $wc_terms_page );
			} else {
				$wc_terms_page = '';
			}
			$cr_form_terms = sprintf( __( 'By submitting your review, you agree to the <a href="%s" target="_blank" rel="noopener noreferrer">terms and conditions</a>.', 'customer-reviews-woocommerce' ), esc_url( $wc_terms_page ) );
			$cr_form_submit = __( 'Submit', 'customer-reviews-woocommerce' );
			ob_start();
			include( $template );
			$output = ob_get_clean();
			echo $output;
		}

		private function form_footer() {
			$template = wc_locate_template(
				self::FOOTER_TEMPLATE,
				'customer-reviews-woocommerce',
				__DIR__ . '/../../templates/'
			);
			$output = '';
			$home_url = '<a href="' . esc_url( get_home_url() ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>';
			$cusrev_url = '<a href="https://wordpress.org/plugins/customer-reviews-woocommerce/" rel="noindex nofollow">CusRev</a>';
			$cr_form_footer = '';
			if( 'no' === get_option( 'ivole_reviews_nobranding', 'yes' ) ) {
				$cr_form_footer = sprintf( __( 'This form was created by %1$s using %2$s plugin.', 'customer-reviews-woocommerce' ), $home_url, $cusrev_url );
			} else {
				$cr_form_footer = sprintf( __( 'This form was created by %1$s.', 'customer-reviews-woocommerce' ), $home_url );
			}
			ob_start();
			include( $template );
			$output = ob_get_clean();
			echo $output;
		}

		public static function save_form( $orderId, $customer, $header, $body, $items, $is_test, $language, $extra ) {
			// check if the table exists
			global $wpdb;
			$table_name = $wpdb->prefix . self::FORMS_TABLE;
			$name_check = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
			if ( $name_check !== $table_name ) {
				// check if the database converted the table name to lowercase
				$table_name_l = strtolower( $table_name );
				if ( $name_check !== $table_name_l ) {
					if ( true !== $wpdb->query(
							"CREATE TABLE `$table_name` (
								`formId` varchar(190),
								`orderId` varchar(190) DEFAULT NULL,
								`customerEmail` varchar(1024) DEFAULT NULL,
								`customerName` varchar(1024) DEFAULT NULL,
								`displayName` varchar(1024) DEFAULT NULL,
								`formHeader` varchar(1024) DEFAULT NULL,
								`formBody` varchar(1024) DEFAULT NULL,
								`items` json DEFAULT NULL,
								`language` varchar(10) DEFAULT NULL,
								`extra` text DEFAULT NULL,
								PRIMARY KEY (`formId`),
								KEY `orderId_index` (`orderId`)
							) CHARACTER SET 'utf8mb4';" ) ) {
						// it is possible that Maria DB is used that does not support JSON type
						if( true !== $wpdb->query(
								"CREATE TABLE `$table_name` (
									`formId` varchar(190),
									`orderId` varchar(190) DEFAULT NULL,
									`customerEmail` varchar(1024) DEFAULT NULL,
									`customerName` varchar(1024) DEFAULT NULL,
									`displayName` varchar(1024) DEFAULT NULL,
									`formHeader` varchar(1024) DEFAULT NULL,
									`formBody` varchar(1024) DEFAULT NULL,
									`items` text DEFAULT NULL,
									`language` varchar(10) DEFAULT NULL,
									`extra` text DEFAULT NULL,
									PRIMARY KEY (`formId`),
									KEY `orderId_index` (`orderId`)
								) CHARACTER SET 'utf8mb4';" ) ) {
							return array( 'code' => 1, 'text' => 'Table ' . $table_name . ' could not be created' );
						}
					}
				} else {
					$table_name = $name_check;
				}
			}

			// add 'language' and 'extra' columns if they don't exist
			if( ! $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `$table_name` LIKE %s", 'language' ) ) ) {
				$wpdb->query( "ALTER TABLE `$table_name` ADD `language` varchar(10) DEFAULT NULL;" );
			}
			if( ! $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `$table_name` LIKE %s", 'extra' ) ) ) {
				$wpdb->query( "ALTER TABLE `$table_name` ADD `extra` text DEFAULT NULL;" );
			}

			// if store reviews are enabed, add a special item to the items array
			if( 'yes' === get_option( 'ivole_form_shop_rating', 'no' ) ) {
				$store_item = array( 'id' => -1, 'name' => Ivole_Email::get_blogname() );
				array_unshift( $items, $store_item );
			}

			if( $is_test ) {
				$formId = self::TEST_FORM;
			} else {
				// generate unique form id
				$formId = strtolower( uniqid() );
			}

			$insert_args = array(
				'formId' => $formId,
				'orderId' => $orderId,
				'customerEmail' => $customer[ 'email' ],
				'customerName' => trim( $customer[ 'firstname' ] . ' ' . $customer[ 'lastname' ] ),
				'formHeader' => $header,
				'formBody' => $body,
				'items' => json_encode( $items ),
				'language' => $language
			);
			$insert_args = apply_filters(
				'cr_local_form_insert',
				$insert_args,
				array(
					'customer' => $customer,
					'items' => $items,
					'table_name' => $table_name,
					'is_test' => $is_test,
					'extra' => $extra
				)
			);
			// insert data
			$res = $wpdb->replace( $table_name, $insert_args );
			if( false !== $res ) {
				return array( 'code' => 0, 'text' => get_home_url() . '/' . self::FORMS_SLUG . '/' . $formId . '/' );
			} else {
				return array( 'code' => 2, 'text' => 'Form \'' . $formId . '\' could not be saved to the table \'' . $table_name . '\'. Error: ' . $wpdb->last_error );
			}
		}

		public static function test_form_for_preview( $template ) {
			return self::save_form(
				'12345', // orderId
				array(
					'firstname' => __( 'Jane', 'customer-reviews-woocommerce' ),
					'lastname' => __( 'Doe', 'customer-reviews-woocommerce' ),
					'email' => '',
				), // customer
				property_exists( $template, 'title' ) ? $template->title : '', // header
				'', // body
				array(
					array( 'id' => 1,
						'name' => __( 'Item 1 Test', 'customer-reviews-woocommerce' ),
						'price' => 15,
						'image' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/test-product-1.jpeg'
					),
					array( 'id' => 2,
						'name' => __( 'Item 2 Test', 'customer-reviews-woocommerce' ),
						'price' => 150,
						'image' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/test-product-2.jpeg'
					)
				), // items
				true, // is_test
				property_exists( $template, 'language' ) ? $template->language : 'EN', // language
				$template // extra
			);
		}

		public static function render_pixel( $pixel ) {
			$log = new CR_Reminders_Log();
			$log->email_opened( $pixel );
			header("Content-type: image/png");
			// needed to avoid cache time on browser side
			header("Cache-Control: private, no-cache, no-cache=Set-Cookie, proxy-revalidate");
			header("Pragma: no-cache");
			echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAAXNSR0IArs4c6QAAAAtJREFUGFdjYAACAAAFAAGq1chRAAAAAElFTkSuQmCC');
		}

	}

endif;
