<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

/**
 * Due to BC reasons, this class cannot be moved.
 *
 * @todo Move namespace up one.
 */

/**
 * The "cdn_cache_ttl_assets" theme setting.
 *
 * @ingroup plugins_setting
 *
 * @BootstrapSetting(
 *   id = "cdn_cache_ttl_assets",
 *   type = "select",
 *   weight = 3,
 *   title = @Translation("Asset Processing"),
 *   description = @Translation("The length of time to cache the parsing and processing of CDN assets before rebuilding them again. Note: any change to CDN values automatically triggers a new build."),
 *   defaultValue = \Drupal\bootstrap\Plugin\Provider\ProviderInterface::TTL_FOREVER,
 *   groups = {
 *     "cdn" = @Translation("CDN (Content Delivery Network)"),
 *     "cdn_provider" = false,
 *     "cache" = @Translation("Advanced Cache"),
 *   },
 * )
 */
class CdnCacheTtlAssets extends CdnCacheTtlBase {}
