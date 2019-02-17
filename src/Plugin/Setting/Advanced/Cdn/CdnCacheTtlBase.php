<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

/**
 * Due to BC reasons, this class cannot be moved.
 *
 * @todo Move namespace up one.
 */

use Drupal\bootstrap\Plugin\Provider\ProviderInterface;
use Drupal\bootstrap\Utility\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for "cdn_cache_ttl_*" settings.
 *
 * @ingroup plugins_setting
 */
class CdnCacheTtlBase extends CdnProviderBase {

  /**
   * The DateFormatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  static protected $dateFormatter;

  /**
   * A list of TTL options.
   *
   * @var array
   */
  static protected $ttlOptions;

  /**
   * {@inheritdoc}
   */
  public function alterFormElement(Element $form, FormStateInterface $form_state, $form_id = NULL) {
    $setting = $this->getSettingElement($form, $form_state);
    $setting->setProperty('options', $this->getTtlOptions());
    $setting->setProperty('access', $this->getSettingAccess());
  }

  /**
   * Retrieves the access value for the setting.
   *
   * @return bool
   *   TRUE or FALSE
   */
  protected function getSettingAccess() {
    return TRUE;
  }

  /**
   * Retrieves the TTL options.
   */
  protected function getTtlOptions() {
    if (!isset(static::$ttlOptions)) {
      $dateFormatter = $this->getDateFormatter();
      $intervals = [
        ProviderInterface::TTL_NEVER,
        ProviderInterface::TTL_ONE_DAY,
        ProviderInterface::TTL_ONE_WEEK,
        ProviderInterface::TTL_ONE_MONTH,
        ProviderInterface::TTL_THREE_MONTHS,
        ProviderInterface::TTL_SIX_MONTHS,
        ProviderInterface::TTL_ONE_YEAR,
      ];
      static::$ttlOptions = array_map([$dateFormatter, 'formatInterval'], array_combine($intervals, $intervals));
      static::$ttlOptions[ProviderInterface::TTL_NEVER] = (string) $this->t('Never');
      static::$ttlOptions[ProviderInterface::TTL_FOREVER] = (string) $this->t('Forever');
    }
    return static::$ttlOptions;
  }

  /**
   * Retrieves the DateFormatter service.
   *
   * @return \Drupal\Core\Datetime\DateFormatterInterface
   *   The DateFormatter service.
   */
  protected function getDateFormatter() {
    if (!isset(static::$dateFormatter)) {
      static::$dateFormatter = \Drupal::service('date.formatter');
    }
    return static::$dateFormatter;
  }

}
