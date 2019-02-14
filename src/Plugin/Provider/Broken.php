<?php

namespace Drupal\bootstrap\Plugin\Provider;

use Drupal\bootstrap\Plugin\PluginBase;

/**
 * Broken CDN Provider instance.
 *
 * @ingroup plugins_provider
 *
 * @BootstrapProvider(
 *   id = "_broken",
 *   label = @Translation("Broken"),
 * )
 */
class Broken extends PluginBase implements ProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function alterFrameworkLibrary(array &$framework, $min = NULL) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getAssets($types = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCdnAssets($version = NULL, $theme = NULL) {
    return [];
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
  public function getCdnVersions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Broken CDN Provider instance.');
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t('Broken');
  }

  /**
   * {@inheritdoc}
   */
  public function getCdnTheme() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getThemes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCdnVersion() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersions() {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   */
  public function getApi() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   */
  public function hasError() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   */
  public function isImported() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   */
  public function processDefinition(array &$definition, $plugin_id) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in 8.x-3.18, will be removed in a future release.
   */
  public function processApi(array $json, array &$definition) {
    // Intentionally left empty.
  }

}
