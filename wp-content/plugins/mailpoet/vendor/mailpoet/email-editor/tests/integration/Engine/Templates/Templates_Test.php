<?php
declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Templates;
if (!defined('ABSPATH')) exit;
class Templates_Test extends \MailPoetTest {
 private Templates $templates;
 public function _before() {
 parent::_before();
 $this->templates = $this->di_container->get( Templates::class );
 $this->templates->initialize();
 }
 public function testItCanFetchBlockTemplate(): void {
 $template_id = 'mailpoet/mailpoet//email-general';
 $template = $this->templates->get_block_template( $template_id );
 self::assertInstanceOf( \WP_Block_Template::class, $template );
 verify( $template->slug )->equals( 'email-general' );
 verify( $template->id )->equals( 'mailpoet/mailpoet//email-general' );
 verify( $template->title )->equals( 'General Email' );
 verify( $template->description )->equals( 'A general template for emails.' );
 }
 public function testItCanAddBlockTemplates(): void {
 $result = $this->templates->add_block_templates( array(), array( 'post_type' => 'mailpoet_email' ), 'wp_template' );
 verify( $result )->arrayCount( 2 );
 verify( $result[0]->content )->notEmpty();
 verify( $result[1]->content )->notEmpty();
 }
 public function testItCanAddBlockTemplateDetails(): void {
 // add_block_template_details.
 $basic_template = new \WP_Block_Template();
 $basic_template->slug = 'simple-light';
 // confirm it has no title or description.
 verify( $basic_template->title )->equals( '' );
 verify( $basic_template->description )->equals( '' );
 $result = $this->templates->add_block_template_details( $basic_template );
 // confirm template was updated.
 verify( $basic_template->title )->equals( 'Simple Light' );
 verify( $basic_template->description )->equals( 'A basic template with header and footer.' );
 verify( $result )->equals( $basic_template );
 }
}
