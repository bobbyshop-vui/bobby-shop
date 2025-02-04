<?php
declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine;
if (!defined('ABSPATH')) exit;
use MailPoet\EmailEditor\Validator\Builder;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
class Email_Api_Controller {
 public function get_email_data(): array {
 // Here comes code getting Email specific data that will be passed on 'email_data' attribute.
 return array();
 }
 public function save_email_data( array $data, WP_Post $email_post ): void {
 // Here comes code saving of Email specific data that will be passed on 'email_data' attribute.
 }
 public function send_preview_email_data( WP_REST_Request $request ): WP_REST_Response {
 $data = $request->get_params();
 try {
 $result = apply_filters( 'mailpoet_email_editor_send_preview_email', $data );
 return new WP_REST_Response(
 array(
 'success' => (bool) $result,
 'result' => $result,
 ),
 $result ? 200 : 400
 );
 } catch ( \Exception $exception ) {
 return new WP_REST_Response( array( 'error' => $exception->getMessage() ), 400 );
 }
 }
 public function get_email_data_schema(): array {
 return Builder::object()->to_array();
 }
}
