<?php

if (!defined('ABSPATH')) exit;


use MailPoetVendor\Twig\Environment;
use MailPoetVendor\Twig\Error\LoaderError;
use MailPoetVendor\Twig\Error\RuntimeError;
use MailPoetVendor\Twig\Extension\CoreExtension;
use MailPoetVendor\Twig\Extension\SandboxExtension;
use MailPoetVendor\Twig\Markup;
use MailPoetVendor\Twig\Sandbox\SecurityError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedTagError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedFilterError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedFunctionError;
use MailPoetVendor\Twig\Source;
use MailPoetVendor\Twig\Template;

/* newsletter/editor.html */
class __TwigTemplate_aa06caf4713b6d5ac2d6909b4a0a21f60afeea73e802e676faab4d7dcfdcb31a extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'templates' => [$this, 'block_templates'],
            'content' => [$this, 'block_content'],
            'after_javascript' => [$this, 'block_after_javascript'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "layout.html";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $this->parent = $this->loadTemplate("layout.html", "newsletter/editor.html", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_templates($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        yield "  ";
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_tools_generic", "newsletter/templates/blocks/base/toolsGeneric.hbs");
        // line 7
        yield "
  ";
        // line 8
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_automated_latest_content_block", "newsletter/templates/blocks/automatedLatestContent/block.hbs");
        // line 11
        yield "
  ";
        // line 12
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_automated_latest_content_widget", "newsletter/templates/blocks/automatedLatestContent/widget.hbs");
        // line 15
        yield "
  ";
        // line 16
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_automated_latest_content_settings", "newsletter/templates/blocks/automatedLatestContent/settings.hbs");
        // line 19
        yield "
  ";
        // line 20
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_automated_latest_content_layout_block", "newsletter/templates/blocks/automatedLatestContentLayout/block.hbs");
        // line 23
        yield "
  ";
        // line 24
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_automated_latest_content_layout_widget", "newsletter/templates/blocks/automatedLatestContentLayout/widget.hbs");
        // line 27
        yield "
  ";
        // line 28
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_automated_latest_content_layout_settings", "newsletter/templates/blocks/automatedLatestContentLayout/settings.hbs");
        // line 31
        yield "
  ";
        // line 32
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_button_block", "newsletter/templates/blocks/button/block.hbs");
        // line 35
        yield "
  ";
        // line 36
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_button_widget", "newsletter/templates/blocks/button/widget.hbs");
        // line 39
        yield "
  ";
        // line 40
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_button_settings", "newsletter/templates/blocks/button/settings.hbs");
        // line 43
        yield "
  ";
        // line 44
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_block", "newsletter/templates/blocks/container/block.hbs");
        // line 47
        yield "
  ";
        // line 48
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_block_empty", "newsletter/templates/blocks/container/emptyBlock.hbs");
        // line 51
        yield "
  ";
        // line 52
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_one_column_widget", "newsletter/templates/blocks/container/oneColumnLayoutWidget.hbs");
        // line 55
        yield "
  ";
        // line 56
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_two_column_widget", "newsletter/templates/blocks/container/twoColumnLayoutWidget.hbs");
        // line 59
        yield "
  ";
        // line 60
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_two_column_12_widget", "newsletter/templates/blocks/container/twoColumnLayoutWidget12.hbs");
        // line 63
        yield "
  ";
        // line 64
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_two_column_21_widget", "newsletter/templates/blocks/container/twoColumnLayoutWidget21.hbs");
        // line 67
        yield "
  ";
        // line 68
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_three_column_widget", "newsletter/templates/blocks/container/threeColumnLayoutWidget.hbs");
        // line 71
        yield "
  ";
        // line 72
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_settings", "newsletter/templates/blocks/container/settings.hbs");
        // line 75
        yield "
  ";
        // line 76
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_column_settings", "newsletter/templates/blocks/container/columnSettings.hbs");
        // line 79
        yield "
  ";
        // line 80
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_divider_block", "newsletter/templates/blocks/divider/block.hbs");
        // line 83
        yield "
  ";
        // line 84
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_divider_widget", "newsletter/templates/blocks/divider/widget.hbs");
        // line 87
        yield "
  ";
        // line 88
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_divider_settings", "newsletter/templates/blocks/divider/settings.hbs");
        // line 91
        yield "
  ";
        // line 92
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_footer_block", "newsletter/templates/blocks/footer/block.hbs");
        // line 95
        yield "
  ";
        // line 96
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_footer_widget", "newsletter/templates/blocks/footer/widget.hbs");
        // line 99
        yield "
  ";
        // line 100
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_footer_settings", "newsletter/templates/blocks/footer/settings.hbs");
        // line 103
        yield "
  ";
        // line 104
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_header_block", "newsletter/templates/blocks/header/block.hbs");
        // line 107
        yield "
  ";
        // line 108
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_header_widget", "newsletter/templates/blocks/header/widget.hbs");
        // line 111
        yield "
  ";
        // line 112
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_header_settings", "newsletter/templates/blocks/header/settings.hbs");
        // line 115
        yield "
  ";
        // line 116
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_image_block", "newsletter/templates/blocks/image/block.hbs");
        // line 119
        yield "
  ";
        // line 120
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_image_widget", "newsletter/templates/blocks/image/widget.hbs");
        // line 123
        yield "
  ";
        // line 124
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_image_settings", "newsletter/templates/blocks/image/settings.hbs");
        // line 127
        yield "
  ";
        // line 128
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_block", "newsletter/templates/blocks/posts/block.hbs");
        // line 131
        yield "
  ";
        // line 132
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_widget", "newsletter/templates/blocks/posts/widget.hbs");
        // line 135
        yield "
  ";
        // line 136
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_settings", "newsletter/templates/blocks/posts/settings.hbs");
        // line 139
        yield "
  ";
        // line 140
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_settings_display_options", "newsletter/templates/blocks/posts/settingsDisplayOptions.hbs");
        // line 143
        yield "
  ";
        // line 144
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_settings_selection", "newsletter/templates/blocks/posts/settingsSelection.hbs");
        // line 147
        yield "
  ";
        // line 148
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_settings_selection_empty", "newsletter/templates/blocks/posts/settingsSelectionEmpty.hbs");
        // line 151
        yield "
  ";
        // line 152
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_settings_single_post", "newsletter/templates/blocks/posts/settingsSinglePost.hbs");
        // line 155
        yield "
  ";
        // line 156
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_block", "newsletter/templates/blocks/products/block.hbs");
        // line 159
        yield "
  ";
        // line 160
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_widget", "newsletter/templates/blocks/products/widget.hbs");
        // line 163
        yield "
  ";
        // line 164
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_settings", "newsletter/templates/blocks/products/settings.hbs");
        // line 167
        yield "
  ";
        // line 168
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_settings_display_options", "newsletter/templates/blocks/products/settingsDisplayOptions.hbs");
        // line 171
        yield "
  ";
        // line 172
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_settings_selection", "newsletter/templates/blocks/products/settingsSelection.hbs");
        // line 175
        yield "
  ";
        // line 176
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_settings_selection_empty", "newsletter/templates/blocks/products/settingsSelectionEmpty.hbs");
        // line 179
        yield "
  ";
        // line 180
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_settings_single_post", "newsletter/templates/blocks/products/settingsSinglePost.hbs");
        // line 183
        yield "
  ";
        // line 184
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_acc_block", "newsletter/templates/blocks/abandonedCartContent/block.hbs");
        // line 187
        yield "
  ";
        // line 188
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_acc_widget", "newsletter/templates/blocks/abandonedCartContent/widget.hbs");
        // line 191
        yield "
  ";
        // line 192
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_acc_settings", "newsletter/templates/blocks/abandonedCartContent/settings.hbs");
        // line 195
        yield "
  ";
        // line 196
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_acc_settings_display_options", "newsletter/templates/blocks/abandonedCartContent/settingsDisplayOptions.hbs");
        // line 199
        yield "
  ";
        // line 200
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_block", "newsletter/templates/blocks/social/block.hbs");
        // line 203
        yield "
  ";
        // line 204
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_block_icon", "newsletter/templates/blocks/social/blockIcon.hbs");
        // line 207
        yield "
  ";
        // line 208
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_widget", "newsletter/templates/blocks/social/widget.hbs");
        // line 211
        yield "
  ";
        // line 212
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_settings", "newsletter/templates/blocks/social/settings.hbs");
        // line 215
        yield "
  ";
        // line 216
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_settings_icon", "newsletter/templates/blocks/social/settingsIcon.hbs");
        // line 219
        yield "
  ";
        // line 220
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_settings_icon_selector", "newsletter/templates/blocks/social/settingsIconSelector.hbs");
        // line 223
        yield "
  ";
        // line 224
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_settings_styles", "newsletter/templates/blocks/social/settingsStyles.hbs");
        // line 227
        yield "
  ";
        // line 228
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_spacer_block", "newsletter/templates/blocks/spacer/block.hbs");
        // line 231
        yield "
  ";
        // line 232
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_spacer_widget", "newsletter/templates/blocks/spacer/widget.hbs");
        // line 235
        yield "
  ";
        // line 236
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_spacer_settings", "newsletter/templates/blocks/spacer/settings.hbs");
        // line 239
        yield "
  ";
        // line 240
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_text_block", "newsletter/templates/blocks/text/block.hbs");
        // line 243
        yield "
  ";
        // line 244
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_text_widget", "newsletter/templates/blocks/text/widget.hbs");
        // line 247
        yield "
  ";
        // line 248
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_text_settings", "newsletter/templates/blocks/text/settings.hbs");
        // line 251
        yield "
  ";
        // line 252
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_heading", "newsletter/templates/components/heading.hbs");
        // line 255
        yield "
  ";
        // line 256
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_history", "newsletter/templates/components/history.hbs");
        // line 259
        yield "
  ";
        // line 260
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_save", "newsletter/templates/components/save.hbs");
        // line 263
        yield "
  ";
        // line 264
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_styles", "newsletter/templates/components/styles.hbs");
        // line 267
        yield "
  ";
        // line 268
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_newsletter_preview", "newsletter/templates/components/newsletterPreview.hbs");
        // line 271
        yield "
  ";
        // line 272
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_sidebar", "newsletter/templates/components/sidebar/sidebar.hbs");
        // line 275
        yield "
  ";
        // line 276
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_sidebar_content", "newsletter/templates/components/sidebar/content.hbs");
        // line 279
        yield "
  ";
        // line 280
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_sidebar_layout", "newsletter/templates/components/sidebar/layout.hbs");
        // line 283
        yield "
  ";
        // line 284
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_sidebar_styles", "newsletter/templates/components/sidebar/styles.hbs");
        // line 287
        yield "
  ";
        // line 288
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_new_account_content", "newsletter/templates/blocks/woocommerceContent/new_account.hbs");
        // line 291
        yield "
  ";
        // line 292
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_processing_order_content", "newsletter/templates/blocks/woocommerceContent/processing_order.hbs");
        // line 295
        yield "
  ";
        // line 296
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_completed_order_content", "newsletter/templates/blocks/woocommerceContent/completed_order.hbs");
        // line 299
        yield "
  ";
        // line 300
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_customer_note_content", "newsletter/templates/blocks/woocommerceContent/customer_note.hbs");
        // line 303
        yield "
  ";
        // line 304
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_content_widget", "newsletter/templates/blocks/woocommerceContent/widget.hbs");
        // line 307
        yield "
  ";
        // line 308
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_heading_block", "newsletter/templates/blocks/woocommerceHeading/block.hbs");
        // line 311
        yield "
  ";
        // line 312
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_heading_widget", "newsletter/templates/blocks/woocommerceHeading/widget.hbs");
        // line 315
        yield "
  ";
        // line 316
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_unknown_block_fallback_block", "newsletter/templates/blocks/unknownBlockFallback/block.hbs");
        // line 319
        yield "
  ";
        // line 320
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_unknown_block_fallback_widget", "newsletter/templates/blocks/unknownBlockFallback/widget.hbs");
        // line 323
        yield "
  ";
        // line 324
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_coupon_block", "newsletter/templates/blocks/coupon/block.hbs");
        // line 327
        yield "
  ";
        // line 328
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_coupon_widget", "newsletter/templates/blocks/coupon/widget.hbs");
        // line 331
        yield "
  ";
        // line 332
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_coupon_settings", "newsletter/templates/blocks/coupon/settings.hbs");
        // line 335
        yield "
";
        return; yield '';
    }

    // line 338
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 339
        yield "<!-- Hidden heading for notices to appear under -->
<h1 style=\"display:none\">";
        // line 340
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Newsletter Editor");
        yield "</h1>
<div id=\"mailpoet_editor\">
  <div id=\"mailpoet_editor_steps_heading\"></div>
  <div class=\"clearfix\"></div>
  <div id=\"mailpoet_editor_heading_left\">
    <div id=\"mailpoet_editor_heading\"></div>
  </div>
  <div id=\"mailpoet_editor_heading_right\">
    <div id=\"mailpoet_editor_top\"></div>
  </div>
  <div class=\"clearfix\"></div>
  <div id=\"mailpoet_editor_main_wrapper\">
    <div id=\"mailpoet_editor_styles\"></div>
    <div id=\"mailpoet_editor_content_container\">
      <div class=\"mailpoet_newsletter_wrapper\">
        <div id=\"mailpoet_editor_content\"></div>
      </div>
    </div>
    <div id=\"mailpoet_editor_sidebar\"></div>
    <div class=\"clear\"></div>
  </div>
  <div id=\"mailpoet_editor_bottom\"></div>

  <div class=\"mailpoet_layer_overlay\" style=\"display:none;\"></div>
</div>

<script type=\"text/javascript\">
  var mailpoet_email_editor_tutorial_seen  = '";
        // line 367
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["editor_tutorial_seen"] ?? null), "html", null, true);
        yield "';
  var mailpoet_email_editor_tutorial_url = '";
        // line 368
        yield $this->extensions['MailPoet\Twig\Assets']->generateCdnUrl("newsletter-editor/editor-drag-demo.20190226-1505.mp4");
        yield "';
  var mailpoet_installed_at = '";
        // line 369
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "installed_at", [], "any", false, false, false, 369), "html", null, true);
        yield "';
