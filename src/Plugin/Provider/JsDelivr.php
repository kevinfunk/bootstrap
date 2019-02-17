<?php

namespace Drupal\bootstrap\Plugin\Provider;

use Drupal\bootstrap\Bootstrap;

/**
 * The "jsdelivr" CDN Provider plugin.
 *
 * @ingroup plugins_provider
 *
 * @BootstrapProvider(
 *   id = "jsdelivr",
 *   label = @Translation("jsDelivr"),
 * )
 */
class JsDelivr extends ProviderBase {

  /**
   * The base API URL.
   *
   * @var string
   */
  const BASE_API_URL = 'https://data.jsdelivr.com/v1/package/npm';

  /**
   * The base CDN URL.
   *
   * @var string
   */
  const BASE_CDN_URL = 'https://cdn.jsdelivr.net/npm';

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('<p><a href=":jsdelivr" target="_blank">jsDelivr</a> is a free multi-CDN infrastructure that uses <a href=":maxcdn" target="_blank">MaxCDN</a>, <a href=":cloudflare" target="_blank">Cloudflare</a> and many others to combine their powers for the good of the open source community... <a href=":jsdelivr_about" target="_blank">read more</a></p>', [
      ':jsdelivr' => 'https://www.jsdelivr.com',
      ':jsdelivr_about' => 'https://www.jsdelivr.com/about',
      ':maxcdn' => 'https://www.maxcdn.com',
      ':cloudflare' => 'https://www.cloudflare.com',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function discoverCdnAssets($version, $theme = NULL) {
    $themes = $this->getCdnThemes($version);
    return isset($themes[$theme]) ? $themes[$theme] : [];
  }

  /**
   * {@inheritdoc}
   */
  protected function discoverCdnThemes($version) {
    $themes = [];
    foreach (['bootstrap', 'bootswatch'] as $package) {
      $mappedVersion = $this->mapVersion($version, $package);
      $files = $this->requestApiV1($package, $mappedVersion, $this->getCacheTtl(static::CACHE_THEMES));
      $themes = $this->parseThemes($files, $package, $mappedVersion, $themes);
    }
    return $themes;
  }

  /**
   * {@inheritdoc}
   */
  protected function discoverCdnVersions() {
    $versions = [];
    $json = $this->requestApiV1('bootstrap', NULL, $this->getCacheTtl(static::CACHE_VERSIONS)) + ['versions' => []];
    foreach ($json['versions'] as $version) {
      // Skip irrelevant versions.
      if (!preg_match('/^' . substr(Bootstrap::FRAMEWORK_VERSION, 0, 1) . '\.\d+\.\d+$/', $version)) {
        continue;
      }
      $versions[$version] = $version;
    }
    return $versions;
  }

  /**
   * {@inheritdoc}
   */
  protected function mapVersion($version, $package = NULL) {
    // While the Bootswatch project attempts to maintain version parity with
    // Bootstrap, it doesn't always happen. This causes issues when the system
    // expects a 1:1 version match between Bootstrap and Bootswatch.
    // @see https://github.com/thomaspark/bootswatch/issues/892#ref-issue-410070082
    if ($package === 'bootswatch') {
      switch ($version) {
        // This version is "broken" because of jsDelivr's API limit.
        case '3.4.1':
          $version = '3.4.0';
          break;

        // This version doesn't exist.
        case '3.1.1':
          $version = '3.2.0';
          break;
      }
    }
    return $version;
  }

  /**
   * Parses JSON from the API and retrieves valid files.
   *
   * @param array $json
   *   The JSON data to parse.
   *
   * @return array
   *   An array of files parsed from provided JSON data.
   */
  protected function parseFiles(array $json) {
    // Immediately return if malformed.
    if (!isset($json['files']) || !is_array($json['files'])) {
      return [];
    }

    $files = [];
    foreach ($json['files'] as $file) {
      // Skip old bootswatch file structure.
      if (preg_match('`^/2|/bower_components`', $file['name'], $matches)) {
        continue;
      }
      preg_match('`([^/]*)/bootstrap(-theme)?(\.min)?\.(js|css)$`', $file['name'], $matches);
      if (!empty($matches[1]) && !empty($matches[4])) {
        $files[] = $file['name'];
      }
    }
    return $files;
  }

  /**
   * Extracts assets from files provided by the jsDelivr API.
   *
   * This will place the raw files into proper "css", "js" and "min" arrays
   * (if they exist) and prepends them with a base URL provided.
   *
   * @param array $files
   *   An array of files to process.
   * @param string $package
   *   The base URL each one of the $files are relative to, this usually
   *   should also include the version path prefix as well.
   * @param string $version
   *   A specific version to use.
   * @param array $themes
   *   An existing array of themes. This is primarily used when building a
   *   complete list of themes.
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
  protected function parseThemes(array $files, $package, $version, array $themes = []) {
    $baseUrl = static::BASE_CDN_URL . "/$package@$version";
    foreach ($files as $file) {
      preg_match('`([^/]*)/bootstrap(-theme)?(\.min)?\.(js|css)$`', $file, $matches);
      if (!empty($matches[1]) && !empty($matches[4])) {
        $path = $matches[1];
        $min = $matches[3];
        $filetype = $matches[4];

        // Determine the "theme" name.
        if ($path === 'css' || $path === 'js') {
          $theme = 'bootstrap';
          $title = (string) $this->t('Default');
        }
        else {
          $theme = $path;
          $title = ucfirst($path);
        }
        if ($matches[2]) {
          $theme = 'bootstrap_theme';
          $title = (string) $this->t('Example Theme');
        }

        $themes[$theme]['title'] = $title;
        if ($min) {
          $themes[$theme]['min'][$filetype][] = "$baseUrl/" . ltrim($file, '/');
        }
        else {
          $themes[$theme][$filetype][] = "$baseUrl/" . ltrim($file, '/');
        }
      }
    }

    // Post process the themes to fill in any missing assets.
    foreach (array_keys($themes) as $theme) {
      // Some themes do not have a non-minified version, clone them to the
      // "normal" css/js arrays to ensure that the theme still loads if
      // aggregation (minification) is disabled.
      foreach (['css', 'js'] as $type) {
        if (!isset($themes[$theme][$type]) && isset($themes[$theme]['min'][$type])) {
          $themes[$theme][$type] = $themes[$theme]['min'][$type];
        }
      }

      // Prepend the main Bootstrap styles before the Bootstrap theme.
      if ($theme === 'bootstrap_theme') {
        if (isset($themes['bootstrap']['css'])) {
          $themes[$theme]['css'] = array_unique(array_merge($themes['bootstrap']['css'], isset($themes[$theme]['css']) ? $themes[$theme]['css'] : []));
        }
        if (isset($themes['bootstrap']['min']['css'])) {
          $themes[$theme]['min']['css'] = array_unique(array_merge($themes['bootstrap']['min']['css'], isset($themes[$theme]['min']['css']) ? $themes[$theme]['min']['css'] : []));
        }
      }

      // Populate missing JavaScript.
      if (!isset($themes[$theme]['js']) && isset($themes['bootstrap']['js'])) {
        $themes[$theme]['js'] = $themes['bootstrap']['js'];
      }
      if (!isset($themes[$theme]['min']['js']) && isset($themes['bootstrap']['min']['js'])) {
        $themes[$theme]['min']['js'] = $themes['bootstrap']['min']['js'];
      }
    }

    return $themes;
  }

  /**
   * Requests JSON from jsDelivr's API V1.
   *
   * @param string $package
   *   The NPM package being requested.
   * @param string $version
   *   A specific version of $package to request. If not provided, a list of
   *   available versions will be returned.
   * @param int $ttl
   *   Optional. A specific TTL value to use for caching the HTTP request. If
   *   not set, it will default to whatever is returned by the HTTP request.
   *
   * @return array
   *   The JSON data from the API.
   */
  protected function requestApiV1($package, $version = NULL, $ttl = NULL) {
    $uri = static::BASE_API_URL . "/$package";
    $options = [];

    if (isset($ttl)) {
      $options['ttl'] = $ttl;
    }

    // If no version was passed, then all versions are returned.
    if (!$version) {
      $response = $this->requestJson($uri, $options);
      $json = $response->getJson();

      // If bootstrap JSON could not be returned, provide defaults.
      if (!$json && $this->cdnExceptions && $package === 'bootstrap') {
        $json = ['versions' => [Bootstrap::FRAMEWORK_VERSION]];
      }

      return $json;
    }

    $response = $this->requestJson("$uri@$version/flat", $options);
    $json = $response->getJson();

    // If bootstrap JSON could not be returned, provide defaults.
    if (!$json && $this->cdnExceptions && $package === 'bootstrap') {
      return [
        '/dist/css/bootstrap.css',
        '/dist/js/bootstrap.js',
        '/dist/css/bootstrap.min.css',
        '/dist/js/bootstrap.min.js',
      ];
    }

    // Parse the files from JSON.
    return $this->parseFiles($json);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   */
  public function processDefinition(array &$definition, $plugin_id) {
    // Intentionally left blank so it doesn't trigger a deprecation warning.
  }

}
