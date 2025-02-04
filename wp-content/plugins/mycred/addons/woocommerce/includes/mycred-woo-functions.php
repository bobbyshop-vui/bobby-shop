<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'mycred_get_woocommerce_settings' ) ) :
	function mycred_get_woocommerce_settings( $module_name = '' ) {

		$settings = array();
		$defaults = mycred_get_addon_defaults( 'woocommerce' );
		$pref_woo = wp_parse_args( mycred_get_option( 'mycred_pref_woo' ), $defaults );

		if ( ! empty( $module_name ) ) {

			if ( ! empty( $pref_woo[ $module_name ] ) ) {
				
				$settings = $pref_woo[ $module_name ];

			}
		
		}
		else {

			$settings = $pref_woo;

		}

		return apply_filters( 'mycred_get_woocommerce_settings', $settings, $module_name, $pref_woo );

	}
endif;

if ( ! function_exists( 'numberBetween' ) ) :
	function numberBetween( $min, $value, $max ) {
		
		if( $min <= $value && $value <= $max ) return false;

		return true;
		
	}
endif;