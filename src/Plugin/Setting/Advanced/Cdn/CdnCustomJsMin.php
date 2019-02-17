<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

/**
 * The "cdn_custom_js_min" theme setting.
 *
 * @ingroup plugins_setting
 *
 * @BootstrapSetting(
 *   cdn_provider = "custom",
 *   id = "cdn_custom_js_min",
 *   type = "textfield",
 *   weight = 4,
 *   title = @Translation("Minified Bootstrap JavaScript URL"),
 *   defaultValue = "https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js",
 *   description = @Translation("Additionally, you can provide the minimized version of the file. It will be used instead if site aggregation is enabled."),
 *   groups = {
 *     "cdn" = @Translation("CDN (Content Delivery Network)"),
 *     "cdn_provider" = false,
 *     "custom" = false,
 *   },
 * )
 */
class CdnCustomJsMin extends CdnProviderBase {}
