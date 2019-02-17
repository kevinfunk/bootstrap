<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

/**
 * Due to BC reasons, this class cannot be moved.
 *
 * @todo Move namespace up one.
 */

/**
 * The "cdn_cache_ttl_themes" theme setting.
 *
 * @ingroup plugins_setting
 *
 * @BootstrapSetting(
 *   id = "cdn_cache_ttl_themes",
 *   type = "select",
 *   weight = 2,
 *   title = @Translation("Available Themes"),
 *   description = @Translation("The length of time to cache the CDN themes (if applicable) before requesting them from the API again."),
 *   defaultValue = \Drupal\bootstrap\Plugin\Provider\ProviderInterface::TTL_ONE_MONTH,
 *   groups = {
 *     "cdn" = @Translation("CDN (Content Delivery Network)"),
 *     "cdn_provider" = false,
 *     "cache" = @Translation("Advanced Cache"),
 *   },
 * )
 */
class CdnCacheTtlThemes extends CdnCacheTtlBase {

  /**
   * {@inheritdoc}
   */
  protected function getSettingAccess() {
    return !!$this->activeProvider->getCdnThemes();
  }

}
