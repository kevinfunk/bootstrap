<?php

namespace Drupal\bootstrap\Plugin\Provider;

/**
 * The "custom" CDN Provider plugin.
 *
 * @ingroup plugins_provider
 *
 * @BootstrapProvider(
 *   id = "custom",
 *   label = @Translation("Custom"),
 *   description = @Translation("Allows the use of any CDN Provider by simply injecting any URLs set below.")
 * )
 */
class Custom extends ProviderBase {

  /**
   * {@inheritdoc}
   */
  protected function discoverCdnAssets($version, $theme = NULL) {
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
