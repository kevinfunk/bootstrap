<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

/**
 * Due to BC reasons, this class cannot be moved.
 *
 * @todo Move namespace up one.
 */

/**
 * The "cdn_custom_js" theme setting.
 *
 * @ingroup plugins_setting
 *
 * @BootstrapSetting(
 *   cdn_provider = "custom",
 *   id = "cdn_custom_js",
 *   type = "textfield",
 *   weight = 3,
 *   title = @Translation("Bootstrap JavaScript URL"),
 *   defaultValue = "https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.js",
 *   description = @Translation("It is best to use <code>https</code> protocols here as it will allow more flexibility if the need ever arises."),
 *   groups = {
 *     "cdn" = @Translation("CDN (Content Delivery Network)"),
 *     "cdn_provider" = false,
 *     "custom" = false,
 *   },
 * )
 */
class CdnCustomJs extends CdnProviderBase {}
