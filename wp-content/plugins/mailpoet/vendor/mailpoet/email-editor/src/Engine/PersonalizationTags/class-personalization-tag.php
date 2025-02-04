<?php
declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\PersonalizationTags;
if (!defined('ABSPATH')) exit;
class Personalization_Tag {
 private string $name;
 private string $token;
 private string $category;
 private $callback;
 private array $attributes;
 public function __construct(
 string $name,
 string $token,
 string $category,
 callable $callback,
 array $attributes = array()
 ) {
 $this->name = $name;
 $this->token = $token;
 $this->category = $category;
 $this->callback = $callback;
 $this->attributes = $attributes;
 }
 public function get_name(): string {
 return $this->name;
 }
 public function get_token(): string {
 return $this->token;
 }
 public function get_category(): string {
 return $this->category;
 }
 public function get_attributes(): array {
 return $this->attributes;
 }
 public function execute_callback( $context, $args = array() ): string {
 return call_user_func( $this->callback, ...array_merge( array( $context ), array( $args ) ) );
 }
}