</script>

";
        // line 372
        if (CoreExtension::getAttribute($this->env, $this->source, ($context["brand_styles"] ?? null), "available", [], "any", false, false, false, 372)) {
            // line 373
            yield "<script type=\"text/javascript\">
  var mailpoet_brand_styles = {
    available: true
  };
</script>
";
        }
        // line 379
        yield "
";
        return; yield '';
    }

    // line 382
    public function block_after_javascript($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 383
        yield "
  ";
        // line 384
        yield do_action("mailpoet_newsletter_editor_after_javascript");
        yield "

  <script type=\"text/javascript\">
    function renderWithFont(node) {
      if (!node.element) return node.text;
      var \$wrapper = jQuery('<span></span>');
      \$wrapper.css({'font-family': Handlebars.helpers.fontWithFallback(node.element.value)});
      \$wrapper.text(node.text);
      return \$wrapper;
    }
    function fontsSelect(selector) {
      jQuery(selector).select2({
        minimumResultsForSearch: Infinity,
        templateSelection: renderWithFont,
        templateResult: renderWithFont
      });
    }

    var templates = {
      styles: Handlebars.compile(
        jQuery('#newsletter_editor_template_styles').html()
      ),
      save: Handlebars.compile(
        jQuery('#newsletter_editor_template_save').html()
      ),
      heading: Handlebars.compile(
        jQuery('#newsletter_editor_template_heading').html()
      ),
      history: Handlebars.compile(
        jQuery('#newsletter_editor_template_history').html()
      ),

      sidebar: Handlebars.compile(
        jQuery('#newsletter_editor_template_sidebar').html()
      ),
      sidebarContent: Handlebars.compile(
        jQuery('#newsletter_editor_template_sidebar_content').html()
      ),
      sidebarLayout: Handlebars.compile(
        jQuery('#newsletter_editor_template_sidebar_layout').html()
      ),
      sidebarStyles: Handlebars.compile(
        jQuery('#newsletter_editor_template_sidebar_styles').html()
      ),
      newsletterPreview: Handlebars.compile(
        jQuery('#newsletter_editor_template_newsletter_preview').html()
      ),

      genericBlockTools: Handlebars.compile(
        jQuery('#newsletter_editor_template_tools_generic').html()
      ),

      containerBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_container_block').html()
      ),
      containerEmpty: Handlebars.compile(
        jQuery('#newsletter_editor_template_container_block_empty').html()
      ),
      oneColumnLayoutInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_container_one_column_widget').html()
      ),
      twoColumnLayoutInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_container_two_column_widget').html()
      ),
      twoColumn12LayoutInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_container_two_column_12_widget').html()
      ),
      twoColumn21LayoutInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_container_two_column_21_widget').html()
      ),
      threeColumnLayoutInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_container_three_column_widget').html()
      ),
      containerBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_container_settings').html()
      ),
      containerBlockColumnSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_container_column_settings').html()
      ),

      buttonBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_button_block').html()
      ),
      buttonInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_button_widget').html()
      ),
      buttonBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_button_settings').html()
      ),

      dividerBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_divider_block').html()
      ),
      dividerInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_divider_widget').html()
      ),
      dividerBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_divider_settings').html()
      ),

      footerBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_footer_block').html()
      ),
      footerInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_footer_widget').html()
      ),
      footerBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_footer_settings').html()
      ),

      headerBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_header_block').html()
      ),
      headerInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_header_widget').html()
      ),
      headerBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_header_settings').html()
      ),

      imageBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_image_block').html()
      ),
      imageInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_image_widget').html()
      ),
      imageBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_image_settings').html()
      ),

      socialBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_social_block').html()
      ),
      socialIconBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_social_block_icon').html()
      ),
      socialInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_social_widget').html()
      ),
      socialBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_social_settings').html()
      ),
      socialSettingsIconSelector: Handlebars.compile(
        jQuery('#newsletter_editor_template_social_settings_icon_selector').html()
      ),
      socialSettingsIcon: Handlebars.compile(
        jQuery('#newsletter_editor_template_social_settings_icon').html()
      ),
      socialSettingsStyles: Handlebars.compile(
        jQuery('#newsletter_editor_template_social_settings_styles').html()
      ),

      spacerBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_spacer_block').html()
      ),
      spacerInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_spacer_widget').html()
      ),
      spacerBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_spacer_settings').html()
      ),

      automatedLatestContentBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_automated_latest_content_block').html()
      ),
      automatedLatestContentInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_automated_latest_content_widget').html()
      ),
      automatedLatestContentBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_automated_latest_content_settings').html()
      ),

      automatedLatestContentLayoutBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_automated_latest_content_layout_block').html()
      ),
      automatedLatestContentLayoutInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_automated_latest_content_layout_widget').html()
      ),
      automatedLatestContentLayoutBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_automated_latest_content_layout_settings').html()
      ),

      postsBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_posts_block').html()
      ),
      postsInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_posts_widget').html()
      ),
      postsBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_posts_settings').html()
      ),
      postSelectionPostsBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_posts_settings_selection').html()
      ),
      emptyPostPostsBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_posts_settings_selection_empty').html()
      ),
      singlePostPostsBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_posts_settings_single_post').html()
      ),
      displayOptionsPostsBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_posts_settings_display_options').html()
      ),

      productsBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_products_block').html()
      ),
      productsInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_products_widget').html()
      ),
      productsBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_products_settings').html()
      ),
      postSelectionProductsBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_products_settings_selection').html()
      ),
      emptyPostProductsBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_products_settings_selection_empty').html()
      ),
      singlePostProductsBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_products_settings_single_post').html()
      ),
      displayOptionsProductsBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_products_settings_display_options').html()
      ),

      abandonedCartContentBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_acc_block').html()
      ),
      abandonedCartContentInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_acc_widget').html()
      ),
      abandonedCartContentBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_acc_settings').html()
      ),
      displayOptionsAbandonedCartContentBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_acc_settings_display_options').html()
      ),

      textBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_text_block').html()
      ),
      textInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_text_widget').html()
      ),
      textBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_text_settings').html()
      ),

      woocommerceNewAccount: Handlebars.compile(
        jQuery('#newsletter_editor_template_woocommerce_new_account_content').html()
      ),
      woocommerceProcessingOrder: Handlebars.compile(
        jQuery('#newsletter_editor_template_woocommerce_processing_order_content').html()
      ),
      woocommerceCompletedOrder: Handlebars.compile(
        jQuery('#newsletter_editor_template_woocommerce_completed_order_content').html()
      ),
      woocommerceCustomerNote: Handlebars.compile(
        jQuery('#newsletter_editor_template_woocommerce_customer_note_content').html()
      ),
      woocommerceContentInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_woocommerce_content_widget').html()
      ),

      woocommerceHeadingBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_woocommerce_heading_block').html()
      ),
      woocommerceHeadingInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_woocommerce_heading_widget').html()
      ),

      unknownBlockFallbackBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_unknown_block_fallback_block').html()
      ),
      unknownBlockFallbackInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_unknown_block_fallback_widget').html()
      ),
      couponBlock: Handlebars.compile(
        jQuery('#newsletter_editor_template_coupon_block').html()
      ),
      couponInsertion: Handlebars.compile(
        jQuery('#newsletter_editor_template_coupon_widget').html()
      ),
      couponBlockSettings: Handlebars.compile(
        jQuery('#newsletter_editor_template_coupon_settings').html()
      ),
    };

    var currentUserEmail = '";
        // line 673
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["current_wp_user"] ?? null), "user_email", [], "any", false, false, false, 673), "html", null, true);
        yield "';
    var mailpoet_product_categories = ";
        // line 674
        yield json_encode(($context["product_categories"] ?? null));
        yield ";
    var mailpoet_products = ";
        // line 675
        yield json_encode(($context["products"] ?? null));
        yield ";
    var mailpoet_original_template_body = ";
        // line 676
        yield json_encode(($context["original_template_body"] ?? null));
        yield ";

    var config = {
      availableStyles: {
        textSizes: [
          '9px', '10px', '11px', '12px', '13px', '14px', '15px', '16px',
          '17px', '18px', '19px', '20px', '21px', '22px', '23px', '24px'
        ],
        headingSizes: [
          '10px', '12px', '14px', '16px', '18px', '20px', '22px', '24px',
          '26px', '30px', '36px', '40px'
        ],
        lineHeights: [
          '1.0',
          '1.2',
          '1.4',
          '1.6',
          '1.8',
          '2.0',
        ],
        fonts: {
          standard: [
            'Arial',
            'Comic Sans MS',
            'Courier New',
            'Georgia',
            'Lucida',
            'Tahoma',
            'Times New Roman',
            'Trebuchet MS',
            'Verdana'
            ";
        // line 707
        if (($context["customFontsEnabled"] ?? null)) {
            // line 708
            yield "          ],
          custom: [
            'Arvo',
            'Lato',
            'Lora',
            'Merriweather',
            'Merriweather Sans',
            'Noticia Text',
            'Open Sans',
            'Playfair Display',
            'Roboto',
            'Source Sans Pro',
            'Oswald',
            'Raleway',
            'Permanent Marker',
            'Pacifico',
            ";
        }
        // line 725
        yield "          ]
        },
        socialIconSets: {
          'default': {
            'custom': '";
        // line 729
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 731
        yield "',
            'facebook': '";
        // line 732
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Facebook.png");
        // line 734
        yield "',
            'twitter': '";
        // line 735
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Twitter.png");
        // line 737
        yield "',
            'youtube': '";
        // line 738
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Youtube.png");
        // line 740
        yield "',
            'website': '";
        // line 741
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Website.png");
        // line 743
        yield "',
            'email': '";
        // line 744
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Email.png");
        // line 746
        yield "',
            'instagram': '";
        // line 747
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Instagram.png");
        // line 749
        yield "',
            'pinterest': '";
        // line 750
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Pinterest.png");
        // line 752
        yield "',
            'linkedin': '";
        // line 753
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/LinkedIn.png");
        // line 755
        yield "'
          },
          'grey': {
            'custom': '";
        // line 758
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 760
        yield "',
            'facebook': '";
        // line 761
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Facebook.png");
        // line 763
        yield "',
            'twitter': '";
        // line 764
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Twitter.png");
        // line 766
        yield "',
            'youtube': '";
        // line 767
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Youtube.png");
        // line 769
        yield "',
            'website': '";
        // line 770
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Website.png");
        // line 772
        yield "',
            'email': '";
        // line 773
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Email.png");
        // line 775
        yield "',
            'instagram': '";
        // line 776
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Instagram.png");
        // line 778
        yield "',
            'pinterest': '";
        // line 779
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Pinterest.png");
        // line 781
        yield "',
            'linkedin': '";
        // line 782
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/LinkedIn.png");
        // line 784
        yield "',
          },
          'white': {
              'custom': '";
        // line 787
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 789
        yield "',
              'facebook': '";
        // line 790
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Facebook.png");
        // line 792
        yield "',
              'twitter': '";
        // line 793
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Twitter.png");
        // line 795
        yield "',
              'youtube': '";
        // line 796
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Youtube.png");
        // line 798
        yield "',
              'website': '";
        // line 799
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Website.png");
        // line 801
        yield "',
              'email': '";
        // line 802
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Email.png");
        // line 804
        yield "',
              'instagram': '";
        // line 805
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Instagram.png");
        // line 807
        yield "',
              'pinterest': '";
        // line 808
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Pinterest.png");
        // line 810
        yield "',
              'linkedin': '";
        // line 811
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/LinkedIn.png");
        // line 813
        yield "',
          },
          'black': {
              'custom': '";
        // line 816
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 818
        yield "',
              'facebook': '";
        // line 819
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Facebook.png");
        // line 821
        yield "',
              'twitter': '";
        // line 822
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Twitter.png");
        // line 824
        yield "',
              'youtube': '";
        // line 825
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Youtube.png");
        // line 827
        yield "',
              'website': '";
        // line 828
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Website.png");
        // line 830
        yield "',
              'email': '";
        // line 831
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Email.png");
        // line 833
        yield "',
              'instagram': '";
        // line 834
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Instagram.png");
        // line 836
        yield "',
              'pinterest': '";
        // line 837
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Pinterest.png");
        // line 839
        yield "',
              'linkedin': '";
        // line 840
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/LinkedIn.png");
        // line 842
        yield "',
          },
          'circles': {
            'custom': '";
        // line 845
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 847
        yield "',
            'facebook': '";
        // line 848
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Facebook.png");
        // line 850
        yield "',
            'twitter': '";
        // line 851
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Twitter.png");
        // line 853
        yield "',
            'youtube': '";
        // line 854
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Youtube.png");
        // line 856
        yield "',
            'website': '";
        // line 857
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Website.png");
        // line 859
        yield "',
            'email': '";
        // line 860
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Email.png");
        // line 862
        yield "',
            'instagram': '";
        // line 863
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Instagram.png");
        // line 865
        yield "',
            'pinterest': '";
        // line 866
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Pinterest.png");
        // line 868
        yield "',
            'linkedin': '";
        // line 869
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/LinkedIn.png");
        // line 871
        yield "',
          },
          'full-flat-roundrect': {
            'custom': '";
        // line 874
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 876
        yield "',
            'facebook': '";
        // line 877
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Facebook.png");
        // line 879
        yield "',
            'twitter': '";
        // line 880
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Twitter.png");
        // line 882
        yield "',
            'youtube': '";
        // line 883
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Youtube.png");
        // line 885
        yield "',
            'website': '";
        // line 886
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Website.png");
        // line 888
        yield "',
            'email': '";
        // line 889
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Email.png");
        // line 891
        yield "',
            'instagram': '";
        // line 892
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Instagram.png");
        // line 894
        yield "',
            'pinterest': '";
        // line 895
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Pinterest.png");
        // line 897
        yield "',
            'linkedin': '";
        // line 898
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/LinkedIn.png");
        // line 900
        yield "',
          },
          'full-gradient-square': {
            'custom': '";
        // line 903
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 905
        yield "',
            'facebook': '";
        // line 906
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Facebook.png");
        // line 908
        yield "',
            'twitter': '";
        // line 909
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Twitter.png");
        // line 911
        yield "',
            'youtube': '";
        // line 912
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Youtube.png");
        // line 914
        yield "',
            'website': '";
        // line 915
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Website.png");
        // line 917
        yield "',
            'email': '";
        // line 918
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Email.png");
        // line 920
        yield "',
            'instagram': '";
        // line 921
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Instagram.png");
        // line 923
        yield "',
            'pinterest': '";
        // line 924
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Pinterest.png");
        // line 926
        yield "',
            'linkedin': '";
        // line 927
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/LinkedIn.png");
        // line 929
        yield "',
          },
          'full-symbol-color': {
            'custom': '";
        // line 932
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 934
        yield "',
            'facebook': '";
        // line 935
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Facebook.png");
        // line 937
        yield "',
            'twitter': '";
        // line 938
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Twitter.png");
        // line 940
        yield "',
            'youtube': '";
        // line 941
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Youtube.png");
        // line 943
        yield "',
            'website': '";
        // line 944
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Website.png");
        // line 946
        yield "',
            'email': '";
        // line 947
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Email.png");
        // line 949
        yield "',
            'instagram': '";
        // line 950
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Instagram.png");
        // line 952
        yield "',
            'pinterest': '";
        // line 953
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Pinterest.png");
        // line 955
        yield "',
            'linkedin': '";
        // line 956
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/LinkedIn.png");
        // line 958
        yield "',
          },
          'full-symbol-black': {
            'custom': '";
        // line 961
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 963
        yield "',
            'facebook': '";
        // line 964
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Facebook.png");
        // line 966
        yield "',
            'twitter': '";
        // line 967
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Twitter.png");
        // line 969
        yield "',
            'youtube': '";
        // line 970
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Youtube.png");
        // line 972
        yield "',
            'website': '";
        // line 973
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Website.png");
        // line 975
        yield "',
            'email': '";
        // line 976
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Email.png");
        // line 978
        yield "',
            'instagram': '";
        // line 979
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Instagram.png");
        // line 981
        yield "',
            'pinterest': '";
        // line 982
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Pinterest.png");
        // line 984
        yield "',
            'linkedin': '";
        // line 985
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/LinkedIn.png");
        // line 987
        yield "',
          },
          'full-symbol-grey': {
            'custom': '";
        // line 990
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 992
        yield "',
            'facebook': '";
        // line 993
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Facebook.png");
        // line 995
        yield "',
            'twitter': '";
        // line 996
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Twitter.png");
        // line 998
        yield "',
            'youtube': '";
        // line 999
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Youtube.png");
        // line 1001
        yield "',
            'website': '";
        // line 1002
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Website.png");
        // line 1004
        yield "',
            'email': '";
        // line 1005
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Email.png");
        // line 1007
        yield "',
            'instagram': '";
        // line 1008
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Instagram.png");
        // line 1010
        yield "',
            'pinterest': '";
        // line 1011
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Pinterest.png");
        // line 1013
        yield "',
            'linkedin': '";
        // line 1014
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/LinkedIn.png");
        // line 1016
        yield "',
          },
        },
        dividers: [
          'hidden',
          'dotted',
          'dashed',
          'solid',
          'double',
          'groove',
          'ridge'
        ]
      },
      socialIcons: {
        'facebook': {
          title: 'Facebook',
          linkFieldName: '";
        // line 1032
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Link"), "js"), "html", null, true);
        yield "',
          defaultLink: 'https://www.facebook.com/',
        },
        'twitter': {
          title: 'Twitter / X',
          linkFieldName: '";
        // line 1037
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Link"), "js"), "html", null, true);
        yield "',
          defaultLink: 'https://x.com/',
        },
        'youtube': {
          title: 'YouTube',
          linkFieldName: '";
        // line 1042
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Link"), "js"), "html", null, true);
        yield "',
          defaultLink: 'https://www.youtube.com/',
        },
        'website': {
          title: '";
        // line 1046
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Website"), "js"), "html", null, true);
        yield "',
          linkFieldName: '";
        // line 1047
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Link"), "js"), "html", null, true);
        yield "',
          defaultLink: '',
        },
        'email': {
          title: '";
        // line 1051
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Email"), "js"), "html", null, true);
        yield "',
          linkFieldName: '";
        // line 1052
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Email"), "js"), "html", null, true);
        yield "',
          defaultLink: '',
        },
        'instagram': {
          title: 'Instagram',
          linkFieldName: '";
        // line 1057
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Link"), "js"), "html", null, true);
        yield "',
          defaultLink: 'https://www.instagram.com/',
        },
        'pinterest': {
          title: 'Pinterest',
          linkFieldName: '";
        // line 1062
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Link"), "js"), "html", null, true);
        yield "',
          defaultLink: 'https://www.pinterest.com/',
        },
        'linkedin': {
          title: 'LinkedIn',
          linkFieldName: '";
        // line 1067
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Link"), "js"), "html", null, true);
        yield "',
          defaultLink: 'https://www.linkedin.com/',
        },
        'custom': {
          title: '";
        // line 1071
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Custom"), "js"), "html", null, true);
        yield "',
          linkFieldName: '";
        // line 1072
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Link"), "js"), "html", null, true);
        yield "',
          defaultLink: '',
        },
      },
      blockDefaults: {
        abandonedCartContent: {
          amount: '2',
          withLayout: true,
          contentType: 'product',
          postStatus: 'publish', // 'draft'|'pending'|'publish'
          inclusionType: 'include', // 'include'|'exclude'
          displayType: 'excerpt', // 'excerpt'|'full'|'titleOnly'
          titleFormat: 'h1', // 'h1'|'h2'|'h3'
          titleAlignment: 'left', // 'left'|'center'|'right'
          titleIsLink: false, // false|true
          imageFullWidth: false, // true|false
          featuredImagePosition: 'alternate', // 'centered'|'left'|'right'|'alternate'|'none',
          pricePosition: 'below', // 'hidden'|'above'|'below'
          readMoreType: 'none', // 'link'|'button'|'none'
          readMoreText: '',
          readMoreButton: {},
          sortBy: 'newest', // 'newest'|'oldest',
          showDivider: true, // true|false
          divider: {
            context: 'abandonedCartContent.divider',
            styles: {
              block: {
                backgroundColor: 'transparent',
                padding: '13px',
                borderStyle: 'solid',
                borderWidth: '3px',
                borderColor: '#aaaaaa',
              },
            },
          },
          backgroundColor: '#ffffff',
          backgroundColorAlternate: '#eeeeee',
        },
        automatedLatestContent: {
          amount: '5',
          withLayout: false,
          contentType: 'post', // 'post'|'page'|'mailpoet_page'
          inclusionType: 'include', // 'include'|'exclude'
          displayType: 'excerpt', // 'excerpt'|'full'|'titleOnly'
          titleFormat: 'h1', // 'h1'|'h2'|'h3'|'ul'
          titleAlignment: 'left', // 'left'|'center'|'right'
          titleIsLink: false, // false|true
          imageFullWidth: false, // true|false
          featuredImagePosition: 'belowTitle', // 'belowTitle'|'aboveTitle'|'none',
          showAuthor: 'no', // 'no'|'aboveText'|'belowText'
          authorPrecededBy: '";
        // line 1122
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Author:"), "js"), "html", null, true);
        yield "',
          showCategories: 'no', // 'no'|'aboveText'|'belowText'
          categoriesPrecededBy: '";
        // line 1124
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Categories:"), "js"), "html", null, true);
        yield "',
          readMoreType: 'button', // 'link'|'button'
          readMoreText: '";
        // line 1126
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Read more"), "js"), "html", null, true);
        yield "',
          readMoreButton: {
            text: '";
        // line 1128
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Read more"), "js"), "html", null, true);
        yield "',
            url: '[postLink]',
            context: 'automatedLatestContent.readMoreButton',
            styles: {
              block: {
                backgroundColor: '#2ea1cd',
                borderColor: '#0074a2',
                borderWidth: '1px',
                borderRadius: '5px',
                borderStyle: 'solid',
                width: '180px',
                lineHeight: '40px',
                fontColor: '#ffffff',
                fontFamily: 'Verdana',
                fontSize: '18px',
                fontWeight: 'normal',
                textAlign: 'center',
              }
            }
          },
          sortBy: 'newest', // 'newest'|'oldest',
          showDivider: true, // true|false
          divider: {
            context: 'automatedLatestContent.divider',
            styles: {
              block: {
                backgroundColor: 'transparent',
                padding: '13px',
                borderStyle: 'solid',
                borderWidth: '3px',
                borderColor: '#aaaaaa',
              },
            },
          },
          backgroundColor: '#ffffff',
          backgroundColorAlternate: '#eeeeee',
        },
        automatedLatestContentLayout: {
          amount: '5',
          withLayout: true,
          contentType: 'post', // 'post'|'page'|'mailpoet_page'
          inclusionType: 'include', // 'include'|'exclude'
          displayType: 'excerpt', // 'excerpt'|'full'|'titleOnly'
          titleFormat: 'h1', // 'h1'|'h2'|'h3'|'ul'
          titleAlignment: 'left', // 'left'|'center'|'right'
          titleIsLink: false, // false|true
          imageFullWidth: false, // true|false
          featuredImagePosition: 'alternate', // 'centered'|'left'|'right'|'alternate'|'none',
          showAuthor: 'no', // 'no'|'aboveText'|'belowText'
          authorPrecededBy: '";
        // line 1177
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Author:"), "js"), "html", null, true);
        yield "',
          showCategories: 'no', // 'no'|'aboveText'|'belowText'
          categoriesPrecededBy: '";
        // line 1179
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Categories:"), "js"), "html", null, true);
        yield "',
          readMoreType: 'button', // 'link'|'button'
          readMoreText: '";
        // line 1181
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Read more"), "js"), "html", null, true);
        yield "',
          readMoreButton: {
            text: '";
        // line 1183
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Read more"), "js"), "html", null, true);
        yield "',
            url: '[postLink]',
            context: 'automatedLatestContentLayout.readMoreButton',
            styles: {
              block: {
                backgroundColor: '#2ea1cd',
                borderColor: '#0074a2',
                borderWidth: '1px',
                borderRadius: '5px',
                borderStyle: 'solid',
                width: '180px',
                lineHeight: '40px',
                fontColor: '#ffffff',
                fontFamily: 'Verdana',
                fontSize: '18px',
                fontWeight: 'normal',
                textAlign: 'center',
              }
            }
          },
          sortBy: 'newest', // 'newest'|'oldest',
          showDivider: true, // true|false
          divider: {
            context: 'automatedLatestContentLayout.divider',
            styles: {
              block: {
                backgroundColor: 'transparent',
                padding: '13px',
                borderStyle: 'solid',
                borderWidth: '3px',
                borderColor: '#aaaaaa',
              },
            },
          },
          backgroundColor: '#ffffff',
          backgroundColorAlternate: '#eeeeee',
        },
        button: {
          text: '";
        // line 1221
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Button"), "js"), "html", null, true);
        yield "',
          url: '',
          styles: {
            block: {
              backgroundColor: '#2ea1cd',
              borderColor: '#0074a2',
              borderWidth: '1px',
              borderRadius: '5px',
              borderStyle: 'solid',
              width: '180px',
              lineHeight: '40px',
              fontColor: '#ffffff',
              fontFamily: 'Verdana',
              fontSize: '18px',
              fontWeight: 'normal',
              textAlign: 'center',
            },
          },
        },
        container: {
          image: {
            src: null,
            display: 'scale',
          },
          styles: {
            block: {
              backgroundColor: 'transparent',
            },
          },
        },
        divider: {
          styles: {
            block: {
              backgroundColor: 'transparent',
              padding: '13px',
              borderStyle: 'solid',
              borderWidth: '3px',
              borderColor: '#aaaaaa',
            },
          },
        },
        footer: {
          text: '<p><a href=\"[link:subscription_unsubscribe_url]\">";
        // line 1263
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Unsubscribe");
        yield "</a> | <a href=\"[link:subscription_manage_url]\">";
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Manage subscription");
        yield "</a><br />";
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Add your postal address here!");
        yield "</p>',
          styles: {
            block: {
              backgroundColor: 'transparent',
            },
            text: {
              fontColor: '#222222',
              fontFamily: 'Arial',
              fontSize: '12px',
              textAlign: 'center',
            },
            link: {
              fontColor: '#6cb7d4',
              textDecoration: 'none',
            },
          },
        },
        image: {
          link: '',
          src: '',
          alt: '";
        // line 1283
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("An image of..."), "js"), "html", null, true);
        yield "',
          fullWidth: false,
          width: '281px',
          height: '190px',
          styles: {
            block: {
              textAlign: 'center',
            },
          },
        },
        posts: {
          amount: '10',
          withLayout: true,
          contentType: 'post', // 'post'|'page'|'mailpoet_page'
          postStatus: 'publish', // 'draft'|'pending'|'private'|'publish'|'future'
          inclusionType: 'include', // 'include'|'exclude'
          displayType: 'excerpt', // 'excerpt'|'full'|'titleOnly'
          titleFormat: 'h1', // 'h1'|'h2'|'h3'|'ul'
          titleAlignment: 'left', // 'left'|'center'|'right'
          titleIsLink: false, // false|true
          imageFullWidth: false, // true|false
          featuredImagePosition: 'alternate', // 'centered'|'left'|'right'|'alternate'|'none',
          showAuthor: 'no', // 'no'|'aboveText'|'belowText'
          authorPrecededBy: '";
        // line 1306
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Author:"), "js"), "html", null, true);
        yield "',
          showCategories: 'no', // 'no'|'aboveText'|'belowText'
          categoriesPrecededBy: '";
        // line 1308
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Categories:"), "js"), "html", null, true);
        yield "',
          readMoreType: 'link', // 'link'|'button'
          readMoreText: '";
        // line 1310
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Read more"), "js"), "html", null, true);
        yield "',
          readMoreButton: {
            text: '";
        // line 1312
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Read more"), "js"), "html", null, true);
        yield "',
            url: '[postLink]',
            context: 'posts.readMoreButton',
            styles: {
              block: {
                backgroundColor: '#2ea1cd',
                borderColor: '#0074a2',
                borderWidth: '1px',
                borderRadius: '5px',
                borderStyle: 'solid',
                width: '180px',
                lineHeight: '40px',
                fontColor: '#ffffff',
                fontFamily: 'Verdana',
                fontSize: '18px',
                fontWeight: 'normal',
                textAlign: 'center',
              },
            },
          },
          sortBy: 'newest', // 'newest'|'oldest',
          showDivider: true, // true|false
          divider: {
            context: 'posts.divider',
            styles: {
              block: {
                backgroundColor: 'transparent',
                padding: '13px',
                borderStyle: 'solid',
                borderWidth: '3px',
                borderColor: '#aaaaaa',
              },
            },
          },
          backgroundColor: '#ffffff',
          backgroundColorAlternate: '#eeeeee',
        },
        products: {
          amount: '10',
          withLayout: true,
          contentType: 'product',
          postStatus: 'publish', // 'draft'|'pending'|'publish'
          inclusionType: 'include', // 'include'|'exclude'
          displayType: 'excerpt', // 'excerpt'|'full'|'titleOnly'
          titleFormat: 'h1', // 'h1'|'h2'|'h3'
          titleAlignment: 'left', // 'left'|'center'|'right'
          titleIsLink: false, // false|true
          imageFullWidth: false, // true|false
          featuredImagePosition: 'alternate', // 'centered'|'left'|'right'|'alternate'|'none',
          pricePosition: 'below', // 'hidden'|'above'|'below'
          readMoreType: 'link', // 'link'|'button'
          readMoreText: '";
        // line 1363
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translateWithContext("Buy now", "Text of a button which links to an ecommerce product page"), "js"), "html", null, true);
        yield "',
          readMoreButton: {
            text: '";
        // line 1365
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translateWithContext("Buy now", "Text of a button which links to an ecommerce product page"), "js"), "html", null, true);
        yield "',
            url: '[postLink]',
            context: 'posts.readMoreButton',
            styles: {
              block: {
                backgroundColor: '#2ea1cd',
                borderColor: '#0074a2',
                borderWidth: '1px',
                borderRadius: '5px',
                borderStyle: 'solid',
                width: '180px',
                lineHeight: '40px',
                fontColor: '#ffffff',
                fontFamily: 'Verdana',
                fontSize: '18px',
                fontWeight: 'normal',
                textAlign: 'center',
              },
            },
          },
          sortBy: 'newest', // 'newest'|'oldest',
          showDivider: true, // true|false
          divider: {
            context: 'posts.divider',
            styles: {
              block: {
                backgroundColor: 'transparent',
                padding: '13px',
                borderStyle: 'solid',
                borderWidth: '3px',
                borderColor: '#aaaaaa',
              },
            },
          },
          backgroundColor: '#ffffff',
          backgroundColorAlternate: '#eeeeee',
        },
        social: {
          iconSet: 'default',
          styles: {
            block: {
              textAlign: 'center'
            }
          },
          icons: [
          {
            type: 'socialIcon',
            iconType: 'facebook',
            link: 'http://www.facebook.com',
            image: '";
        // line 1414
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Facebook.png");
        // line 1416
        yield "',
            height: '32px',
            width: '32px',
            text: '";
        // line 1419
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Facebook"), "js"), "html", null, true);
        yield "',
          },
          {
            type: 'socialIcon',
            iconType: 'twitter',
            link: 'http://www.twitter.com',
            image: '";
        // line 1425
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Twitter.png");
        // line 1427
        yield "',
            height: '32px',
            width: '32px',
            text: '";
        // line 1430
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Twitter"), "js"), "html", null, true);
        yield "',
          },
          ],
        },
        spacer: {
          styles: {
            block: {
              backgroundColor: 'transparent',
              height: '40px',
            },
          },
        },
        text: {
          text: '";
        // line 1443
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Edit this to insert text."), "js"), "html", null, true);
        yield "',
        },
        header: {
          text: '<a href=\"[link:newsletter_view_in_browser_url]\">";
        // line 1446
        yield $this->extensions['MailPoet\Twig\I18n']->translate("View this in your browser.");
        yield "</a>',
          styles: {
            block: {
              backgroundColor: 'transparent',
            },
            text: {
              fontColor: '#222222',
              fontFamily: 'Arial',
              fontSize: '12px',
              textAlign: 'center',
            },
            link: {
              fontColor: '#6cb7d4',
              textDecoration: 'underline',
            },
          },
        },
        woocommerceHeading: {
          contents: ";
        // line 1464
        yield json_encode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "email_headings", [], "any", false, false, false, 1464));
        yield ",
        },
        coupon: {
            type: 'coupon',
            discountType: ";
        // line 1468
        yield json_encode(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "coupon", [], "any", false, false, false, 1468), "defaults", [], "any", false, false, false, 1468), "discountType", [], "any", false, false, false, 1468));
        yield ",
            code: ";
        // line 1469
        yield json_encode(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "coupon", [], "any", false, false, false, 1469), "defaults", [], "any", false, false, false, 1469), "code", [], "any", false, false, false, 1469));
        yield ",
            amountMax: ";
        // line 1470
        yield json_encode(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "coupon", [], "any", false, false, false, 1470), "defaults", [], "any", false, false, false, 1470), "amountMax", [], "any", false, false, false, 1470));
        yield ",
            amount: 10,
            expiryDay: 10,
            styles: {
              block: {
                backgroundColor: '#ffffff',
                borderColor: '#000000',
                borderWidth: '1px',
                borderRadius: '5px',
                borderStyle: 'solid',
                width: '200px',
                lineHeight: '40px',
                fontColor: '#000000',
                fontFamily: 'Verdana',
                fontSize: '18px',
                fontWeight: 'normal',
                textAlign: 'center',
              },
          },
        },
      },
      shortcodes: ";
        // line 1491
        yield json_encode(($context["shortcodes"] ?? null));
        yield ",
      sidepanelWidth: '331px',
      newsletterPreview: {
        width: '1024px',
        height: '768px',
        previewTypeLocalStorageKey: 'newsletter_editor.preview_type'
      },
      validation: {
        validateUnsubscribeLinkPresent: ";
        // line 1499
        yield ((((($context["mss_active"] ?? null) && (($context["is_wc_transactional_email"] ?? null) != true)) && (($context["is_confirmation_email_template"] ?? null) != true))) ? ("true") : ("false"));
        yield ",
        validateReEngageLinkPresent: ";
        // line 1500
        yield ((((($context["mss_active"] ?? null) && (($context["is_wc_transactional_email"] ?? null) != true)) && (($context["is_confirmation_email_template"] ?? null) != true))) ? ("true") : ("false"));
        yield ",
        validateActivationLinkIsPresent: ";
        // line 1501
        yield ((($context["is_confirmation_email_template"] ?? null)) ? ("true") : ("false"));
        yield ",
      },
      urls: {
        send: '";
        // line 1504
        yield admin_url(("admin.php?page=mailpoet-newsletters#/send/" . intval($this->extensions['MailPoet\Twig\Functions']->params("id"))));
        yield "',
        imageMissing: '";
        // line 1505
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/image-missing.svg");
        // line 1507
        yield "',
      },
      currentUserId: '";
        // line 1509
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["current_wp_user"] ?? null), "wp_user_id", [], "any", false, false, false, 1509), "html", null, true);
        yield "',
      mtaMethod: '";
        // line 1510
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape((($__internal_compile_0 = (($__internal_compile_1 = ($context["settings"] ?? null)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["mta"] ?? null) : null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["method"] ?? null) : null), "html", null, true);
        yield "',
      woocommerceCustomizerEnabled: ";
        // line 1511
        yield ((CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "customizer_enabled", [], "any", false, false, false, 1511)) ? ("true") : ("false"));
        yield ",
      confirmationEmailCustomizerEnabled: ";
        // line 1512
        yield ((($context["is_confirmation_email_customizer_enabled"] ?? null)) ? ("true") : ("false"));
        yield ",
      ";
        // line 1513
        if (($context["is_wc_transactional_email"] ?? null)) {
            // line 1514
            yield "      overrideGlobalStyles: {
        text: {
          fontColor: ";
            // line 1516
            yield json_encode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "text_color", [], "any", false, false, false, 1516));
            yield ",
        },
        h1: {
          fontColor: ";
            // line 1519
            yield json_encode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "base_color", [], "any", false, false, false, 1519));
            yield ",
        },
        h2: {
          fontColor: ";
            // line 1522
            yield json_encode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "base_color", [], "any", false, false, false, 1522));
            yield ",
        },
        h3: {
          fontColor: ";
            // line 1525
            yield json_encode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "base_color", [], "any", false, false, false, 1525));
            yield ",
        },
        link: {
          fontColor: ";
            // line 1528
            yield json_encode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "link_color", [], "any", false, false, false, 1528));
            yield ",
        },
        wrapper: {
          backgroundColor: ";
            // line 1531
            yield json_encode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "body_background_color", [], "any", false, false, false, 1531));
            yield ",
        },
        body: {
          backgroundColor: ";
            // line 1534
            yield json_encode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "background_color", [], "any", false, false, false, 1534));
            yield ",
        },
        woocommerce: {
          brandingColor: ";
            // line 1537
            yield json_encode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "base_color", [], "any", false, false, false, 1537));
            yield ",
          headingFontColor: ";
            // line 1538
            yield json_encode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "base_text_color", [], "any", false, false, false, 1538));
            yield ",
          isSavedWithUpdatedStyles: true,
        },
      },
      hiddenWidgets: ['automatedLatestContentLayout', 'header', 'footer', 'posts', 'products'],
      ";
        }
        // line 1544
        yield "      ";
        if (($context["is_confirmation_email_template"] ?? null)) {
            // line 1545
            yield "      hiddenWidgets: ['automatedLatestContentLayout', 'header', 'footer', 'posts', 'products'],
      ";
        }
        // line 1547
        yield "      coupon: ";
        yield json_encode(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "coupon", [], "any", false, false, false, 1547), "config", [], "any", false, false, false, 1547));
        yield ",
    };

    wp.hooks.doAction('mailpoet_newsletters_editor_initialize', config);

  </script>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "newsletter/editor.html";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  2091 => 1547,  2087 => 1545,  2084 => 1544,  2075 => 1538,  2071 => 1537,  2065 => 1534,  2059 => 1531,  2053 => 1528,  2047 => 1525,  2041 => 1522,  2035 => 1519,  2029 => 1516,  2025 => 1514,  2023 => 1513,  2019 => 1512,  2015 => 1511,  2011 => 1510,  2007 => 1509,  2003 => 1507,  2001 => 1505,  1997 => 1504,  1991 => 1501,  1987 => 1500,  1983 => 1499,  1972 => 1491,  1948 => 1470,  1944 => 1469,  1940 => 1468,  1933 => 1464,  1912 => 1446,  1906 => 1443,  1890 => 1430,  1885 => 1427,  1883 => 1425,  1874 => 1419,  1869 => 1416,  1867 => 1414,  1815 => 1365,  1810 => 1363,  1756 => 1312,  1751 => 1310,  1746 => 1308,  1741 => 1306,  1715 => 1283,  1688 => 1263,  1643 => 1221,  1602 => 1183,  1597 => 1181,  1592 => 1179,  1587 => 1177,  1535 => 1128,  1530 => 1126,  1525 => 1124,  1520 => 1122,  1467 => 1072,  1463 => 1071,  1456 => 1067,  1448 => 1062,  1440 => 1057,  1432 => 1052,  1428 => 1051,  1421 => 1047,  1417 => 1046,  1410 => 1042,  1402 => 1037,  1394 => 1032,  1376 => 1016,  1374 => 1014,  1371 => 1013,  1369 => 1011,  1366 => 1010,  1364 => 1008,  1361 => 1007,  1359 => 1005,  1356 => 1004,  1354 => 1002,  1351 => 1001,  1349 => 999,  1346 => 998,  1344 => 996,  1341 => 995,  1339 => 993,  1336 => 992,  1334 => 990,  1329 => 987,  1327 => 985,  1324 => 984,  1322 => 982,  1319 => 981,  1317 => 979,  1314 => 978,  1312 => 976,  1309 => 975,  1307 => 973,  1304 => 972,  1302 => 970,  1299 => 969,  1297 => 967,  1294 => 966,  1292 => 964,  1289 => 963,  1287 => 961,  1282 => 958,  1280 => 956,  1277 => 955,  1275 => 953,  1272 => 952,  1270 => 950,  1267 => 949,  1265 => 947,  1262 => 946,  1260 => 944,  1257 => 943,  1255 => 941,  1252 => 940,  1250 => 938,  1247 => 937,  1245 => 935,  1242 => 934,  1240 => 932,  1235 => 929,  1233 => 927,  1230 => 926,  1228 => 924,  1225 => 923,  1223 => 921,  1220 => 920,  1218 => 918,  1215 => 917,  1213 => 915,  1210 => 914,  1208 => 912,  1205 => 911,  1203 => 909,  1200 => 908,  1198 => 906,  1195 => 905,  1193 => 903,  1188 => 900,  1186 => 898,  1183 => 897,  1181 => 895,  1178 => 894,  1176 => 892,  1173 => 891,  1171 => 889,  1168 => 888,  1166 => 886,  1163 => 885,  1161 => 883,  1158 => 882,  1156 => 880,  1153 => 879,  1151 => 877,  1148 => 876,  1146 => 874,  1141 => 871,  1139 => 869,  1136 => 868,  1134 => 866,  1131 => 865,  1129 => 863,  1126 => 862,  1124 => 860,  1121 => 859,  1119 => 857,  1116 => 856,  1114 => 854,  1111 => 853,  1109 => 851,  1106 => 850,  1104 => 848,  1101 => 847,  1099 => 845,  1094 => 842,  1092 => 840,  1089 => 839,  1087 => 837,  1084 => 836,  1082 => 834,  1079 => 833,  1077 => 831,  1074 => 830,  1072 => 828,  1069 => 827,  1067 => 825,  1064 => 824,  1062 => 822,  1059 => 821,  1057 => 819,  1054 => 818,  1052 => 816,  1047 => 813,  1045 => 811,  1042 => 810,  1040 => 808,  1037 => 807,  1035 => 805,  1032 => 804,  1030 => 802,  1027 => 801,  1025 => 799,  1022 => 798,  1020 => 796,  1017 => 795,  1015 => 793,  1012 => 792,  1010 => 790,  1007 => 789,  1005 => 787,  1000 => 784,  998 => 782,  995 => 781,  993 => 779,  990 => 778,  988 => 776,  985 => 775,  983 => 773,  980 => 772,  978 => 770,  975 => 769,  973 => 767,  970 => 766,  968 => 764,  965 => 763,  963 => 761,  960 => 760,  958 => 758,  953 => 755,  951 => 753,  948 => 752,  946 => 750,  943 => 749,  941 => 747,  938 => 746,  936 => 744,  933 => 743,  931 => 741,  928 => 740,  926 => 738,  923 => 737,  921 => 735,  918 => 734,  916 => 732,  913 => 731,  911 => 729,  905 => 725,  886 => 708,  884 => 707,  850 => 676,  846 => 675,  842 => 674,  838 => 673,  546 => 384,  543 => 383,  539 => 382,  533 => 379,  525 => 373,  523 => 372,  517 => 369,  513 => 368,  509 => 367,  479 => 340,  476 => 339,  472 => 338,  466 => 335,  464 => 332,  461 => 331,  459 => 328,  456 => 327,  454 => 324,  451 => 323,  449 => 320,  446 => 319,  444 => 316,  441 => 315,  439 => 312,  436 => 311,  434 => 308,  431 => 307,  429 => 304,  426 => 303,  424 => 300,  421 => 299,  419 => 296,  416 => 295,  414 => 292,  411 => 291,  409 => 288,  406 => 287,  404 => 284,  401 => 283,  399 => 280,  396 => 279,  394 => 276,  391 => 275,  389 => 272,  386 => 271,  384 => 268,  381 => 267,  379 => 264,  376 => 263,  374 => 260,  371 => 259,  369 => 256,  366 => 255,  364 => 252,  361 => 251,  359 => 248,  356 => 247,  354 => 244,  351 => 243,  349 => 240,  346 => 239,  344 => 236,  341 => 235,  339 => 232,  336 => 231,  334 => 228,  331 => 227,  329 => 224,  326 => 223,  324 => 220,  321 => 219,  319 => 216,  316 => 215,  314 => 212,  311 => 211,  309 => 208,  306 => 207,  304 => 204,  301 => 203,  299 => 200,  296 => 199,  294 => 196,  291 => 195,  289 => 192,  286 => 191,  284 => 188,  281 => 187,  279 => 184,  276 => 183,  274 => 180,  271 => 179,  269 => 176,  266 => 175,  264 => 172,  261 => 171,  259 => 168,  256 => 167,  254 => 164,  251 => 163,  249 => 160,  246 => 159,  244 => 156,  241 => 155,  239 => 152,  236 => 151,  234 => 148,  231 => 147,  229 => 144,  226 => 143,  224 => 140,  221 => 139,  219 => 136,  216 => 135,  214 => 132,  211 => 131,  209 => 128,  206 => 127,  204 => 124,  201 => 123,  199 => 120,  196 => 119,  194 => 116,  191 => 115,  189 => 112,  186 => 111,  184 => 108,  181 => 107,  179 => 104,  176 => 103,  174 => 100,  171 => 99,  169 => 96,  166 => 95,  164 => 92,  161 => 91,  159 => 88,  156 => 87,  154 => 84,  151 => 83,  149 => 80,  146 => 79,  144 => 76,  141 => 75,  139 => 72,  136 => 71,  134 => 68,  131 => 67,  129 => 64,  126 => 63,  124 => 60,  121 => 59,  119 => 56,  116 => 55,  114 => 52,  111 => 51,  109 => 48,  106 => 47,  104 => 44,  101 => 43,  99 => 40,  96 => 39,  94 => 36,  91 => 35,  89 => 32,  86 => 31,  84 => 28,  81 => 27,  79 => 24,  76 => 23,  74 => 20,  71 => 19,  69 => 16,  66 => 15,  64 => 12,  61 => 11,  59 => 8,  56 => 7,  53 => 4,  49 => 3,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "newsletter/editor.html", "/home/circleci/mailpoet/mailpoet/views/newsletter/editor.html");
    }
}
