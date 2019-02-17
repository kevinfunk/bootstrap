<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

/**
 * Due to BC reasons, this class cannot be moved.
 *
 * @todo Move namespace up one.
 */

/**
 * The "cdn_custom_css_min" theme setting.
 *
 * @ingroup plugins_setting
 *
 * @BootstrapSetting(
 *   cdn_provider = "custom",
 *   id = "cdn_custom_css_min",
 *   type = "textfield",
 *   weight = 2,
 *   title = @Translation("Minified Bootstrap CSS URL"),
 *   defaultValue = "https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css",
 *   description = @Translation("Additionally, you can provide the minimized version of the file. It will be used instead if site aggregation is enabled."),
 *   groups = {
 *     "cdn" = @Translation("CDN (Content Delivery Network)"),
 *     "cdn_provider" = false,
 *     "custom" = false,
 *   },
 * )
 */
class CdnCustomCssMin extends CdnProviderBase {}
