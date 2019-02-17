<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

/**
 * Due to BC reasons, this class cannot be moved.
 *
 * @todo Move namespace up one.
 */

use Drupal\bootstrap\Utility\Element;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * The "cdn_jsdelivr_version" theme setting.
 *
 * @ingroup plugins_setting
 *
 * @BootstrapSetting(
 *   cdn_provider = "jsdelivr",
 *   id = "cdn_jsdelivr_version",
 *   type = "select",
 *   weight = -1,
 *   title = @Translation("Version"),
 *   description = @Translation("Choose the Bootstrap version from jsdelivr"),
 *   defaultValue = @BootstrapConstant("Drupal\bootstrap\Bootstrap::FRAMEWORK_VERSION"),
 *   groups = {
 *     "cdn" = @Translation("CDN (Content Delivery Network)"),
 *     "cdn_provider" = false,
 *     "jsdelivr" = false,
 *   },
 * )
 */
class CdnJsdelivrVersion extends CdnProviderBase {

  /**
   * {@inheritdoc}
   */
  public function buildCdnProviderElement(Element $setting, FormStateInterface $form_state) {
    $plugin_id = Html::cleanCssIdentifier($this->settingProvider->getPluginId());
    $setting->setProperty('options', $this->settingProvider->getCdnVersions());
    $setting->setProperty('ajax', [
      'callback' => [get_class($this), 'ajaxProviderCallback'],
      'wrapper' => 'cdn-provider-' . $plugin_id,
    ]);

    $setting->setProperty('smart_description', FALSE);
    if ($this->settingProvider->getCdnExceptions(FALSE)) {
      $setting->setProperty('description', t('Unable to parse the @provider API to determine versions. This version is the default version supplied by the base theme.', [
        '@provider' => $this->settingProvider->getLabel(),
      ]));
    }
    else {
      $setting->setProperty('description', t('These versions are automatically populated by the @provider API.', [
        '@provider' => $this->settingProvider->getLabel(),
      ]));
    }

    // Check for any CDN failure(s).
    $this->checkCdnExceptions();
  }

}
