<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Qna_Shortcode' ) ) :

	/**
	 * Class for Q & A shortcode
	 */
	class CR_Qna_Shortcode
	{

		/**
		 * @var CR_Qna
		 */
		private $qna;

		/**
		 * The constructor.
		 *
		 * @param CR_Qna $cr_qna
		 */
		public function __construct( $cr_qna ) {
			$this->register_shortcode();
			$this->qna = $cr_qna;
		}

		public function register_shortcode() {
			add_shortcode( 'cusrev_qna', array( $this, 'render_cusrev_qna_shortcode' ) );
		}

		public function render_cusrev_qna_shortcode( $attributes ) {
			if( 'yes' !== get_option( 'ivole_questions_answers', 'no' ) ) {
				echo 'Q & A are disabled in the settings';
				return;
			}
			$attributes = shortcode_atts( array(
				'products' => [],
				'shop' => [],
			), $attributes, 'cusrev_qna' );

			if( !is_array( $attributes['products'] ) ) {
				$attributes['products'] = trim( $attributes['products'] );
			}

			if( !empty( $attributes['products'] ) && $attributes['products'] !== 'all' ) {
				$attributes['products'] = array_map( 'trim', explode( ',', $attributes['products'] ) );
			}

			if( !is_array($attributes['shop']) ) {
				$attributes['shop'] = trim( $attributes['shop'] );
			}

			if( !empty( $attributes['shop'] ) && $attributes['shop'] !== 'all'){
				$attributes['shop'] = array_map( 'trim', explode( ',', $attributes['shop'] ) );
			}

			ob_start();
			$this->qna->display_qna_tab( $attributes );
			$output = ob_get_clean();
			return $output;
		}
	}

endif;
