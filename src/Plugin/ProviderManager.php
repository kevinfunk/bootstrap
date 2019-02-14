<?php

namespace Drupal\bootstrap\Plugin;

use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Theme;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;

/**
 * Manages discovery and instantiation of Bootstrap CDN providers.
 *
 * @ingroup plugins_provider
 */
class ProviderManager extends PluginManager implements FallbackPluginManagerInterface {
  /**
   * The base file system path for CDN providers.
   *
   * @var string
   */
  const FILE_PATH = 'public://bootstrap/provider';

  /**
   * Constructs a new \Drupal\bootstrap\Plugin\ProviderManager object.
   *
   * @param \Drupal\bootstrap\Theme $theme
   *   The theme to use for discovery.
   */
  public function __construct(Theme $theme) {
    parent::__construct($theme, 'Plugin/Provider', 'Drupal\bootstrap\Plugin\Provider\ProviderInterface', 'Drupal\bootstrap\Annotation\BootstrapProvider');
    $this->setCacheBackend(\Drupal::cache('discovery'), 'theme:' . $theme->getName() . ':provider', $this->getCacheTags());
  }

  /**
   * Retrieves a CDN Provider.
   *
   * @param string $provider
   *   Optional. The ID of the provider to load. If not set or an invalid
   *   provider was specified, the base provider will be returned.
   * @param array $configuration
   *   Optional. An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\bootstrap\Plugin\Provider\ProviderInterface
   *   A CDN Provider instance.
   */
  public function get($provider, array $configuration = []) {
    return $this->createInstance($provider, $configuration + ['theme' => $this->theme]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return '_broken';
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    /** @var \Drupal\bootstrap\Plugin\Provider\ProviderInterface $provider */
    $provider = new $definition['class'](['theme' => $this->theme], $plugin_id, $definition);
    $provider->processDefinition($definition, $plugin_id);
  }

  /**
   * Loads a CDN Provider.
   *
   * @param \Drupal\bootstrap\Theme|string $theme
   *   Optional. A theme to associate with the provider. If not set, the
   *   active theme will be used.
   * @param string $provider
   *   Optional. The ID of the provider to load. If not set, the provider set
   *   on the supplied or active $theme will be used.
   * @param array $configuration
   *   Optional. An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\bootstrap\Plugin\Provider\ProviderInterface
   *   A CDN Provider instance.
   */
  public static function load($theme = NULL, $provider = NULL, array $configuration = []) {
    $theme = Bootstrap::getTheme($theme);
    return (new static($theme))->get($provider ?: $theme->getSetting('cdn_provider'), $configuration + ['theme' => $theme]);
  }

}
