<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Email_Verify' ) ) :

	class CR_Email_Verify {
		private static $dns_name = '._domainkey.';
		private static $dns_value = '.dkim.amazonses.com';

		public function __construct() {
		}

		public function is_verified() {
			$licenseKey = get_option( 'ivole_license_key', '' );
			$emailFrom = get_option( 'ivole_email_from', '' );
			$fromName = get_option( 'ivole_email_from_name', Ivole_Email::get_blogname() );
			$emailFooter = get_option( 'ivole_email_footer', '' );
			$res = array (
				'code' => 0,
				'fromEmail' => $emailFrom,
				'fromName' => $fromName,
				'emailFooter' => $emailFooter
			);
			if( filter_var( $emailFrom, FILTER_VALIDATE_EMAIL ) ) {
				$data = array(
					'token' => '164592f60fbf658711d47b2f55a1bbba',
					'licenseKey' => $licenseKey,
					'email' => $emailFrom
				);
				$api_url = 'https://z4jhozi8lc.execute-api.us-east-1.amazonaws.com/v1/is-email-verified';
				$data_string = json_encode($data);
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $api_url );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array (
					'Content-Type: application/json',
					'Content-Length: ' . strlen( $data_string ) )
				);
				$result = curl_exec( $ch );
				if( false === $result ) {
					return $res;
				}
				$result = json_decode( $result );
				if( isset( $result->verified ) && 1 == $result->verified ) {
					$res['code'] = 1;
					return $res;
				} else {
					return $res;
				}
			} else {
				return $res;
			}
		}

		public function verify_email( $email ) {
			$licenseKey = get_option( 'ivole_license_key', '' );
			if( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				$email = strtolower( $email );
				$data = array(
					'token' => '164592f60fbf658711d47b2f55a1bbba',
					'licenseKey' => $licenseKey,
					'shopDomain' => Ivole_Email::get_blogurl(),
					'email' => $email
				);
				$api_url = 'https://z4jhozi8lc.execute-api.us-east-1.amazonaws.com/v1/verify-email';
				$data_string = json_encode($data);
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $api_url );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen( $data_string ) )
				);
				$result = curl_exec( $ch );
				if( false === $result ) {
					return array( 'res' => 2, 'message' => curl_error( $ch ) );
				}
				$result = json_decode( $result );
				//error_log( print_r( $result, true ) );
				if( isset( $result->status ) && 'OK' === $result->status ) {
					update_option( 'ivole_email_from', $email );
					return array( 'res' => 1, 'message' => '' );
				} else if( isset( $result->error ) && 'Email has already been verified' === $result->error ) {
					return array( 'res' => 3, 'message' => $result->error );
				} else {
					return array( 'res' => 0, 'message' => '' );
				}
			} else {
				return array( 'res' => 99, 'message' => '' );
			}
		}

		public function is_dkim_verified() {
			$res = array (
				'code' => 0,
				'tokens' => array()
			);
			$licenseKey = get_option( 'ivole_license_key', '' );
			$emailFrom = get_option( 'ivole_email_from', '' );
			if ( filter_var( $emailFrom, FILTER_VALIDATE_EMAIL ) ) {
				$domain = substr( $emailFrom, strpos( $emailFrom, '@' ) + 1 );
				$http_query = http_build_query(
					array (
						'licenseKey' => $licenseKey,
						'email' => $emailFrom
					)
				);
				$api_url = 'https://api.cusrev.com/v1/production/email-dkim?' . $http_query;
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $api_url );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
				$result = curl_exec( $ch );
				if( false === $result ) {
					return $res;
				}

				$result = json_decode( $result, true );
				if (
					isset( $result['DkimAttributes'] ) &&
					is_array( $result['DkimAttributes'] ) &&
					0 < count( $result['DkimAttributes'] ) &&
					isset( $result['DkimAttributes'][$emailFrom] ) &&
					is_array( $result['DkimAttributes'][$emailFrom] )
				) {
					// get DKIM tokens
					if (
						isset( $result['DkimAttributes'][$emailFrom]['DkimTokens'] ) &&
						is_array( $result['DkimAttributes'][$emailFrom]['DkimTokens'] ) &&
						0 < count( $result['DkimAttributes'][$emailFrom]['DkimTokens'] ) &&
						'cusrev.com' !== $domain
					) {
						foreach ( $result['DkimAttributes'][$emailFrom]['DkimTokens'] as $token ) {
							$res['tokens'][] = array (
								'name' => $token . self::$dns_name . $domain,
								'value' => $token . self::$dns_value
							);
						}
					}
					// get DKIM verification status
					if ( isset( $result['DkimAttributes'][$emailFrom]['DkimVerificationStatus'] ) ) {
						if ( 'Success' === $result['DkimAttributes'][$emailFrom]['DkimVerificationStatus'] ) {
							$res['code'] = 1;
						} elseif ( 'Pending' === $result['DkimAttributes'][$emailFrom]['DkimVerificationStatus'] ) {
							$res['code'] = 2;
						}
					}
				}
			}
			return $res;
		}

		public function verify_dkim( $email ) {
			$res = array (
				'code' => 0,
				'tokens' => array()
			);
			$licenseKey = get_option( 'ivole_license_key', '' );
			if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				$email = strtolower( $email );
				$domain = substr( $email, strpos( $email, '@' ) + 1 );
				$data = array(
					'licenseKey' => $licenseKey,
					'email' => $email
				);
				$api_url = 'https://api.cusrev.com/v1/production/email-dkim';
				$data_string = json_encode($data);
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $api_url );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen( $data_string ) )
				);
				$result = curl_exec( $ch );
				if( false === $result ) {
					return $res;
				}
				$result = json_decode( $result );
				if(
					isset( $result->DkimTokens ) &&
					is_array( $result->DkimTokens ) &&
					0 < count( $result->DkimTokens ) &&
					'cusrev.com' !== $domain
				) {
					update_option( 'ivole_email_from', $email );
					foreach ( $result->DkimTokens as $token ) {
						$res['tokens'][] = array (
							'name' => $token . self::$dns_name . $domain,
							'value' => $token . self::$dns_value
						);
					}
					$res['code'] = 1;
				}
			}
			return $res;
		}

	}

endif;
