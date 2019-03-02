<?php

namespace Drupal\bootstrap\Plugin\Provider;

/**
 * The "bootstrapcdn" CDN Provider plugin.
 *
 * @ingroup plugins_provider
 *
 * @BootstrapProvider(
 *   id = "bootstrapcdn",
 *   label = @Translation("BootstrapCDN"),
 *   description = @Translation("BootstrapCDN was founded in 2012 by <a href=:DavidHenzel rel=noopener target=_blank>David Henzel</a> and Justin Dorfman at MaxCDN. Today, BootstrapCDN is used by over <a href=:built_with rel=noopener target=_blank>7.9 million sites</a> delivering over 70 billion requests a month.", arguments = {
 *     ":DavidHenzel" = "https://twitter.com/DavidHenzel",
 *     ":built_with" = "https://trends.builtwith.com/cdn/BootstrapCDN",
 *   }),
 * )
 */
class BootstrapCdn extends ApiProviderBase {

  /**
   * {@inheritdoc}
   */
  protected function getApiAssetsUrlTemplate() {
    return 'https://www.bootstrapcdn.com/api/v1/@library/@version';
  }

  /**
   * {@inheritdoc}
   */
  protected function getApiVersionsUrlTemplate() {
    return 'https://www.bootstrapcdn.com/api/v1/@library';
  }

  /**
   * {@inheritdoc}
   */
  protected function getCdnUrlTemplate() {
    return 'https://stackpath.bootstrapcdn.com/@library/@version/@file';
  }

}
