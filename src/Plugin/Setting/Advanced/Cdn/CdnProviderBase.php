<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

/**
 * Due to BC reasons, this class cannot be moved.
 *
 * @todo Move namespace up one.
 */

use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Plugin\ProviderManager;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\bootstrap\Traits\FormAutoloadFixTrait;
use Drupal\bootstrap\Utility\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * A base class for CDN Provider settings.
 *
 * @ingroup plugins_provider
 * @ingroup plugins_setting
 */
abstract class CdnProviderBase extends SettingBase {

  use FormAutoloadFixTrait;

  /**
   * The active provider based on form value or theme setting.
   *
   * @var \Drupal\bootstrap\Plugin\Provider\ProviderInterface
   */
  protected $activeProvider;

  /**
   * The setting provider.
   *
   * @var \Drupal\bootstrap\Plugin\Provider\ProviderInterface
   */
  protected $settingProvider;

  /**
   * The current provider manager instance.
   *
   * @var \Drupal\bootstrap\Plugin\ProviderManager
   */
  protected $providerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->providerManager = new ProviderManager($this->theme);
    $this->settingProvider = $this->providerManager->get(isset($plugin_definition['cdn_provider']) ? $plugin_definition['cdn_provider'] : NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    // Add autoload fix to make sure AJAX callbacks work.
    static::formAutoloadFix($form_state);
    $this->activeProvider = $this->providerManager->get($form_state->getValue('cdn_provider', $this->theme->getSetting('cdn_provider')));
    $this->alterFormElement(Element::create($form), $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFormElement(Element $form, FormStateInterface $form_state, $form_id = NULL) {
    // Immediately return if it's not the provider that should be configured.
    if ($this->activeProvider->getPluginId() !== $this->settingProvider->getPluginId()) {
      return;
    }
    $setting = $this->getSettingElement($form, $form_state);
    $this->buildCdnProviderElement($setting, $form_state);
  }

  /**
   * Builds the setting element for the CDN Provider.
   *
   * @param \Drupal\bootstrap\Utility\Element $setting
   *   The Element object that comprises the setting.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function buildCdnProviderElement(Element $setting, FormStateInterface $form_state) {
    // Allow settings to build more.
  }

  /**
   * AJAX callback for reloading CDN Providers.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function ajaxProvidersCallback(array $form, FormStateInterface $form_state) {
    return $form['cdn']['cdn_provider'];
  }

  /**
   * AJAX callback for reloading a specific CDN Provider.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function ajaxProviderCallback(array $form, FormStateInterface $form_state) {
    return $form['cdn']['cdn_provider'][$form_state->getValue('cdn_provider', Bootstrap::getTheme()->getSetting('cdn_provider'))];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['library_info'];
  }

  /**
   * Handles any CDN Provider exceptions that may have been thrown.
   */
  protected function checkCdnExceptions() {
    if ($exceptions = $this->settingProvider->getCdnExceptions()) {
      drupal_set_message($this->t('Unable to parse @provider CDN data. Check the <a href=":logs">logs</a> for more details. If your issues are network related, consider using the "custom" CDN Provider instead to statically set the URLs that should be used.', [
        ':logs' => Url::fromRoute('dblog.overview')->toString(),
        '@provider' => $this->settingProvider->getLabel(),
      ]), 'error');
      foreach ($exceptions as $exception) {
        watchdog_exception($this->theme->getName(), $exception);
      }
    }
  }

}
