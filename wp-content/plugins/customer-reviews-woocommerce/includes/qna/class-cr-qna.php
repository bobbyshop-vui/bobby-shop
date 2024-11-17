<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Qna' ) ) :

	class CR_Qna {

		private $per_page = 5;
		private $recaptcha = '';
		private $recaptcha_score = 0.5;
		private $search = '';
		private $duplicate_answer;

		public function __construct() {
			$this->per_page = apply_filters( 'cr_qna_per_page', $this->per_page );
			if( 'yes' === get_option( 'ivole_questions_answers', 'no' ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'recaptcha_script' ) );
				add_filter( 'woocommerce_product_tabs', array( $this, 'create_qna_tab' ) );
				add_action( 'wp_ajax_cr_new_qna', array( $this, 'new_qna' ) );
				add_action( 'wp_ajax_nopriv_cr_new_qna', array( $this, 'new_qna' ) );
				add_action( 'wp_ajax_cr_vote_question', array( $this, 'vote_q_registered' ) );
				add_action( 'wp_ajax_nopriv_cr_vote_question', array( $this, 'vote_q_unregistered' ) );
				add_action( 'wp_ajax_cr_show_more_qna', array( $this, 'show_more_qna' ) );
				add_action( 'wp_ajax_nopriv_cr_show_more_qna', array( $this, 'show_more_qna' ) );
				add_action( 'comment_post', array( $this, 'comment_post_qna' ), 10, 3 );
				if( 'yes' === get_option( 'ivole_qna_count', 'no' ) ) {
					add_action( 'wc_get_template', array( $this, 'show_qna_count' ), 10, 5 );
				}
				add_action( 'transition_comment_status', array( $this, 'qna_approved' ), 10, 3 );
			}
			add_filter( 'preprocess_comment', array( $this, 'update_answer_type' ) );
			add_action( 'pre_get_comments', array( $this, 'filter_out_qna' ) );
		}

		public function create_qna_tab( $tabs ) {
			$tab_title = __( 'Q & A', 'customer-reviews-woocommerce' );
			$args = $this->get_args();
			$qna_count = $this->get_qna_count( $args );
			if( $qna_count ) {
				$tab_title = sprintf( __( 'Q & A (%d)', 'customer-reviews-woocommerce' ), $qna_count );
			}
			$tabs['cr_qna'] = array(
				'title' 	=> apply_filters( 'cr_qna_tab_title', $tab_title ),
				'priority' 	=> apply_filters( 'cr_qna_tab_priority', 40 ),
				'callback' 	=> array( $this, 'display_qna_tab' )
			);
			return $tabs;
		}

		public function display_qna_tab( $attributes = null ) {
			$args = $this->get_args( $attributes );
			if( $args ) {
				$cr_post_id = get_the_ID();
				$qna = $this->get_qna( $args, 0 );
				$total_qna = $this->get_qna_count( $args );
				$template = wc_locate_template(
					'qna-tab.php',
					'customer-reviews-woocommerce',
					__DIR__ . '/../../templates/'
				);
				$cr_recaptcha = $this->recaptcha;
				include( $template );
			}
		}

		public function get_qna( $args, $page ) {
			$return_qna = array();

			//highlight search results and sanitize
			if( 0 < strlen( $this->search ) ) {
				$qna = get_comments( $args );
				$qna = array_map( function( $item ) {
					$item->comment_content = $this->highlight_search_text( $item->comment_content );
					return $item;
				}, $qna);
			} else {
				$args['number'] = $this->per_page;
				$args['offset'] = $page * $this->per_page;
				$args['parent'] = 0;
				$qna = get_comments( $args );
				$qna = array_map( function( $item ) {
					$item->comment_content = sanitize_textarea_field( $item->comment_content );
					$item->comment_content = esc_html( $item->comment_content );
					return $item;
				}, $qna);
			}
			// fetch answers
			foreach ( $qna as $q ) {

				if( $q->comment_parent != 0 ) {
					$q = get_comment( $q->comment_parent );
				}

				$ans = $q->get_children( array(
					'type' => 'cr_qna',
					'format' => 'tree',
					'status' => 'approve',
					'hierarchical' => false,
					'orderby' => 'date',
					'order' => 'ASC'
				) );
				$return_ans = array();
				foreach ($ans as $a) {
					$author_type = 0;
					if( wc_review_is_from_verified_owner( $a->comment_ID ) ) {
						$author_type = 2;
					}
					if( isset( $a->user_id ) ) {
						if( user_can( $a->user_id, 'manage_woocommerce' ) ) {
							$author_type = 1;
						}
					}

					$answer_content = wpautop( wp_kses( $a->comment_content, wp_kses_allowed_html( 'post' ) ) );

					//highlight
					if( 0 < strlen( $this->search ) ) {
						$answer_content = $this->highlight_search_text( $a->comment_content );
					}

					$return_ans[] = array(
						'id' => $a->comment_ID,
						'answer' => $answer_content,
						'author' => sanitize_text_field( $a->comment_author ),
						'date' => $a->comment_date,
						'author_type' => $author_type
					);
				}
				$return_qna[$q->comment_ID] = array(
					'id' => $q->comment_ID,
					'question' => $q->comment_content, // sanitized above
					'author' => sanitize_text_field( $q->comment_author ),
					'date' => $q->comment_date,
					'answers' => $return_ans,
					'votes' => $this->get_q_votes( $q->comment_ID ),
					'post' => $q->comment_post_ID
				);
			}
			if( 0 < strlen( $this->search ) ) {
				$return_qna = array_slice( $return_qna, $page * $this->per_page, $this->per_page );
			}
			return $return_qna;
		}

		public function new_qna() {
			$return = array(
				'code' => 2,
				'description' => __( 'Data validation error.', 'customer-reviews-woocommerce' )
			);
			if( isset( $_POST['currentPostID'] ) ) {
				$product_id = intval( $_POST['currentPostID'] );
				$question_id = 0;
				$nonce = 'cr_qna_';
				if( isset( $_POST['questionID'] ) && 0 < intval( $_POST['questionID'] ) ) {
					$question_id = intval( $_POST['questionID'] );
					$nonce = 'cr_qna_a_';
					if( isset( $_POST['productID'] ) && 0 < intval( $_POST['productID'] ) ) {
						$product_id = intval( $_POST['productID'] );
					}
				}
				if( 0 < $product_id ) {
					if( check_ajax_referer( $nonce . $_POST['currentPostID'], 'security', false ) ) {
						$captcha_correct = true;
						if( self::is_captcha_enabled() ) {
							$secret_key = get_option( 'ivole_qna_captcha_secret_key', '' );
							if( isset( $_POST['cptcha'] ) && 0 < strlen( $_POST['cptcha'] ) ) {
								$captch_response = json_decode( wp_remote_retrieve_body( wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array( 'body' => array( 'secret' => $secret_key, 'response' => $_POST['cptcha'], 'remoteip' => $_SERVER['REMOTE_ADDR'] ) ) ) ), true );
								if( $captch_response['success'] ) {
									if( $captch_response['score'] && $this->recaptcha_score > $captch_response['score'] ) {
										$captcha_correct = false;
										$return['description'] = __( 'reCAPTCHA score is below the threshold.', 'customer-reviews-woocommerce' );
									}
								} else {
									$captcha_correct = false;
									$return['code'] = 3;
									$return['description'] = sprintf( __( 'reCAPTCHA validation error (%s).', 'customer-reviews-woocommerce' ), implode(', ', $captch_response["error-codes"] ) );
								}
							} else {
								$captcha_correct = false;
								$return['code'] = 4;
								$return['description'] = __( 'reCAPTCHA response is missing.', 'customer-reviews-woocommerce' );
							}
						}
						if( $captcha_correct ) {
							$data_is_available = true;
							$question = '';
							$name = '';
							$email = '';
							if( isset( $_POST['text'] ) ) {
								$question = sanitize_textarea_field( trim( $_POST['text'] ) );
							}
							if( isset( $_POST['name'] ) ) {
								$name = sanitize_text_field( trim( $_POST['name'] ) );
							}
							if( isset( $_POST['email'] ) ) {
								$email = sanitize_email( trim( $_POST['email'] ) );
							}
							if( $question && $name && is_email( $email ) ) {
								$user = get_user_by( 'email', $email );
								if( $user ) {
									$user = $user->ID;
								} else {
									$user = 0;
								}
								$commentdata = array(
									'comment_author' => $name,
									'comment_author_email' => $email,
									'comment_author_url' => '',
									'comment_content' => $question,
									'comment_type' => 'cr_qna',
									'comment_post_ID' => $product_id,
									'comment_parent' => $question_id,
									'user_id' => $user
								);
								add_filter( 'pre_comment_approved', array( $this, 'is_comment_approved' ), 10, 2 );
								$this->duplicate_answer = ( 0 < $question_id ) ? true : false;
								add_filter( 'comment_duplicate_message', array( $this, 'duplicate_message' ) );
								$result = wp_new_comment( $commentdata, true );
								remove_filter( 'comment_duplicate_message', array( $this, 'duplicate_message' ) );
								remove_filter( 'pre_comment_approved', array( $this, 'is_comment_approved' ), 10 );
								if( 0 < $question_id ) {
									$error_description = __( 'An error when adding the answer.', 'customer-reviews-woocommerce' );
									$success_description = __( 'The answer was successfully added.', 'customer-reviews-woocommerce' );
								} else {
									$error_description = __( 'An error when adding the question.', 'customer-reviews-woocommerce' );
									$success_description = __( 'The question was successfully added.', 'customer-reviews-woocommerce' );
								}
								if( !$result || is_wp_error( $result ) ) {
									if( is_wp_error( $result ) ) {
										$error_description = $result->get_error_message();
									}
									$return = array(
										'code' => 1,
										'description' => $error_description
									);
								} else {
									$return = array(
										'code' => 0,
										'description' => $success_description
									);
								}
							}
						}
					}
				}
			}
			wp_send_json( $return );
		}

		public function update_answer_type( $commentdata ) {
			// if a new comment is a reply to a question, then set its type to 'cr_qna'
			if( isset( $commentdata['comment_parent'] ) && 0 < $commentdata['comment_parent'] ) {
				if( 'cr_qna' === get_comment_type( $commentdata['comment_parent'] ) ) {
					$commentdata['comment_type'] = 'cr_qna';
				}
			}
			return $commentdata;
		}

		private function get_qna_count( $args ) {
			$count = 0;
			if( $args ) {
				// fetch questions
				$args['parent'] = 0;
				$args['count'] = true;
				$qna_count = get_comments( $args );
				if( $qna_count ) {
					$count = intval( $qna_count );
				}
			}
			return $count;
		}

		public function recaptcha_script() {
			if( is_product() ) {
				if( self::is_captcha_enabled() ) {
					$lang = CR_Trust_Badge::get_badge_language();
					$site_key = get_option( 'ivole_qna_captcha_site_key', '' );
					$this->recaptcha = $site_key;
					wp_register_script( 'cr-recaptcha', 'https://www.google.com/recaptcha/api.js?hl=' . $lang . '&render=' . $site_key , array(), null, true );
					wp_enqueue_script( 'cr-recaptcha' );
				}
			}
		}

		public function filter_out_qna( &$query ) {
			if( is_product() ) {
				if( isset( $query->query_vars ) && isset( $query->query_vars['type'] ) && 'cr_qna' !== $query->query_vars['type'] ) {
					if( isset( $query->query_vars['type__not_in'] ) && is_array( $query->query_vars['type__not_in'] ) ) {
						$query->query_vars['type__not_in'][] = 'cr_qna';
					} else {
						$query->query_vars['type__not_in'] = array( 'cr_qna' );
					}
				}
			}
		}

		public function vote_q_registered() {
			$comment_id = intval( $_POST['reviewID'] );
			$upvote = intval( $_POST['upvote'] );
			$registered_upvoters = get_comment_meta( $comment_id, 'cr_question_reg_upvoters', true );
			$registered_downvoters = get_comment_meta( $comment_id, 'cr_question_reg_downvoters', true );
			$current_user = get_current_user_id();
			// check if this registered user has already upvoted this review
			if( !empty( $registered_upvoters ) ) {
				$registered_upvoters = maybe_unserialize( $registered_upvoters );
				if( is_array( $registered_upvoters ) ) {
					$registered_upvoters_count = count( $registered_upvoters );
					$index_upvoters = -1;
					for($i = 0; $i < $registered_upvoters_count; $i++ ) {
						if( $current_user === $registered_upvoters[$i] ) {
							if( 0 < $upvote ) {
								// upvote request, exit because this user has already upvoted this review earlier
								$votes = $this->get_q_votes( $comment_id );
								wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
								return;
							} else {
								// downvote request, remove the upvote
								$index_upvoters = $i;
								break;
							}
						}
					}
					if( 0 <= $index_upvoters ) {
						array_splice( $registered_upvoters, $index_upvoters, 1 );
					}
				} else {
					$registered_upvoters = array();
				}
			} else {
				$registered_upvoters = array();
			}
			// check if this registered user has already downvoted this review
			if( !empty( $registered_downvoters ) ) {
				$registered_downvoters = maybe_unserialize( $registered_downvoters );
				if( is_array( $registered_downvoters ) ) {
					$registered_downvoters_count = count( $registered_downvoters );
					$index_downvoters = -1;
					for($i = 0; $i < $registered_downvoters_count; $i++ ) {
						if( $current_user === $registered_downvoters[$i] ) {
							if( 0 < $upvote ) {
								// upvote request, remove the downvote
								$index_downvoters = $i;
								break;
							} else {
								// downvote request, exit because this user has already downvoted this review earlier
								$votes = $this->get_q_votes( $comment_id );
								wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
							}
						}
					}
					if( 0 <= $index_downvoters ) {
						array_splice( $registered_downvoters, $index_downvoters, 1 );
					}
				} else {
					$registered_downvoters = array();
				}
			} else {
				$registered_downvoters = array();
			}

			//update arrays of registered upvoters and downvoters
			if( 0 < $upvote ) {
				$registered_upvoters[] = $current_user;
			} else {
				$registered_downvoters[] = $current_user;
			}
			update_comment_meta( $comment_id, 'cr_question_reg_upvoters', $registered_upvoters );
			update_comment_meta( $comment_id, 'cr_question_reg_downvoters', $registered_downvoters );
			$votes = $this->get_q_votes( $comment_id );
			$this->send_q_votes( $comment_id, $votes );
			// compatibility with W3 Total Cache plugin
			// clear DB cache to make sure that count of upvotes is immediately updated
			if( function_exists( 'w3tc_dbcache_flush' ) ) {
				w3tc_dbcache_flush();
			}
			wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
		}

		public function send_q_votes( $comment_id, $votes ) {
			$comment = get_comment( $comment_id );
			if( $comment ) {
				update_comment_meta( $comment_id, 'cr_question_votes', $votes['upvotes'] - $votes['downvotes'] );
				$product_id = $comment->comment_post_ID;
				//clear WP Super Cache after voting
				if( function_exists( 'wpsc_delete_post_cache' ) ) {
					wpsc_delete_post_cache( $product_id );
				}
				//clear W3TC after voting
				if( function_exists( 'w3tc_flush_post' ) ) {
					w3tc_flush_post( $product_id );
				}
			}
		}

		public function get_q_votes( $comment_id ) {
			$r_upvotes = 0;
			$r_downvotes = 0;
			$u_upvotes = 0;
			$u_downvotes = 0;
			$current = 0;
			$registered_upvoters = get_comment_meta( $comment_id, 'cr_question_reg_upvoters', true );
			$registered_downvoters = get_comment_meta( $comment_id, 'cr_question_reg_downvoters', true );
			$unregistered_upvoters = get_comment_meta( $comment_id, 'cr_question_unreg_upvoters', true );
			$unregistered_downvoters = get_comment_meta( $comment_id, 'cr_question_unreg_downvoters', true );

			if( !empty( $registered_upvoters ) ) {
				$registered_upvoters = maybe_unserialize( $registered_upvoters );
				if( is_array( $registered_upvoters ) ) {
					$r_upvotes = count( $registered_upvoters );
				}
			}

			if( !empty( $registered_downvoters ) ) {
				$registered_downvoters = maybe_unserialize( $registered_downvoters );
				if( is_array( $registered_downvoters ) ) {
					$r_downvotes = count( $registered_downvoters );
				}
			}

			if( !empty( $unregistered_upvoters ) ) {
				$unregistered_upvoters = maybe_unserialize( $unregistered_upvoters );
				if( is_array( $unregistered_upvoters ) ) {
					$u_upvotes = count( $unregistered_upvoters );
				}
			}

			if( !empty( $unregistered_downvoters ) ) {
				$unregistered_downvoters = maybe_unserialize( $unregistered_downvoters );
				if( is_array( $unregistered_downvoters ) ) {
					$u_downvotes = count( $unregistered_downvoters );
				}
			}

			$current_user = get_current_user_id();
			if( $current_user ) {
				if( is_array( $registered_upvoters ) ) {
					$r_upvoters_flip = array_flip( $registered_upvoters );
					if( isset( $r_upvoters_flip[$current_user] ) ) {
						$current = 1;
					}
				}
				if( 0 === $current && is_array( $registered_downvoters ) ) {
					$r_downvoters_flip = array_flip( $registered_downvoters );
					if( isset( $r_downvoters_flip[$current_user] ) ) {
						$current = -1;
					}
				}
			} else {
				$ip = $_SERVER['REMOTE_ADDR'];
				if( is_array( $unregistered_upvoters ) ) {
					$u_upvoters_flip = array_flip( $unregistered_upvoters );
					if( isset( $u_upvoters_flip[$ip] ) ) {
						$current = 1;
					}
				}
				if( 0 === $current && is_array( $unregistered_downvoters ) ) {
					$u_downvoters_flip = array_flip( $unregistered_downvoters );
					if( isset( $u_downvoters_flip[$ip] ) ) {
						$current = -1;
					}
				}
				if( 0 === $current ) {
					if( isset( $_COOKIE['cr_question_upvote'] ) ) {
						$upcomment_ids = json_decode( $_COOKIE['cr_question_upvote'], true );
						if( is_array( $upcomment_ids ) ) {
							$upcomment_ids_flip = array_flip( $upcomment_ids );
							if( isset( $upcomment_ids_flip[$comment_id] ) ) {
								$current = 1;
							}
						}
						if( 0 === $current ) {
							$downcomment_ids = json_decode( $_COOKIE['cr_question_downvote'], true );
							if( is_array( $downcomment_ids ) ) {
								$downcomment_ids_flip = array_flip( $downcomment_ids );
								if( isset( $downcomment_ids_flip[$comment_id] ) ) {
									$current = -1;
								}
							}
						}
					}
				}
			}

			$votes = array(
				'upvotes' => $r_upvotes + $u_upvotes,
				'downvotes' => $r_downvotes + $u_downvotes,
				'total' => $r_upvotes + $r_downvotes + $u_upvotes + $u_downvotes,
				'current' => $current
			);
			return $votes;
		}

		public function vote_q_unregistered() {
			$ip = $_SERVER['REMOTE_ADDR'];
			$comment_id = intval( $_POST['reviewID'] );
			$upvote = intval( $_POST['upvote'] );

			// check (via cookie) if this unregistered user has already upvoted this review
			if( isset( $_COOKIE['cr_question_upvote'] ) ) {
				$upcomment_ids = json_decode( $_COOKIE['cr_question_upvote'], true );
				if( is_array( $upcomment_ids ) ) {
					$upcomment_ids_count = count( $upcomment_ids );
					$index_upvoters = -1;
					for( $i = 0; $i < $upcomment_ids_count; $i++ ) {
						if( $comment_id === $upcomment_ids[$i] ) {
							if( 0 < $upvote ) {
								// upvote request, exit because this user has already upvoted this review earlier
								$votes = $this->get_q_votes( $comment_id );
								wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
							} else {
								// downvote request, remove the upvote
								$index_upvoters = $i;
								break;
							}
						}
					}
					if( 0 <= $index_upvoters ) {
						array_splice( $upcomment_ids, $index_upvoters, 1 );
					}
				} else {
					$upcomment_ids = array();
				}
			} else {
				$upcomment_ids = array();
			}

			// check (via cookie) if this unregistered user has already downvoted this review
			if( isset( $_COOKIE['cr_question_downvote'] ) ) {
				$downcomment_ids = json_decode( $_COOKIE['cr_question_downvote'], true );
				if( is_array( $downcomment_ids ) ) {
					$downcomment_ids_count = count( $downcomment_ids );
					$index_downvoters = -1;
					for( $i = 0; $i < $downcomment_ids_count; $i++ ) {
						if( $comment_id === $downcomment_ids[$i] ) {
							if( 0 < $upvote ) {
								// upvote request, remove the downvote
								$index_downvoters = $i;
								break;
							} else {
								// downvote request, exit because this user has already downvoted this review earlier
								$votes = $this->get_q_votes( $comment_id );
								wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
							}
						}
					}
					if( 0 <= $index_downvoters ) {
						array_splice( $downcomment_ids, $index_downvoters, 1 );
					}
				} else {
					$downcomment_ids = array();
				}
			} else {
				$downcomment_ids = array();
			}

			$unregistered_upvoters = get_comment_meta( $comment_id, 'cr_question_unreg_upvoters', true );
			$unregistered_downvoters = get_comment_meta( $comment_id, 'cr_question_unreg_downvoters', true );

			// check if this unregistered user has already upvoted this review
			if( !empty( $unregistered_upvoters ) ) {
				$unregistered_upvoters = maybe_unserialize( $unregistered_upvoters );
				if( is_array( $unregistered_upvoters ) ) {
					$unregistered_upvoters_count = count( $unregistered_upvoters );
					$index_upvoters = -1;
					for($i = 0; $i < $unregistered_upvoters_count; $i++ ) {
						if( $ip === $unregistered_upvoters[$i] ) {
							if( 0 < $upvote ) {
								// upvote request, exit because this user has already upvoted this review earlier
								$votes = $this->get_q_votes( $comment_id );
								wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
							} else {
								// downvote request, remove the upvote
								$index_upvoters = $i;
								break;
							}
						}
					}
					if( 0 <= $index_upvoters ) {
						array_splice( $unregistered_upvoters, $index_upvoters, 1 );
					}
				} else {
					$unregistered_upvoters = array();
				}
			} else {
				$unregistered_upvoters = array();
			}

			// check if this unregistered user has already downvoted this review
			if( !empty( $unregistered_downvoters ) ) {
				$unregistered_downvoters = maybe_unserialize( $unregistered_downvoters );
				if( is_array( $unregistered_downvoters ) ) {
					$unregistered_downvoters_count = count( $unregistered_downvoters );
					$index_downvoters = -1;
					for($i = 0; $i < $unregistered_downvoters_count; $i++ ) {
						if( $ip === $unregistered_downvoters[$i] ) {
							if( 0 < $upvote ) {
								// upvote request, remove the downvote
								$index_downvoters = $i;
								break;
							} else {
								// downvote request, exit because this user has already downvoted this review earlier
								$votes = $this->get_q_votes( $comment_id );
								wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
							}
						}
					}
					if( 0 <= $index_downvoters ) {
						array_splice( $unregistered_downvoters, $index_downvoters, 1 );
					}
				} else {
					$unregistered_downvoters = array();
				}
			} else {
				$unregistered_downvoters = array();
			}

			//update cookie arrays of unregistered upvoters and downvoters
			if( 0 < $upvote ) {
				$upcomment_ids[] = $comment_id;
				$unregistered_upvoters[] = $ip;
			} else {
				$downcomment_ids[] = $comment_id;
				$unregistered_downvoters[] = $ip;
			}
			setcookie( 'cr_question_upvote', json_encode( $upcomment_ids ), time() + 365*24*60*60, COOKIEPATH, COOKIE_DOMAIN );
			setcookie( 'cr_question_downvote', json_encode( $downcomment_ids ), time() + 365*24*60*60, COOKIEPATH, COOKIE_DOMAIN );
			update_comment_meta( $comment_id, 'cr_question_unreg_upvoters', $unregistered_upvoters );
			update_comment_meta( $comment_id, 'cr_question_unreg_downvoters', $unregistered_downvoters );
			$votes = $this->get_q_votes( $comment_id );
			$this->send_q_votes( $comment_id, $votes );
			// compatibility with W3 Total Cache plugin
			// clear DB cache to make sure that count of upvotes is immediately updated
			if( function_exists( 'w3tc_dbcache_flush' ) ) {
				w3tc_dbcache_flush();
			}
			wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
		}

		// returns HTML with a list of questions and answers
		// requires an array of questions and answers as the input parameter
		public static function display_qna_list( $qna ) {
			$template = wc_locate_template(
				'qna-list.php',
				'customer-reviews-woocommerce',
				__DIR__ . '/../../templates/'
			);
			$output = '';
			$date_format = get_option( 'date_format', 'F j, Y' );
			$cr_verified_label = get_option( 'ivole_verified_owner', '' );
			if( $cr_verified_label ) {
				if ( function_exists( 'pll__' ) ) {
					$cr_verified_label = esc_attr( pll__( $cr_verified_label ) );
				} else {
					$cr_verified_label = esc_attr( $cr_verified_label );
				}
			} else {
				$cr_verified_label = esc_attr__( 'verified owner', 'woocommerce' );
			}
			ob_start();
			include( $template );
			$output = ob_get_clean();
			return $output;
		}

		public function show_more_qna() {
			$html = '';
			$page = 0;
			$last_page = false;
			if( isset( $_POST['productID'] ) ) {
				if( isset( $_POST['page'] ) ) {
					//search
					if( !empty( trim( $_POST['search'] ) ) ) {
						$this->search = sanitize_text_field( trim( $_POST['search'] ) );
					} else {
						$this->search = '';
					}
					$page = intval( $_POST['page'] ) + 1;
					$args = $this->get_args();
					$qna = $this->get_qna( $args, $page );
					$qna_count = count( $qna );
					if( 0 < $qna_count ) {
						$html = CR_Qna::display_qna_list( $qna );
					}
					if( $this->get_qna_count( $args ) <= $this->per_page * $page + $qna_count ) {
						$last_page = true;
					}
				}
			}
			wp_send_json( array(
				'page' => $page,
				'html' => $html,
				'last_page' => $last_page )
			);
		}

		public function show_qna_count( $located, $template_name, $args, $template_path, $default_path ) {
			if( 'single-product/rating.php' === $template_name ) {
				global $product;
				if( isset( $product ) ) {
					$template = wc_locate_template(
						'cr-rating.php',
						'customer-reviews-woocommerce',
						__DIR__ . '/../../templates/'
					);
					$located = $template;
				}
			}
			return $located;
		}

		public static function get_count_answered( $product_id ) {
			global $wpdb;
			$query = "SELECT COUNT(DISTINCT cmt1.comment_ID) AS count FROM $wpdb->comments AS cmt1 " .
			"INNER JOIN $wpdb->comments AS cmt2 ON cmt1.comment_ID = cmt2.comment_parent " .
			"WHERE cmt1.comment_approved = 1 AND cmt1.comment_post_ID = " . $product_id .
			" AND cmt1.comment_parent = 0 AND cmt1.comment_type = 'cr_qna'";
			$count_answered = $wpdb->get_var( $query );
			return intval( $count_answered );
		}

		private function highlight_search_text( $text ) {

			$highlight = $this->search;

			$text = sanitize_textarea_field( $text );
			$text = esc_html( $text );
			$text = preg_replace( '/(' . $highlight . ')(?![^<>]*\/>)/iu', '<span class="cr-search-highlight">\0</span>', $text );

			return $text;
		}

		public static function is_captcha_enabled() {
			return 'yes' === get_option( 'ivole_qna_enable_captcha', 'no' );
		}

		public function is_comment_approved( $approved, $commentdata ) {
			if ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ) ) {
				$approved = 1;
			} else {
				$approved = 0;
			}
			return $approved;
		}

		public function duplicate_message( $message ) {
			if ( $this->duplicate_answer ) {
				$message = __( 'A similar answer has already been provided. Please rephrase your answer.', 'customer-reviews-woocommerce' );
			} else {
				$message = __( 'A similar question has already been asked. Please rephrase your question.', 'customer-reviews-woocommerce' );
			}
			return $message;
		}

		private function get_args( $attributes = null ) {
			$args = array(
				'status' => 'approve',
				'type' => 'cr_qna',
				'search' => $this->search,
				'post_type' => array(),
				'post__in' => array()
			);

			if( !$attributes && isset( $_POST['cr_attributes'] ) ) {
				$attributes = $_POST['cr_attributes'];
			}
			if( $attributes ) {
				if( isset( $attributes['products'] ) ) {
					if( 'all' === $attributes['products'] ) {
						$args['post_type'][] = 'product';
					} elseif( is_array( $attributes['products'] ) ) {
						$args['post__in'] = array_merge( $args['post__in'], $attributes['products'] );
					}
				}
				if( isset( $attributes['shop'] ) ) {
					if( 'all' === $attributes['shop'] ) {
						$args['post_type'] = array_merge( $args['post_type'], array( 'post', 'page' ) );
					} elseif( is_array( $attributes['shop'] ) ) {
						$args['post__in'] = array_merge( $args['post__in'], $attributes['shop'] );
					}
				}
			}

			if( 0 === count( $args['post_type'] ) && 0 === count( $args['post__in'] ) ) {
				// get the current page / post / product ID
				$post_id = get_the_ID();
				if( !$post_id && isset( $_POST['productID'] ) ){
					$post_id = intval( $_POST['productID'] );
				}
				if( !$post_id ) {
					return false;
				}
				$args['post__in'][] = $post_id;
			}

			return $args;
		}

		public function qna_approved( $new_status, $old_status, $comment ) {
			if( 'cr_qna' === get_comment_type( $comment ) && 0 < $comment->comment_parent ) {
				if( $new_status != $old_status ) {
					if( 'approved' === $new_status ) {
						$language = NULL;
						// WPML integration
						if ( has_action( 'wpml_post_language_details' ) ) {
							$wpml_language = apply_filters( 'wpml_post_language_details', NULL, $comment->comment_post_ID );
							if ( $wpml_language && isset( $wpml_language['language_code'] ) ) {
								$language = $wpml_language['language_code'];
							}
						}
						//
						$qe = new CR_Qna_Email( 'qna_reply', $language );
						$qe->trigger_email( $comment->comment_parent, $comment->comment_author, $comment->comment_post_ID, $comment->comment_content, $comment->comment_ID );
					}
				}
			}
		}

		public function comment_post_qna( $comment_ID, $comment_approved, $commentdata ) {
			if( $commentdata && is_array( $commentdata ) ) {
				if( isset( $commentdata['comment_type'] ) && 'cr_qna' === $commentdata['comment_type'] ) {
					if( isset( $commentdata['comment_parent'] ) && 0 < $commentdata['comment_parent'] ) {
						if( 1 === $comment_approved ) {
							$language = NULL;
							// WPML integration
							if ( has_action( 'wpml_post_language_details' ) ) {
								$wpml_language = apply_filters( 'wpml_post_language_details', NULL, $commentdata['comment_post_ID'] );
								if ( $wpml_language && isset( $wpml_language['language_code'] ) ) {
									$language = $wpml_language['language_code'];
								}
							}
							//
							$qe = new CR_Qna_Email( 'qna_reply', $language );
							$qe->trigger_email( $commentdata['comment_parent'], $commentdata['comment_author'], $commentdata['comment_post_ID'], $commentdata['comment_content'], $comment_ID );
						}
					}
				}
			}
		}

	}

endif;
