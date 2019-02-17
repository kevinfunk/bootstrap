<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

use Drupal\bootstrap\Utility\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * The "cdn_jsdelivr_theme" theme setting.
 *
 * @ingroup plugins_setting
 *
 * @BootstrapSetting(
 *   cdn_provider = "jsdelivr",
 *   id = "cdn_jsdelivr_theme",
 *   type = "select",
 *   title = @Translation("Theme"),
 *   description = @Translation("Choose the example Bootstrap Theme provided by Bootstrap or one of the Bootswatch themes."),
 *   defaultValue = "bootstrap",
 *   empty_option = @Translation("Bootstrap (default)"),
 *   empty_value = "bootstrap",
 *   groups = {
 *     "cdn" = @Translation("CDN (Content Delivery Network)"),
 *     "cdn_provider" = false,
 *     "jsdelivr" = false,
 *   },
 * )
 */
class CdnJsdelivrTheme extends CdnProviderBase {

  /**
   * {@inheritdoc}
   */
  public function buildCdnProviderElement(Element $setting, FormStateInterface $form_state) {
    $version = $form_state->getValue('cdn_jsdelivr_version', $this->theme->getSetting('cdn_jsdelivr_version'));
    $themes = $this->provider->getCdnThemes($version);

    $options = [];
    foreach ($themes as $theme => $data) {
      $options[$theme] = $data['title'];
    }

    $setting->setProperty('options', $options);
    $setting->setProperty('suffix', '<div id="bootstrap-theme-preview"></div>');

    if ($this->provider->getCdnExceptions(FALSE)) {
      $setting->setProperty('description', t('Unable to parse the @provider API to determine themes. This theme is simply the default CSS supplied by the framework.', [
        '@provider' => $this->provider->getLabel(),
      ]));
    }
    else {
      $setting->setProperty('description', t('Choose the example <a href=":bootstrap_theme" target="_blank">Bootstrap Theme</a> provided by Bootstrap or one of the many, many <a href=":bootswatch" target="_blank">Bootswatch</a> themes!', [
        ':bootswatch' => 'https://bootswatch.com',
        ':bootstrap_theme' => 'https://getbootstrap.com/docs/3.4/examples/theme/',
      ]));
    }

    // Check for any CDN failure(s).
    $this->checkCdnExceptions();
  }

}
