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
   * The cache backend used for caching various CDN provider tasks.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $cache;

  /**
   * The amount, in seconds, CDN Provider data should be cached.
   *
   * @var int
   *
   * @see \Drupal\bootstrap\Plugin\Provider\ProviderInterface::CACHE_TTL
   */
  protected $cacheTtl;

  /**
   * The currently set CDN assets.
   *
   * @var array
   */
  protected $cdnAssets;

  /**
   * A list of currently set Exception objects.
   *
   * @var \Drupal\bootstrap\Plugin\Provider\ProviderException[]
   */
  protected $cdnExceptions = [];

  /**
   * The versions supplied by the CDN provider.
   *
   * @var array
   */
  protected $versions;

  /**
   * Adds a new CDN Provider exception.
   *
   * @param string|\Exception $message
   *   The exception message.
   */
  protected function addCdnException($message) {
    if ($message instanceof \Throwable) {
      $this->cdnExceptions[] = new ProviderException($this, $message->getMessage(), $message->getCode(), $message);
    }
    else {
      $this->cdnExceptions[] = new ProviderException($this, $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterFrameworkLibrary(array &$framework, $min = NULL) {
    // Attempt to retrieve cached CDN assets from the database. This is
    // primarily used to avoid unnecessary API requests and speed up the
    // process during a cache rebuild. The "keyvalue.expirable" service is
    // used as it persists through cache rebuilds. In order to prevent stale
    // data, a hash is used constructed of various CDN and preprocess settings.
    // The cache is rebuilt after it has expired, one week by default, based on
    // the "cdn_cache_ttl" theme setting.
    // @see https://www.drupal.org/project/bootstrap/issues/3031415
    $cdn = [
      'ttl' => $this->getCacheTtl(),
      'min' => [
        'css' => !!(isset($min) ? $min : \Drupal::config('system.performance')->get('css.preprocess')),
        'js' => !!(isset($min) ? $min : \Drupal::config('system.performance')->get('js.preprocess')),
      ],
      'provider' => $this->pluginId,
      'theme' => $this->getCdnTheme(),
      'version' => $this->getCdnVersion(),
    ];

    // Construct a key based on hashed CDN Provider values.
    $key = 'library-' . Crypt::hashBase64(serialize($cdn));

    // Retrieve the assets from the cache.
    $assets = $cdn['ttl'] > 0 ? $this->getCache()->get($key) : NULL;

    // Rebuild assets if they're not set.
    if (!isset($assets)) {
      $cdnAssets = $this->getCdnAssets($cdn['version'], $cdn['theme']);

      // Iterate over each type.
      $assets = [];
      foreach (['css', 'js'] as $type) {
        $files = !empty($cdn['min'][$type]) && isset($cdnAssets['min'][$type]) ? $cdnAssets['min'][$type] : (isset($cdnAssets[$type]) ? $cdnAssets[$type] : []);
        foreach ($files as $asset) {
          $data = ['data' => $asset, 'type' => 'external'];
          // CSS library assets use "SMACSS" categorization, assign to "base".
          if ($type === 'css') {
            $assets[$type]['base'][$asset] = $data;
          }
          else {
            $assets[$type][$asset] = $data;
          }
        }
      }

      // Cache the assets.
      $this->getCache()->setWithExpire($key, $assets, $cdn['ttl']);
    }

    // Immediately return if there are no theme CDN assets to use.
    if (empty($assets)) {
      return;
    }

    // Override the framework version with the CDN version that is being used.
    $framework['version'] = $cdn['version'];

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
   * @param string $name
   *   The name of the cache item to retrieve.
   * @param string $key
   *   Optional. A specific key of the item to retrieve. Note: this can be in
   *   the form of dot notation if the value is nested in an array. If not
   *   provided, the entire contents of $name will be returned.
   * @param mixed $default
   *   Optional. The default value to return if $key is not set.
   * @param callable $builder
   *   Optional. If provided, a builder will be invoked when there is no cache
   *   currently set. The return value of the build will be used to set the
   *   cached value, provided there are no CDN Provider exceptions generated.
   *   If there are, but you still need the cache to be set, reset them prior
   *   to returning from the builder callback.
   *
   * @return mixed
   *   The cached value if it's set or the value supplied to $default if not.
   */
  protected function cacheGet($name, $key = NULL, $default = NULL, callable $builder = NULL) {
    $data = $this->getCache()->get($name, []);

    if (!isset($key)) {
      return $data;
    }

    $parts = Unicode::splitDelimiter($key);
    $value = NestedArray::getValue($data, $parts, $key_exists);

    // Build the cache.
    if (!$key_exists && $builder) {
      $value = $builder($default);
      if (!isset($value)) {
        $value = $default;
      }
      NestedArray::setValue($data, $parts, $value);

      // Only set the cache if no CDN Provider exceptions were thrown.
      if (!$this->cdnExceptions) {
        $this->getCache()->setWithExpire($name, $data, $this->getCacheTtl());
      }

      return $value;
    }

    return $key_exists ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  protected function discoverCdnAssets($version, $theme) {
    return $this->getAssets();
  }

  /**
   * Retrieves the cache instance.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   *   An expirable key/value storage instance.
   */
  protected function getCache() {
    if (!isset($this->cache)) {
      $this->cache = \Drupal::keyValueExpirable($this->getCacheId());
    }
    return $this->cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTtl() {
    if (!isset($this->cacheTtl)) {
      $this->cacheTtl = (int) $this->theme->getSetting('cdn_cache_ttl', static::CACHE_TTL);
    }
    return $this->cacheTtl;
  }

  /**
   * Retrieves the unique cache identifier for the CDN provider.
   *
   * @return string
   *   The CDN provider cache identifier.
   */
  protected function getCacheId() {
    return "theme:{$this->theme->getName()}:cdn:{$this->getPluginId()}";
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
      $this->cdnAssets = $this->cacheGet('assets');
    }

    if (!isset($this->cdnAssets[$version][$theme])) {
      $escapedVersion = Unicode::escapeDelimiter($version);
      $this->cdnAssets[$version][$theme] = $this->cacheGet('assets', "$escapedVersion.$theme", [], function () use ($version, $theme) {
        return $this->discoverCdnAssets($version, $theme);
      });
    }

    return $this->cdnAssets[$version][$theme];
  }

  /**
   * {@inheritdoc}
   */
  public function getCdnExceptions($reset = TRUE) {
    $exceptions = $this->cdnExceptions;
    if ($reset) {
      $this->cdnExceptions = [];
    }
    return $exceptions;
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
   * Allows providers a way to map a version to a different version.
   *
   * @param string $version
   *   The version to map.
   *
   * @return string
   *   The mapped version.
   */
  protected function mapVersion($version) {
    return $version;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $this->getCache()->deleteAll();

    // Invalidate library info if this provider is the one currently used.
    if (($provider = $this->theme->getCdnProvider()) && $provider->getPluginId() === $this->pluginId) {
      /** @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface $invalidator */
      $invalidator = \Drupal::service('cache_tags.invalidator');
      $invalidator->invalidateTags(['library_info']);
    }
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
    Bootstrap::deprecated();
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
  public function hasError() {
    Bootstrap::deprecated();
    return $this->pluginDefinition['error'];
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   */
  public function isImported() {
    Bootstrap::deprecated();
    return $this->pluginDefinition['imported'];
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
        $json = Bootstrap::requestJson($api);
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
  public function processApi(array $json, array &$definition) {
    Bootstrap::deprecated();
  }

}
