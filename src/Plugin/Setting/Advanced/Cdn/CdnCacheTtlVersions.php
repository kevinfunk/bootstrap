<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

/**
 * Due to BC reasons, this class cannot be moved.
 *
 * @todo Move namespace up one.
 */

/**
 * The "cdn_cache_ttl_versions" theme setting.
 *
 * @ingroup plugins_setting
 *
 * @BootstrapSetting(
 *   id = "cdn_cache_ttl_versions",
 *   type = "select",
 *   weight = 1,
 *   title = @Translation("Available Versions"),
 *   description = @Translation("The length of time to cache the CDN verions before requesting them from the API again."),
 *   defaultValue = \Drupal\bootstrap\Plugin\Provider\ProviderInterface::TTL_ONE_WEEK,
 *   groups = {
 *     "cdn" = @Translation("CDN (Content Delivery Network)"),
 *     "cdn_provider" = false,
 *     "cache" = @Translation("Advanced Cache"),
 *   },
 * )
 */
class CdnCacheTtlVersions extends CdnCacheTtlBase {

  /**
   * {@inheritdoc}
   */
  protected function getSettingAccess() {
    return !!$this->activeProvider->getCdnVersions();
  }

}
