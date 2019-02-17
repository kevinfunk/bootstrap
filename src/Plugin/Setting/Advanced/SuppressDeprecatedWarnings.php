<?php

namespace Drupal\bootstrap\Plugin\Setting\Advanced;

use Drupal\bootstrap\Plugin\Setting\SettingBase;

/**
 * The "suppress_deprecated_warnings" theme setting.
 *
 * @ingroup plugins_setting
 *
 * @BootstrapSetting(
 *   id = "suppress_deprecated_warnings",
 *   type = "checkbox",
 *   weight = -2,
 *   title = @Translation("Suppress deprecated warnings"),
 *   defaultValue = 0,
 *   description = @Translation("Enable this setting if you wish to suppress deprecated warning messages. <strong class='error text-error'>WARNING: Suppressing these messages does not &quot;fix&quot; the problem and you will inevitably encounter issues when they are removed in future updates. Only use this setting in extreme and necessary circumstances.</strong>"),
 *   groups = {
 *     "advanced" = @Translation("Advanced"),
 *   },
 * )
 */
class SuppressDeprecatedWarnings extends SettingBase {}
