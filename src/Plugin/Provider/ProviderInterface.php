<?php

namespace Drupal\bootstrap\Plugin\Provider;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * ProviderInterface.
 *
 * @ingroup plugins_provider
 */
interface ProviderInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * The default CDN Provider cache time-to-live (TTL) value (one week).
   *
   * @var int
   */
  const CACHE_TTL = 604800;

  /**
   * Alters the framework library.
   *
   * @param array $framework
   *   The framework library, passed by reference.
   * @param bool $min
   *   Optional. Flag determining whether to use minified resources. If not set,
   *   this will automatically be determined based on system configuration.
   */
  public function alterFrameworkLibrary(array &$framework, $min = NULL);

  /**
   * Retrieves the cache time-to-live (TTL) value.
   *
   * @return int
   *   The cache expire value, in seconds.
   */
  public function getCacheTtl();

  /**
   * Retrieves the assets from the CDN, if any.
   *
   * @param string $version
   *   Optional. The version of assets to return. If not set, the setting
   *   stored in the active theme will be used.
   * @param string $theme
   *   Optional. A specific set of themed assets to return, if any. If not set,
   *   the setting stored in the active theme will be used.
   *
   * @return array
   *   An associative array containing the following keys, if there were
   *   matching files found:
   *   - css
   *   - js
   *   - min:
   *     - css
   *     - js
   */
  public function getCdnAssets($version = NULL, $theme = NULL);

  /**
   * Retrieves the provider description.
   *
   * @return string
   *   The provider description.
   */
  public function getDescription();

  /**
   * Retrieves the provider human-readable label.
   *
   * @return string
   *   The provider human-readable label.
   */
  public function getLabel();

  /**
   * Retrieves any CDN ProviderException objects triggered during discovery.
   *
   * Note: this is primarily used as a way to communicate in the UI that
   * the discovery of the CDN Provider's assets failed.
   *
   * @param bool $reset
   *   Flag indicating whether to remove the Exceptions once they have been
   *   retrieved.
   *
   * @return \Drupal\bootstrap\Plugin\Provider\ProviderException[]
   *   An array of CDN ProviderException objects, if any.
   */
  public function getCdnExceptions($reset = TRUE);

  /**
   * Retrieves the currently set CDN provider theme.
   *
   * @return string
   *   The currently set CDN provider theme.
   */
  public function getCdnTheme();

  /**
   * Retrieves the themes supported by the CDN provider.
   *
   * @param string $version
   *   Optional. A specific version of themes to retrieve. If not set, the
   *   currently set CDN version of the active theme will be used.
   *
   * @return array
   *   An array of themes. If the CDN provider does not support any it will
   *   just be an empty array.
   */
  public function getCdnThemes($version = NULL);

  /**
   * Retrieves the currently set CDN provider version.
   *
   * @return string
   *   The currently set CDN provider version.
   */
  public function getCdnVersion();

  /**
   * Retrieves the versions supported by the CDN provider.
   *
   * @return array
   *   An array of versions. If the CDN provider does not support any it will
   *   just be an empty array.
   */
  public function getCdnVersions();

  /**
   * Removes any cached data the CDN Provider may have.
   */
  public function resetCache();

  /****************************************************************************
   *
   * Deprecated methods
   *
   ***************************************************************************/

  /**
   * Retrieves the API URL if set.
   *
   * @return string
   *   The API URL.
   *
   * @deprecated in 8.x-3.18, will be removed in a future release. There is no
   *   replacement for this functionality.
   */
  public function getApi();

  /**
   * Retrieves Provider assets for the active provider, if any.
   *
   * @param string|array $types
   *   The type of asset to retrieve: "css" or "js", defaults to an array
   *   array containing both if not set.
   *
   * @return array
   *   If $type is a string or an array with only one (1) item in it, the
   *   assets are returned as an indexed array of files. Otherwise, an
   *   associative array is returned keyed by the type.
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   *
   * @see \Drupal\bootstrap\Plugin\Provider\ProviderInterface::getCdnAssets()
   */
  public function getAssets($types = NULL);

  /**
   * Retrieves the themes supported by the CDN provider.
   *
   * @return array
   *   An array of themes. If the CDN provider does not support any it will
   *   just be an empty array.
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   *
   * @see \Drupal\bootstrap\Plugin\Provider\ProviderInterface::getCdnThemes()
   */
  public function getThemes();

  /**
   * Retrieves the versions supported by the CDN provider.
   *
   * @return array
   *   An array of versions. If the CDN provider does not support any it will
   *   just be an empty array.
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   *
   * @see \Drupal\bootstrap\Plugin\Provider\ProviderInterface::getCdnVersions()
   */
  public function getVersions();

  /**
   * Flag indicating that the API data parsing failed.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @deprecated in 8.x-3.18, will be removed in a future release. There is no
   *   1:1 replacement for this functionality.
   *
   * @see \Drupal\bootstrap\Plugin\Provider\ProviderInterface::getCdnExceptions()
   */
  public function hasError();

  /**
   * Flag indicating that the API data was manually imported.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @deprecated in 8.x-3.18, will be removed in a future release. There is no
   *   replacement for this functionality.
   */
  public function isImported();

  /**
   * Processes the provider plugin definition upon discovery.
   *
   * @param array $definition
   *   The provider plugin definition.
   * @param string $plugin_id
   *   The plugin identifier.
   *
   * @deprecated in 8.x-3.18, will be removed in a future release. There is no
   *   replacement for this functionality.
   */
  public function processDefinition(array &$definition, $plugin_id);

  /**
   * Processes the provider plugin definition upon discovery.
   *
   * @param array $json
   *   The JSON data retrieved from the API request.
   * @param array $definition
   *   The provider plugin definition.
   *
   * @deprecated in 8.x-3.18, will be removed in a future release. There is no
   *   replacement for this functionality.
   */
  public function processApi(array $json, array &$definition);

}
