<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//qTranslate integration
$ivole_language = get_option( 'ivole_language' );
if( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) && $ivole_language === 'QQ' ) {
	echo qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( wpautop( wp_kses_post( get_option( 'ivole_email_body', $def_body ) ) ) );
} else {
	//WPML and Polylang integration
	if ( has_filter( 'wpml_translate_single_string' ) && defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE && !function_exists( 'pll_current_language' ) ) {
		$wpml_current_language = strtolower( $lang );
		echo wpautop( wp_kses_post( apply_filters( 'wpml_translate_single_string', get_option( 'ivole_email_body', $def_body ), 'ivole', 'ivole_email_body', $wpml_current_language ) ) );
	} elseif ( function_exists( 'pll_current_language' ) && function_exists( 'pll_get_post_language' ) && function_exists( 'pll_translate_string' ) ) {
		$polylang_current_language = strtolower( $lang );
		echo wpautop( wp_kses_post( pll_translate_string( get_option( 'ivole_email_body', $def_body ), $polylang_current_language ) ) );
	} else {
		echo wpautop( wp_kses_post( get_option( 'ivole_email_body', $def_body ) ) );
	}
}
