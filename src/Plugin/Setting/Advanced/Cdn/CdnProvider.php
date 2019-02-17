<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

/**
 * Due to BC reasons, this class cannot be moved.
 *
 * @todo Move namespace up one.
 */

use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Plugin\Form\SystemThemeSettings;
use Drupal\bootstrap\Plugin\Provider\Broken;
use Drupal\bootstrap\Plugin\Provider\ProviderInterface;
use Drupal\bootstrap\Plugin\ProviderManager;
use Drupal\bootstrap\Utility\Element;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * The "cdn_provider" theme setting.
 *
 * @ingroup plugins_provider
 * @ingroup plugins_setting
 *
 * @BootstrapSetting(
 *   id = "cdn_provider",
 *   type = "select",
 *   title = @Translation("CDN Provider"),
 *   description = @Translation("Choose the CDN Provider used to load Bootstrap resources."),
 *   defaultValue = "jsdelivr",
 *   empty_value = "",
 *   weight = -1,
 *   groups = {
 *     "cdn" = @Translation("CDN (Content Delivery Network)"),
 *     "cdn_provider" = false,
 *   },
 *   options = { },
 * )
 */
class CdnProvider extends CdnProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alterFormElement(Element $form, FormStateInterface $form_state, $form_id = NULL) {
    parent::alterFormElement($form, $form_state);

    // Wrap the default group so it can be replaced via AJAX.
    $group = $this->getGroupElement($form, $form_state);
    $group->setProperty('prefix', '<div id="cdn-providers">');
    $group->setProperty('suffix', '</div>');

    // Intercept possible manual import of API data via AJAX callback.
    $this->importProviderData($form_state);

    // Override the options with the provider manager discovery.
    $setting = $this->getSettingElement($form, $form_state);
    $setting->setProperty('empty_option', $this->t('None (compile locally)'));
    $providers = $this->theme->getCdnProviders();
    $setting->setProperty('options', array_map(function (ProviderInterface $provider) {
      return $provider->getLabel();
    }, $providers));

    $setting->setProperty('ajax', [
      'callback' => [get_class($this), 'ajaxProvidersCallback'],
      'wrapper' => 'cdn-providers',
    ]);

