<?php
declare( strict_types = 1 );
namespace MailPoet\EmailEditor\Engine;
if (!defined('ABSPATH')) exit;
use MailPoet\EmailEditor\Engine\Renderer\Renderer;
class Send_Preview_Email {
 private Renderer $renderer;
 public function __construct(
 Renderer $renderer
 ) {
 $this->renderer = $renderer;
 }
 public function send_preview_email( $data ): bool {
 if ( is_bool( $data ) ) {
 // preview mail already sent. Do not process again.
 return $data;
 }
 $this->validate_data( $data );
 $email = $data['email'];
 $post_id = $data['postId'];
 $post = $this->fetch_post( $post_id );
 $subject = $post->post_title;
 $language = get_bloginfo( 'language' );
 $rendered_data = $this->renderer->render(
 $post,
 $subject,
 __( 'Preview', 'mailpoet' ),
 $language
 );
 $email_html_content = $rendered_data['html'];
 return $this->send_email( $email, $subject, $email_html_content );
 }
 public function send_email( string $to, string $subject, string $body ): bool {
 add_filter( 'wp_mail_content_type', array( $this, 'set_mail_content_type' ) );
 $result = wp_mail( $to, $subject, $body );
 // Reset content-type to avoid conflicts.
 remove_filter( 'wp_mail_content_type', array( $this, 'set_mail_content_type' ) );
 return $result;
 }
 public function set_mail_content_type( string $content_type ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
 return 'text/html';
 }
 private function validate_data( array $data ) {
 if ( empty( $data['email'] ) || empty( $data['postId'] ) ) {
 throw new \InvalidArgumentException( esc_html__( 'Missing required data', 'mailpoet' ) );
 }
 if ( ! is_email( $data['email'] ) ) {
 throw new \InvalidArgumentException( esc_html__( 'Invalid email', 'mailpoet' ) );
 }
 }
 private function fetch_post( $post_id ): \WP_Post {
 $post = get_post( intval( $post_id ) );
 if ( ! $post instanceof \WP_Post ) {
 throw new \Exception( esc_html__( 'Invalid post', 'mailpoet' ) );
 }
 return $post;
 }
}
