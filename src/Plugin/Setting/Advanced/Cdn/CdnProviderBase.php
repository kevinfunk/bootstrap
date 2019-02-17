<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Plugin\Provider\ProviderInterface;
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
   * The current provider.
   *
   * @var \Drupal\bootstrap\Plugin\Provider\ProviderInterface
   */
  protected $provider;

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
    $this->provider = $this->providerManager->get(isset($plugin_definition['cdn_provider']) ? $plugin_definition['cdn_provider'] : NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    // Add autoload fix to make sure AJAX callbacks work.
    static::formAutoloadFix($form_state);
    $this->alterFormElement(Element::create($form), $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFormElement(Element $form, FormStateInterface $form_state, $form_id = NULL) {
    // Immediately return if it's not the provider that should be configured.
    $default_provider = $form_state->getValue('cdn_provider', $this->theme->getSetting('cdn_provider'));
    if ($default_provider !== $this->provider->getPluginId()) {
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
   * Builds a reset button for the cache provider.
   *
   * @param \Drupal\bootstrap\Plugin\Provider\ProviderInterface $provider
   *   A CDN Provider instance.
   *
   * @return \Drupal\bootstrap\Utility\Element
   *   The reset element.
   */
  protected function buildResetProviderCache(ProviderInterface $provider) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter */
    $dateFormatter = \Drupal::service('date.formatter');
    $reset = Element::createStandalone([
      '#type' => 'item',
      '#weight' => 100,
      '#description' => $this->t('All @provider data is cached using a time-based expiration method so it can persist through numerous cache rebuilds. If you believe data is not being retrieved from the API properly, you can manually reset the cache here. Otherwise it will invalidate and be rebuilt automatically after %duration.', [
        '@provider' => $provider->getLabel(),
        '%duration' => $dateFormatter->formatInterval($provider->getCacheTtl()),
      ]),
    ]);

    $reset->submit = Element::createStandalone([
      '#type' => 'submit',
      '#value' => $this->t('Reset @provider Cache', [
        '@provider' => $provider->getLabel(),
      ]),
      '#submit' => [
        [get_class($this), 'submitResetProviderCache'],
      ],
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxProvidersCallback'],
        'wrapper' => 'cdn-providers',
      ],
    ]);
    return $reset;
  }

  /**
   * AJAX callback for reloading CDN providers.
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
   * AJAX callback for reloading a specific CDN provider.
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
    if ($exceptions = $this->provider->getCdnExceptions()) {
      drupal_set_message($this->t('Unable to parse @provider CDN data. Check the <a href=":logs">logs</a> for more details. If your issues are network related, consider using the "custom" CDN Provider instead to statically set the URLs that should be used.', [
        ':logs' => Url::fromRoute('dblog.overview')->toString(),
        '@provider' => $this->provider->getLabel(),
      ]), 'error');
      foreach ($exceptions as $exception) {
        watchdog_exception('bootstrap', $exception);
      }
    }
  }

}