    $group->cache = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Cache'),
      '#description' => $this->t('All @provider data is intelligently and automatically cached using the various settings below. This allows the @provider data to persist through cache rebuilds. This data will invalidate and rebuild automatically, however a manual reset can be invoked below.', [
        '@provider' => $this->activeProvider->getPluginId() === 'custom' ? $this->t('CDN Provider') : $this->activeProvider->getLabel(),
      ]),
      '#weight' => 1000,
    ];

    if (!($this->activeProvider instanceof Broken)) {
      // Add a CDN Provider cache reset button.
      if ($reset = $this->buildResetProviderCache($this->activeProvider)) {
        $group->cache->reset = $reset;
      }
      $this->createProviderGroup($group, $this->activeProvider);
    }
    else {
      $group->cache['#access'] = FALSE;
    }
  }

  /**
   * Submit callback for resetting CDN Provider cache.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitResetProviderCache(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $theme = SystemThemeSettings::getTheme(Element::create($form), $form_state);
    $provider = ProviderManager::load($theme, $form_state->getValue('cdn_provider', $theme->getSetting('cdn_provider')));
    $provider->resetCache();
  }

  /**
   * Creates the necessary containers for each provider.
   *
   * @param \Drupal\bootstrap\Utility\Element $group
   *   The group element instance.
   * @param \Drupal\bootstrap\Plugin\Provider\ProviderInterface $provider
   *   The provider instance.
   */
  protected function createProviderGroup(Element $group, ProviderInterface $provider) {
    $plugin_id = Html::cleanCssIdentifier($provider->getPluginId());

    // Create the provider container.
    $group->$plugin_id = [
      '#type' => 'container',
      '#prefix' => '<div id="cdn-provider-' . $plugin_id . '" class="form-group">',
      '#suffix' => '</div>',
    ];

    // Add in the provider description.
    if ($description = $provider->getDescription()) {
      $group->$plugin_id->description = [
        '#markup' => '<div class="help-block">' . $description . '</div>',
        '#weight' => -99,
      ];
    }

    // To avoid triggering unnecessary deprecation messages, extract these
    // values from the provider definition directly.
    // @todo Remove when the deprecated functionality is removed.
    $definition = $provider->getPluginDefinition();
    $hasError = !empty($definition['error']);
    $isImported = !empty($definition['imported']);

    // Indicate there was an error retrieving the provider's API data.
    if ($hasError || $isImported) {
      if ($isImported) {
        Bootstrap::deprecated('\Drupal\bootstrap\Plugin\Provider\ProviderInterface::isImported');
      }
      if ($hasError) {
        // Now a deprecation message can be shown as the provider clearly is
        // using the outdated "process definition" method of providing assets.
        Bootstrap::deprecated('\Drupal\bootstrap\Plugin\Provider\ProviderInterface::hasError');
        $description_label = $this->t('ERROR');
        $description = $this->t('Unable to reach or parse the data provided by the @title API. Ensure the server this website is hosted on is able to initiate HTTP requests. If the request consistently fails, it is likely that there are certain PHP functions that have been disabled by the hosting provider for security reasons. It is possible to manually copy and paste the contents of the following URL into the "Imported @title data" section below.<br /><br /><a href=":provider_api" target="_blank">:provider_api</a>.', [
          '@title' => $provider->getLabel(),
          ':provider_api' => $provider->getApi(),
        ]);
        $group->$plugin_id->error = [
          '#markup' => '<div class="alert alert-danger messages error"><strong>' . $description_label . ':</strong> ' . $description . '</div>',
          '#weight' => -20,
        ];
      }

      $group->$plugin_id->import = [
        '#type' => 'details',
        '#title' => t('Imported @title data', ['@title' => $provider->getLabel()]),
        '#description' => t('The provider will attempt to parse the data entered here each time it is saved. If no data has been entered, any saved files associated with this provider will be removed and the provider will again attempt to request the API data normally through the following URL: <a href=":provider_api" target="_blank">:provider_api</a>.', [
          ':provider_api' => $provider->getPluginDefinition()['api'],
        ]),
        '#weight' => 10,
        '#open' => FALSE,
      ];

      $group->$plugin_id->import->cdn_provider_import_data = [
        '#type' => 'textarea',
        '#default_value' => file_exists(ProviderManager::FILE_PATH . '/' . $plugin_id . '.json') ? file_get_contents(ProviderManager::FILE_PATH . '/' . $plugin_id . '.json') : NULL,
      ];

      $group->$plugin_id->import->submit = [
        '#type' => 'submit',
        '#value' => t('Save provider data'),
        '#executes_submit_callback' => FALSE,
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxCallback'],
          'wrapper' => 'cdn-provider-' . $plugin_id,
        ],
      ];
    }
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
    $reset = Element::createStandalone([
      '#type' => 'item',
      '#weight' => 100,
    ]);

    $reset->submit = Element::createStandalone([
      '#type' => 'submit',
      '#description' => $this->t('Note: this will not reset any cached HTTP requests; see the "Advanced" section.'),
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
   * Imports data for a provider that was manually uploaded in theme settings.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function importProviderData(FormStateInterface $form_state) {
    if ($form_state->getValue('clicked_button') === t('Save provider data')->render()) {
      $provider_path = ProviderManager::FILE_PATH;
      file_prepare_directory($provider_path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

      $provider = $form_state->getValue('cdn_provider', $this->theme->getSetting('cdn_provider'));
      $file = "$provider_path/$provider.json";

      if ($import_data = $form_state->getValue('cdn_provider_import_data', FALSE)) {
        file_unmanaged_save_data($import_data, $file, FILE_EXISTS_REPLACE);
      }
      elseif ($file && file_exists($file)) {
        file_unmanaged_delete($file);
      }

      // Clear the cached definitions so they can get rebuilt.
      $this->providerManager->clearCachedDefinitions();
    }
  }

}
