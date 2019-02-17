<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

use Drupal\bootstrap\Bootstrap;
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
    $plugin_id = Html::cleanCssIdentifier($this->provider->getPluginId());
    $setting->setProperty('options', $this->provider->getCdnVersions());
    $setting->setProperty('ajax', [
      'callback' => [get_class($this), 'ajaxProviderCallback'],
      'wrapper' => 'cdn-provider-' . $plugin_id,
    ]);

    if ($this->provider->getCdnExceptions(FALSE)) {
      $setting->setProperty('description', t('Unable to parse the @provider API to determine versions. This version is the default version supplied by the base theme.', [
        '@provider' => $this->provider->getLabel(),
      ]));
    }
    else {
      $setting->setProperty('description', t('These versions are automatically populated by the @provider API. While newer versions may appear over time, it is highly recommended the version that the site was built with stays at that version. Until a newer version has been properly tested for updatability by the site maintainer, you should not arbitrarily "update" just because there is a newer version. This can cause many inconsistencies and undesired effects with an existing site.', [
        '@provider' => $this->provider->getLabel(),
      ]));
    }

    // Check for any CDN failure(s).
    $this->checkCdnExceptions();
  }

}
