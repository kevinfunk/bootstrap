<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

/**
 * The "cdn_custom_css" theme setting.
 *
 * @ingroup plugins_setting
 *
 * @BootstrapSetting(
 *   cdn_provider = "custom",
 *   id = "cdn_custom_css",
 *   type = "textfield",
 *   weight = 1,
 *   title = @Translation("Bootstrap CSS URL"),
 *   defaultValue = "https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.css",
 *   description = @Translation("It is best to use <code>https</code> protocols here as it will allow more flexibility if the need ever arises."),
 *   groups = {
 *     "cdn" = @Translation("CDN (Content Delivery Network)"),
 *     "cdn_provider" = false,
 *     "custom" = false,
 *   },
 * )
 */
class CdnCustomCss extends CdnProviderBase {}
