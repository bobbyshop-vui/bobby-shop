<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Custom_Questions' ) ) :

	class CR_Custom_Questions {
		private $questions = array();
		public static $meta_id = 'ivole_c_questions';
		public static $onsite_prefix = 'cr_onsite_';
		public static $type_label_prefix = 'cr_typ_lab_';

		public function __construct() {
		}

		public function parse_shop_questions( $order ) {
			if( isset( $order->shop_questions ) && is_array( $order->shop_questions ) ) {
				$this->parse_questions( $order->shop_questions );
			}
		}

		public function parse_product_questions( $item ) {
			if( isset( $item->item_questions ) && is_array( $item->item_questions ) ) {
				$this->parse_questions( $item->item_questions );
			}
		}

		public function parse_questions( $input ) {
			$num_questions = count( $input );
			for( $i = 0; $i < $num_questions; $i++ ) {
				if( $input[$i]->type ) {
					switch( $input[$i]->type ) {
						case 'radio':
							if( isset( $input[$i]->title ) && isset( $input[$i]->value ) ) {
								$question = new CR_Custom_Question();
								$question->type = 'radio';
								$question->title = sanitize_text_field( $input[$i]->title );
								$question->value = sanitize_text_field( $input[$i]->value );
								$this->questions[] = $question;
							}
							break;
						case 'checkbox':
							if( isset( $input[$i]->title ) &&
									isset( $input[$i]->value ) && is_array( $input[$i]->value ) ) {
										$question = new CR_Custom_Question();
										$question->type = 'checkbox';
										$question->title = sanitize_text_field( $input[$i]->title );
										$count_values = count( $input[$i]->value );
										for( $j = 0; $j < $count_values; $j++ ) {
											$question->values[] = sanitize_text_field( $input[$i]->value[$j] );
										}
										$this->questions[] = $question;
									}
							break;
						case 'rating':
							if( isset( $input[$i]->title ) && isset( $input[$i]->value ) ) {
								$question = new CR_Custom_Question();
								$question->type = 'rating';
								$question->title = sanitize_text_field( $input[$i]->title );
								$question->value = intval( $input[$i]->value );
								$this->questions[] = $question;
							}
							break;
						case 'comment':
							if( isset( $input[$i]->title ) && isset( $input[$i]->value ) ) {
								$question = new CR_Custom_Question();
								$question->type = 'comment';
								$question->title = sanitize_text_field( $input[$i]->title );
								$question->value = sanitize_text_field( $input[$i]->value );
								$this->questions[] = $question;
							}
							break;
						default:
							break;
					}
				}
			}
		}

		public function has_questions() {
			if( count( $this->questions ) > 0 ) {
				return true;
			} else {
				return false;
			}
		}

		public function save_questions( $review_id ) {
			if( count( $this->questions ) > 0 ) {
				update_comment_meta( $review_id, self::$meta_id, $this->questions );
			}
		}

		public function read_questions( $review_id ) {
			$meta = get_comment_meta( $review_id, self::$meta_id, true );
			if( $meta && is_array( $meta ) ) {
				$count_meta = count( $meta );
				for( $i = 0; $i < $count_meta; $i++ ) {
					if( $meta[$i] instanceof CR_Custom_Question ) {
						$this->questions[] = $meta[$i];
					}
				}
			}
		}

		public function get_questions( $f, $hr ) {
			$fr = '';
			if( $f ) {
				$fr = 'f';
			}
			$count_questions = count( $this->questions );
			$output = '';
			for( $i = 0; $i < $count_questions; $i++ ) {
				if (
					isset( $this->questions[$i]->type ) &&
					isset( $this->questions[$i]->title )
				) {
					$title = ( isset( $this->questions[$i]->label ) && $this->questions[$i]->label ) ? $this->questions[$i]->label : $this->questions[$i]->title;
					switch( $this->questions[$i]->type ) {
						case 'checkbox':
							if (
								isset( $this->questions[$i]->values ) &&
								is_array( $this->questions[$i]->values )
							) {
								$count_values = count( $this->questions[$i]->values );
								$output_temp = '';
								for( $j = 0; $j < $count_values; $j++ ) {
									$output_temp .= '<li>' . $this->questions[$i]->values[$j] . '</li>';
								}
								if( $count_values > 0 ) {
									if ( 2 === $f ) {
										// slider layout
										$output .= '<div class="cr-sldr-custom-question cr-sldr-checkbox">';
										$output .= '<p class="cr-sldr-p"><span class="cr-sldr-label">' . $title . '</span> :</p>';
										$output .= '<ul class="iv' . $fr . '-custom-question-ul">' . $output_temp . '</ul>';
										$output .= '</div>';
									} else {
										$output .= '<p class="iv' . $fr . '-custom-question-checkbox">' . $this->questions[$i]->title . ' : </p>';
										$output .= '<ul class="iv' . $fr . '-custom-question-ul">' . $output_temp . '</ul>';
									}
								}
							}
							break;
						case 'rating':
							if( isset( $this->questions[$i]->value ) ) {
								if( $this->questions[$i]->value > 0 ) {
									if( $f ) {
										if ( 2 === $f ) {
											// slider layout
											$output .= '<div class="cr-sldr-custom-question"><div class="rating">';
											$output .= '<div class="crstar-rating">';
											$output .= '<span style="width:' . esc_attr( ( $this->questions[$i]->value / 5 ) * 100 ) . '%;"></span>';
											$output .= '</div></div>';
											$output .= '<div class="cr' . $fr . '-custom-question-rating">' . $title . '</div></div>';
										} else {
											// list layout
											$output .= '<div class="cr' . $fr . '-custom-question-rating-cont"><div class="cr' . $fr . '-custom-question-rating">' . $title . ' :</div>';
											$output .= wc_get_rating_html( $this->questions[$i]->value ) . '</div>';
										}
									} else {
										$output .= '<div class="cr' . $fr . '-custom-question-rating-cont"><span class="cr' . $fr . '-custom-question-rating">' . $title . ' :</span>';
										$output .= '<span class="iv' . $fr . '-star-rating">';
										for ( $j = 1; $j < 6; $j++ ) {
											$class = ( $j <= $this->questions[$i]->value ) ? 'filled' : 'empty';
											$output .= '<span class="dashicons dashicons-star-' . $class . '"></span>';
										}
										$output .= '</span></div>';
									}
								}
							}
							break;
						case 'radio':
						case 'comment':
						case 'number':
						case 'text':
							if( isset( $this->questions[$i]->value ) && $this->questions[$i]->value ) {
								if ( 2 === $f ) {
									// slider layout
									$output .= '<div class="cr-sldr-custom-question">';
									$output .= '<p class="cr-sldr-p"><span class="cr-sldr-label">' . $title . '</span> ' . $this->questions[$i]->value . '</p>';
									$output .= '</div>';
								} else {
									$output .= '<p class="iv' . $fr . '-custom-question-p"><span class="iv' . $fr . '-custom-question-radio">' . $title .
										'</span> ' . $this->questions[$i]->value . '</p>';
								}
							}
							break;
						default:
							break;
					}
				}
			}
			if( strlen( $output ) > 0 ) {
				if( $f ) {
					if ( 2 === $f ) {
						// do not add <hr>
					} else {
						$output = '<hr class="iv' . $fr . '-custom-question-hr">' . $output . '<hr class="iv' . $fr . '-custom-question-hr">';
					}
				} else {
					if( $hr ) {
						$output = '<hr class="iv' . $fr . '-custom-question-hr">' . $output;
					}
				}
			}
			return $output;
		}

		public function output_questions( $f = false, $hr = true ) {
			$qs = $this->get_questions( $f, $hr );
			if ( $qs ) {
				echo apply_filters( 'cr_custom_questions', $qs );
			}
		}

		public function delete_questions( $review_id ) {
			delete_comment_meta( $review_id, self::$meta_id );
		}

		public static function review_form_questions( $comment_form ) {
			$onsite_form = CR_Forms_Settings::get_default_form_settings();
			$rs = '';
			$qs = '';
			if (
				$onsite_form &&
				is_array( $onsite_form )
			) {
				$hash = random_int( 0, 99 ) . '_';
				$shared_index = 0;
				// if there are any custom ratings, display them
				if (
					isset( $onsite_form['rtn_crta'] ) &&
					is_array( $onsite_form['rtn_crta'] )
				) {
					$index = 0;
					$max_rtns = CR_Forms_Settings_Rating::get_max_rating_criteria();
					foreach ( $onsite_form['rtn_crta'] as $r ) {
						if ( $index >= $max_rtns ) {
							break;
						}
						if (
							isset( $r['rating'] ) &&
							$r['rating']
						) {
							$required = false;
							if ( isset( $r['required'] ) && $r['required'] ) {
								$required = true;
							}
							$label = ( isset( $r['label'] ) && $r['label'] ? $r['label'] : $r['rating'] );
							$rating = array(
								'title' => $r['rating'],
								'type' => 'rating',
								'label' => $label
							);
							$rs .= self::display_rating(
								$r['rating'],
								$required,
								$rating,
								false,
								$hash,
								$shared_index
							);
							$index++;
							$shared_index++;
						}
					}
				}
				if ( $rs ) {
					$comment_form = self::display_rating(
						__( 'Overall rating', 'customer-reviews-woocommerce' ),
						true,
						array(
							'type' => 'ovrl'
						),
						true,
						$hash,
						'ovrl'
					);
					$comment_form .= '<div class="cr-onsite-ratings">' . $rs . '</div>';
				} else {
					$comment_form = str_replace( 'cr-review-form-rating-overall', 'cr-review-form-rating-overall cr-review-form-rating-ovonly', $comment_form );
				}
				// if there are any custom questions, display them
				if (
					isset( $onsite_form['cus_atts'] ) &&
					is_array( $onsite_form['cus_atts'] )
				) {
					$index = 0;
					$max_atts = CR_Forms_Settings::get_max_cus_atts();
					$answer_required = __( '* Answer is required', 'customer-reviews-woocommerce' );
					foreach ( $onsite_form['cus_atts'] as $q ) {
						if ( $index >= $max_atts ) {
							break;
						}
						if (
							isset( $q['attribute'] ) &&
							$q['attribute']
						) {
							$required = '';
							$required_class = '';
							if ( isset( $q['required'] ) && $q['required'] ) {
								$required = '<span class="required">*</span>';
								$required_class = ' cr-review-form-que-req';
							}
							$label = ( isset( $q['label'] ) && $q['label'] ? $q['label'] : $q['attribute'] );
							$type_label = array(
								'title' => $q['attribute'],
								'type' => $q['type'],
								'label' => $label
							);
							switch ( $q['type'] ) {
								case 'text':
									$qs .= '<div class="cr-onsite-question cr-full-width' . $required_class . '">';
									$qs .= '<label for="' . esc_attr( self::$onsite_prefix . $hash . $shared_index ) . '">' . esc_html( $q['attribute'] ) . $required . '</label>';
									$qs .= '<input id="' . esc_attr( self::$onsite_prefix . $hash . $shared_index ) . '" name="' . esc_attr( self::$onsite_prefix . $hash . $shared_index ) . '" type="' . esc_attr( $q['type'] ) . '" class="cr-onsite-question-inp">';
									$qs .= '<input name="' . esc_attr( self::$type_label_prefix . $hash . $shared_index ) . '" type="hidden" value="' . esc_attr( json_encode( $type_label ) ) . '">';
									$qs .= '<div class="cr-review-form-field-error">' . $answer_required . '</div>';
									$qs .= '</div>';
									$index++;
									$shared_index++;
									break;
								case 'number':
									$qs .= '<div class="cr-onsite-question' . $required_class . '">';
									$qs .= '<label for="' . esc_attr( self::$onsite_prefix . $hash . $shared_index ) . '">' . esc_html( $q['attribute'] ) . $required . '</label>';
									$qs .= '<input id="' . esc_attr( self::$onsite_prefix . $hash . $shared_index ) . '" name="' . esc_attr( self::$onsite_prefix . $hash . $shared_index ) . '" type="' . esc_attr( $q['type'] ) . '" class="cr-onsite-question-inp">';
									$qs .= '<input name="' . esc_attr( self::$type_label_prefix . $hash . $shared_index ) . '" type="hidden" value="' . esc_attr( json_encode( $type_label ) ) . '">';
									$qs .= '<div class="cr-review-form-field-error">' . $answer_required . '</div>';
									$qs .= '</div>';
									$index++;
									$shared_index++;
									break;
								default:
									break;
							}
						}
					}
				}
				if ( $qs ) {
					$comment_form .= '<div class="cr-onsite-questions">' . $qs . '</div>';
				} else {
					$comment_form = str_replace( 'cr-onsite-ratings', 'cr-onsite-ratings cr-onsite-ratings-only', $comment_form );
				}
			}
			return $comment_form;
		}

		public static function submit_onsite_questions( $comment_id ) {
			if ( isset( $_POST['onsiteQuestions'] ) ) {
				self::submit_save_onsite_questions( $comment_id, $_POST['onsiteQuestions'] );
			} else {
				self::submit_save_onsite_questions( $comment_id, $_POST );
			}
		}

		public static function submit_save_onsite_questions( $comment_id, $post ) {
			if ( $post && is_array( $post ) ) {
				$onsite_questions = array_filter(
					$post,
					function( $k ) {
						return ( strpos( $k, self::$onsite_prefix ) === 0 );
					},
					ARRAY_FILTER_USE_KEY
				);
				if ( $onsite_questions && is_array( $onsite_questions ) ) {
					$cus_questions = array();
					foreach ( $onsite_questions as $key => $response ) {
						$type_label = self::$type_label_prefix . substr( $key, strlen( self::$onsite_prefix ) );
						if (
							isset( $post[$type_label] ) &&
							$post[$type_label]
						) {
							$type_label = json_decode( stripslashes( $post[$type_label] ), true );
							if (
								$type_label &&
								is_array( $type_label ) &&
								isset( $type_label['title'] ) &&
								isset( $type_label['type'] ) &&
								isset( $type_label['label'] ) &&
								trim( $response )
							) {
								$question = new CR_Custom_Question();
								$question->type = $type_label['type'];
								$question->title = sanitize_text_field( $type_label['title'] );
								$question->label = sanitize_text_field( $type_label['label'] );
								$question->value = sanitize_text_field( $response );
								$cus_questions[] = $question;
							} elseif (
								$type_label &&
								is_array( $type_label ) &&
								isset( $type_label['type'] ) &&
								'ovrl' === $type_label['type']
							) {
								update_comment_meta( $comment_id, 'rating', intval( $response ) );
							}
						}
					}
					if ( $cus_questions ) {
						update_comment_meta( $comment_id, self::$meta_id, $cus_questions );
					}
				}
			}
		}

		public static function display_rating( $label, $required, $rating, $overall, $hash, $index ) {
			ob_start();
			?>
			<div class="cr-review-form-rating">
				<div class="cr-review-form-rating-label">
					<?php
						if ( $label ) {
							echo esc_html( $label );
							if ( $required ) {
								echo '<span class="required">*</span>';
							}
						}
					?>
				</div>
				<div class="cr-review-form-rating-cont<?php if ( $required ) { echo ' cr-review-form-rating-req'; } ?>">
					<?php for( $i = 1; $i <= 5; $i++ ): ?>
						<div class="cr-review-form-rating-inner" data-rating="<?php echo $i; ?>">
							<svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="cr-rating-deact">
								<path d="M10.5131 0.288628C10.7119 -0.0962093 11.288 -0.0962093 11.4868 0.288628L14.4654 6.04249C14.5448 6.19546 14.6976 6.3014 14.8745 6.32573L21.5344 7.24876C21.9799 7.31054 22.1576 7.83281 21.8357 8.132L17.0158 12.611C16.8881 12.7297 16.8295 12.9014 16.86 13.0691L17.9974 19.3935C18.0738 19.8165 17.6081 20.1392 17.2092 19.9392L11.2529 16.9538C11.0946 16.8745 10.9053 16.8745 10.747 16.9538L4.79023 19.9392C4.39182 20.1392 3.92604 19.8165 4.00249 19.3935L5.13988 13.0691C5.17004 12.9014 5.11177 12.7297 4.98365 12.611L0.164665 8.132C-0.157703 7.83281 0.020013 7.31054 0.465542 7.24876L7.12575 6.32573C7.30224 6.3014 7.45552 6.19546 7.5345 6.04249L10.5131 0.288628Z" fill="#DFE4E7"/>
							</svg>
							<svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="cr-rating-act">
								<path d="M10.5131 0.288628C10.7119 -0.0962093 11.288 -0.0962093 11.4868 0.288628L14.4654 6.04249C14.5448 6.19546 14.6976 6.3014 14.8745 6.32573L21.5344 7.24876C21.9799 7.31054 22.1576 7.83281 21.8357 8.132L17.0158 12.611C16.8881 12.7297 16.8295 12.9014 16.86 13.0691L17.9974 19.3935C18.0738 19.8165 17.6081 20.1392 17.2092 19.9392L11.2529 16.9538C11.0946 16.8745 10.9053 16.8745 10.747 16.9538L4.79023 19.9392C4.39182 20.1392 3.92604 19.8165 4.00249 19.3935L5.13988 13.0691C5.17004 12.9014 5.11177 12.7297 4.98365 12.611L0.164665 8.132C-0.157703 7.83281 0.020013 7.31054 0.465542 7.24876L7.12575 6.32573C7.30224 6.3014 7.45552 6.19546 7.5345 6.04249L10.5131 0.288628Z" fill="#F4DB6B"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M7.91797 18.3717L12.328 1.91336L14.4655 6.04248C14.5448 6.19545 14.6977 6.30139 14.8746 6.32572L21.5345 7.24875C21.98 7.31053 22.1577 7.8328 21.8357 8.13199L17.0159 12.611C16.8882 12.7297 16.8295 12.9014 16.8601 13.0691L17.9975 19.3934C18.0739 19.8165 17.6082 20.1392 17.2093 19.9392L11.253 16.9538C11.0947 16.8745 10.9054 16.8745 10.7471 16.9538L7.91797 18.3717Z" fill="#F5CD5B"/>
							</svg>
						</div>
					<?php endfor; ?>
					<div class="cr-review-form-rating-nbr">0/5</div>
				</div>
				<div class="cr-review-form-field-error">
					<?php _e( '* Rating is required', 'customer-reviews-woocommerce' ); ?>
				</div>
				<input class="cr-review-form-rating-inp" name="<?php echo esc_attr( self::$onsite_prefix . $hash . $index ); ?>" type="hidden" value="">
				<input name="<?php echo esc_attr( self::$type_label_prefix . $hash . $index ); ?>" type="hidden" value="<?php echo esc_attr( json_encode( $rating ) ); ?>">
			</div>
			<?php
			$out = ob_get_clean();
			if ( $overall ) {
				$out = '<div class="cr-review-form-rating-overall">' . $out . '</div>';
			}
			return $out;
		}

		// display a rating block on a review form
		public static function review_form_rating( $item_id ) {
			$hash = random_int( 0, 99 ) . '_';
			$out = self::display_rating(
				__( 'Rating', 'customer-reviews-woocommerce' ),
				true,
				array(
					'type' => 'ovrl'
				),
				true,
				$hash,
				'ovrl'
			);
			//
			if ( 0 < $item_id ) {
				$out = self::review_form_questions( $out );
			}
			echo $out;
		}

	}

endif;

if ( ! class_exists( 'CR_Custom_Question' ) ) :
	class CR_Custom_Question {
		public $type;
		public $title;
		public $label;
		public $value;
		public $values = array();
	}
endif;
