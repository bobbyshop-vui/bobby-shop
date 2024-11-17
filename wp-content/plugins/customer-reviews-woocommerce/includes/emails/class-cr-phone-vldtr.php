<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Phone_Vldtr' ) ) :

class CR_Phone_Vldtr {

	private $is_supported_php;

	public function __construct() {
		// check PHP version because the phone library works only with PHP 8 or newer
		if ( version_compare( phpversion(), '8.0.0' ) >= 0 ) {
			// load the library for validation of phone numbers
			require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/vendor/autoload.php';
			$this->is_supported_php = true;
		} else {
			$this->is_supported_php = false;
		}
	}

	// a function to parse phone numbers using a specialized library
	public function parse_phone_number( $phone, $country ) {
		if ( $this->is_supported_php ) {
			$NumberProto = '';

			$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
			try {
				$NumberProto = $phoneUtil->parse( $phone, $country );
			} catch ( \libphonenumber\NumberParseException $e ) {
				return false;
			}

			$isValid = $phoneUtil->isValidNumber( $NumberProto );
			if ( ! $isValid ) {
				return false;
			}

			$formattedNumber = $phoneUtil->format( $NumberProto, \libphonenumber\PhoneNumberFormat::E164 );

			// remove '+'
			$formattedNumber = preg_replace( "/[^0-9]/", '', $formattedNumber );

			return $formattedNumber;
		} else {
			return $this->parse_phone_number_backup( $phone, $country );
		}
	}

	// a fallback function for websites with old PHP versions
	private function parse_phone_number_backup( $phone, $country ) {
		// leave only digits and +
		$phone = preg_replace( '/[^\d+]/', '', $phone );

		if ( ! $phone ) {
			return false;
		}

		// try to find the country calling code
		$calling_code = '';
		if ( function_exists( 'WC' ) ) {
			$calling_code = WC()->countries->get_country_calling_code( $country );
			$calling_code = is_array( $calling_code ) ? $calling_code[0] : $calling_code;
		}

		// get a phone number without a country calling code
		if ( $calling_code ) {
			$phone_short = ltrim( $phone, $calling_code );
		}

		// remove a leading zero for countries where it could be added
		if ( ! in_array( $country, array( 'US', 'CA' ), true ) ) {
			$phone_short = preg_replace( '/^0/', '', $phone_short );
		}

		// create a full phone number and remove +
		$phone = $calling_code . $phone_short;
		$phone = preg_replace( "/[^0-9]/", '', $phone );

		return $phone;
	}

}

endif;
