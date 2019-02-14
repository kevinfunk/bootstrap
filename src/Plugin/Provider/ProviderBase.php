<?php

namespace Drupal\bootstrap\Plugin\Provider;

use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Plugin\PluginBase;
use Drupal\bootstrap\Plugin\ProviderManager;
use Drupal\bootstrap\Theme;
use Drupal\bootstrap\Utility\Crypt;
use Drupal\bootstrap\Utility\Unicode;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

/**
 * CDN provider base class.
 *
 * @ingroup plugins_provider
 */
class ProviderBase extends PluginBase implements ProviderInterface {

  /**
   * The currently set assets.
   *
   * @var array
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   */
  protected $assets = [];

  /**
   * The currently set CDN assets.
   *
   * @var array
   */
  protected $cdnAssets;

  /**
   * The cache backend used for caching various CDN provider tasks.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The versions supplied by the CDN provider.
   *
   * @var array
   */
  protected $versions;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cache = \Drupal::cache('discovery');
  }

  /**
   * {@inheritdoc}
   */
  public function alterFrameworkLibrary(array &$framework, $min = NULL) {
    // Attempt to retrieve CDN assets from a sort of permanent cached in the
    // theme settings. This is primarily used to avoid unnecessary API requests
    // and speed up the process during a cache rebuild. Theme settings are used
    // as they persist through cache rebuilds. In order to prevent stale data,
    // a hash is used based on current CDN settings and this "permacache" is
    // reset at least once a week regardless.
    // @see https://www.drupal.org/project/bootstrap/issues/3031415
    $cdnCache = $this->theme->getSetting('cdn_cache', []);
    $requestTime = \Drupal::time()->getRequestTime();

    // Reset cache if expired.
    if (isset($cdnCache['expire']) && (empty($cdnCache['expire']) || $requestTime > $cdnCache['expire'])) {
      $cdnCache = [];
    }

    // Set expiration date (1 week by default).
    if (!isset($cdnCache['expire'])) {
      $cdnCache['expire'] = $requestTime + $this->theme->getSetting('cdn_cache_expire', 604800);
    }

    $cdnVersion = $this->getCdnVersion();
    $cdnTheme = $this->getCdnTheme();

    // Cache not found.
    $cdnHash = Crypt::hashBase64("{$this->pluginId}:$cdnTheme:$cdnVersion");
    if (!isset($cdnCache[$cdnHash])) {
      // Retrieve assets and reset cache (should only cache one at a time).
      $cdnCache = [
        'expire' => $cdnCache['expire'],
        $cdnHash => $this->getCdnAssets($cdnVersion, $cdnTheme),
      ];
      $this->theme->setSetting('cdn_cache', $cdnCache);
    }

    // Immediately return if there are no theme CDN assets to use.
    if (empty($cdnCache[$cdnHash])) {
      return;
    }

    // Retrieve the system performance config.
    if (!isset($min)) {
      $config = \Drupal::config('system.performance');
      $min = [
        'css' => $config->get('css.preprocess'),
        'js' => $config->get('js.preprocess'),
      ];
    }
    else {
      $min = ['css' => !!$min, 'js' => !!$min];
    }

    // Iterate over each type.
    $assets = [];
    foreach (['css', 'js'] as $type) {
      $files = !empty($min[$type]) && isset($cdnCache[$cdnHash]['min'][$type]) ? $cdnCache[$cdnHash]['min'][$type] : (isset($cdnCache[$cdnHash][$type]) ? $cdnCache[$cdnHash][$type] : []);
      foreach ($files as $asset) {
        $data = ['data' => $asset, 'type' => 'external'];
        // CSS library assets use "SMACSS" categorization, assign it to "base".
        if ($type === 'css') {
          $assets[$type]['base'][$asset] = $data;
        }
        else {
          $assets[$type][$asset] = $data;
        }
      }
    }

    // Override the framework version with the CDN version that is being used.
    $framework['version'] = $cdnVersion;

    // Merge the assets into the library info.
    $framework = NestedArray::mergeDeepArray([$assets, $framework], TRUE);

    // The overrides file must also be stored in the "base" category so
    // it isn't added after any potential sub-theme's "theme" category.
    // There's no weight, so it will be added after the provider's assets.
    // Since this uses a relative path to the ancestor from DRUPAL_ROOT,
    // the entire path must be prepended with a forward slash (/) so it
    // doesn't prepend the active theme's path.
    // @see https://www.drupal.org/node/2770613
    if ($overrides = $this->getOverrides()) {
      $framework['css']['base']["/$overrides"] = [];
    }
  }

  /**
   * Retrieves a value from the CDN provider cache.
   *
   * @param string $key
   *   The name of the item to retrieve. Note: this can be in the form of dot
   *   notation if the value is nested in an array.
   * @param mixed $default
   *   Optional. The default value to return if $key is not set.
   * @param callable $builder
   *   Optional. If provided, a builder will be invoked when there is no cache
   *   currently set.
   *
   * @return mixed
   *   The cached value if it's set or the value supplied to $default if not.
   */
  protected function cacheGet($key, $default = NULL, callable $builder = NULL) {
    $cid = $this->getCacheId();
    $cache = $this->cache->get($cid);
    $data = $cache && isset($cache->data) && is_array($cache->data) ? $cache->data : [];
    $parts = Unicode::splitDelimiter($key);
    $value = NestedArray::getValue($data, $parts, $key_exists);

    // Build the cache.
    if (!$key_exists && $builder) {
      $value = $builder($default);
      if (!isset($value)) {
        $value = $default;
      }
      NestedArray::setValue($data, $parts, $value);
      $this->cache->set($cid, $data);
      return $value;
    }

    return $key_exists ? $value : $default;
  }

  /**
   * Sets a value in the CDN provider cache.
   *
   * @param string $key
   *   The name of the item to set. Note: this can be in the form of dot
   *   notation if the value is nested in an array.
   * @param mixed $value
   *   Optional. The value to set.
   */
  protected function cacheSet($key, $value = NULL) {
    $cid = $this->getCacheId();
    $cache = $this->cache->get($cid);
    $data = $cache && isset($cache->data) && is_array($cache->data) ? $cache->data : [];
    $parts = Unicode::splitDelimiter($key);
    NestedArray::setValue($data, $parts, $value);
    $this->cache->set($cid, $data);
  }

  /**
   * {@inheritdoc}
   */
  protected function discoverCdnAssets($version, $theme) {
    return $this->getAssets();
  }

  /**
   * Retrieves the unique cache identifier for the CDN provider.
   *
   * @return string
   *   The CDN provider cache identifier.
   */
  protected function getCacheId() {
    return "theme:{$this->theme->getName()}:provider:{$this->getPluginId()}";
  }

  /**
   * {@inheritdoc}
   */
  public function getCdnAssets($version = NULL, $theme = NULL) {
    if (!isset($version)) {
      $version = $this->getCdnVersion();
    }
    if (!isset($theme)) {
      $theme = $this->getCdnTheme();
    }

    if (!isset($this->cdnAssets)) {
      $this->cdnAssets = $this->cacheGet('cdn.assets', []);
    }

    if (!isset($this->cdnAssets[$version][$theme])) {
      $escapedVersion = Unicode::escapeDelimiter($version);
      $this->cdnAssets[$version][$theme] = $this->cacheGet("cdn.assets.$escapedVersion.$theme", [], function () use ($version, $theme) {
        return $this->discoverCdnAssets($version, $theme);
      });
    }

    return $this->cdnAssets[$version][$theme];
  }

  /**
   * {@inheritdoc}
   */
  public function getCdnTheme() {
    return $this->theme->getSetting("cdn_{$this->getPluginId()}_theme") ?: 'bootstrap';
  }

  /**
   * {@inheritdoc}
   */
  public function getCdnThemes($version = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCdnVersion(Theme $theme = NULL) {
    return $this->theme->getSetting("cdn_{$this->getPluginId()}_version") ?: Bootstrap::FRAMEWORK_VERSION;
  }

  /**
   * {@inheritdoc}
   */
  public function getCdnVersions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'] ?: $this->getPluginId();
  }

  /**
   * Retrieves the Drupal overrides CSS file.
   *
   * @return string|null
   *   THe Drupal overrides CSS file.
   */
  protected function getOverrides() {
    $version = $this->getCdnVersion();
    $theme = $this->getCdnTheme();
    $theme = !$theme || $theme === '_default' || $theme === 'bootstrap' || $theme === 'bootstrap_theme' ? '' : "-$theme";
    foreach ($this->theme->getAncestry(TRUE) as $ancestor) {
      $overrides = $ancestor->getPath() . "/css/{$version}/overrides{$theme}.min.css";
      if (file_exists($overrides)) {
        return $overrides;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getThemes() {
    return $this->pluginDefinition['themes'];
  }

  /**
   * {@inheritdoc}
   */
  public function getVersions() {
    return $this->pluginDefinition['versions'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasError() {
    return $this->pluginDefinition['error'];
  }

  /**
   * {@inheritdoc}
   */
  public function isImported() {
    return $this->pluginDefinition['imported'];
  }

  /**
   * Retrieves JSON from a URI.
   *
   * @param string $uri
   *   The URI to retrieve JSON from.
   * @param array $options
   *   The options to pass to the HTTP client.
   * @param \Exception|null $exception
   *   The exception thrown if there was an error, passed by reference.
   *
   * @return array
   *   The requested JSON array.
   */
  protected function requestJson($uri, array $options = [], &$exception = NULL) {
    $json = [];

    $options += [
      'method' => 'GET',
      'headers' => [
        'User-Agent' => 'Drupal Bootstrap 8.x-3.x (https://www.drupal.org/project/bootstrap)',
      ],
    ];

    /** @var \GuzzleHttp\Client $client */
    $client = \Drupal::service('http_client_factory')->fromOptions($options);
    $request = new Request($options['method'], $uri);
    try {
      $response = $client->send($request, $options);
      if ($response->getStatusCode() == 200) {
        $contents = $response->getBody(TRUE)->getContents();
        $json = Json::decode($contents) ?: [];
      }
    }
    catch (GuzzleException $e) {
      $exception = $e;
    }
    catch (\Exception $e) {
      $exception = $e;
    }

    return $json;
  }

  /****************************************************************************
   *
   * Deprecated methods
   *
   ***************************************************************************/

  /**
   * {@inheritdoc}
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   */
  public function getApi() {
    return $this->pluginDefinition['api'];
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   */
  public function getAssets($types = NULL) {
    Bootstrap::deprecated();
    return $this->assets;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   */
  public function processDefinition(array &$definition, $plugin_id) {
    // Due to code recursion and the need to keep this code in place for BC
    // reasons, this deprecated message should only be logged and not shown.
    Bootstrap::deprecated(FALSE);

    // Process API data.
    if ($api = $this->getApi()) {
      $provider_path = ProviderManager::FILE_PATH;
      file_prepare_directory($provider_path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

      // Use manually imported API data, if it exists.
      if (file_exists("$provider_path/$plugin_id.json") && ($imported_data = file_get_contents("$provider_path/$plugin_id.json"))) {
        $definition['imported'] = TRUE;
        try {
          $json = Json::decode($imported_data);
        }
        catch (\Exception $e) {
          // Intentionally left blank.
        }
      }
      // Otherwise, attempt to request API data if the provider has specified
      // an "api" URL to use.
      else {
        $json = $this->requestJson($api);
      }

      if (!isset($json)) {
        $json = [];
        $definition['error'] = TRUE;
      }

      $this->processApi($json, $definition);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   */
  public function processApi(array $json, array &$definition) {}

}
