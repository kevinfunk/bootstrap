<?php

namespace Drupal\bootstrap\Plugin\Provider;

/**
 * The "custom" CDN provider plugin.
 *
 * @ingroup plugins_provider
 *
 * @BootstrapProvider(
 *   id = "custom",
 *   label = @Translation("Custom"),
 * )
 */
class Custom extends ProviderBase {

  /**
   * {@inheritdoc}
   */
  protected function discoverCdnAssets($version, $theme) {
    $assets = [];
    foreach (['css', 'js'] as $type) {
      if ($setting = $this->theme->getSetting('cdn_custom_' . $type)) {
        $assets[$type][] = $setting;
      }
      if ($setting = $this->theme->getSetting('cdn_custom_' . $type . '_min')) {
        $assets['min'][$type][] = $setting;
      }
    }
    return $assets;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(array &$definition, $plugin_id) {
    // Intentionally left blank so it doesn't trigger a deprecation warning.
  }

}
