<?php

/**
 * @file
 * Drupal Bootstrap Drush commands.
 */

use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Theme;
use Drupal\Component\Serialization\Yaml;

/**
 * Implements hook_drush_command().
 */
function bootstrap_drush_command() {
  $items['bootstrap-generate-docs'] = [
    'description' => dt('Generates markdown documentation for the Drupal based code.'),
    'arguments' => [
      'type' => 'The specific type of documentation to generate, defaults to "all". Can be: "all", "settings".',
    ],
    'aliases' => ['bs-docs'],
  ];
  return $items;
}

/**
 * Generates markdown documentation.
 *
 * @param string $type
 *   The type of documentation.
 */
function drush_bootstrap_generate_docs($type = 'all') {
  $types = $type === 'all' ? ['settings'] : [$type];
  foreach ($types as $type) {
    $function = "_drush_bootstrap_generate_docs_$type";
    if (function_exists($function)) {
      $ret = $function(Bootstrap::getTheme('bootstrap'));
      if ($ret) {
        drush_log('Successfully generated documentation for: ' . $type, 'success');
      }
      else {
        drush_log('Unable to generate documentation for: ' . $type, 'error');
      }
    }
    else {
      drush_log('Invalid documentation type: ' . $type, 'error');
    }
  }
}

/**
 * Generates settings documentation.
 *
 * @param \Drupal\bootstrap\Theme $bootstrap
 *   The theme instance of the Drupal Bootstrap base theme.
 */
function _drush_bootstrap_generate_docs_settings(Theme $bootstrap) {
  $filename = realpath($bootstrap->getPath() . '/docs/Theme-Settings.md');
  $marker_start = "<!-- THEME SETTINGS GENERATION START -->";
  $marker_end = "<!-- THEME SETTINGS GENERATION END -->\n";
  $contents = @file_get_contents($filename) ?: '';
  $parts = @preg_split('/' . preg_quote($marker_start, '/') . '|' . preg_quote($marker_end, '/') . '/', $contents) ?: [];
  $start = isset($parts[0]) ? [trim($parts[0])] : [];
  $end = isset($parts[2]) ? [trim($parts[2])] : [];

  // Determine the groups.
  $groups = [
    'general' => [],
    'components' => [],
    'javascript' => [],
    'cdn' => [],
    'advanced' => [],
  ];
  foreach ($bootstrap->getSettingPlugin() as $setting) {
    // Only get the first two groups (we don't need 3rd, or more, levels).
    $_groups = array_slice(array_filter($setting->getGroups()), 0, 2, FALSE);
    if (!$_groups) {
      continue;
    }
    $groups[array_keys($_groups)[0]][implode(' > ', $_groups)][] = $setting->getPluginDefinition();
  }

  // Generate a table of each group's settings.
  $lines = [$marker_start];
  foreach ($groups as $subgroups) {
    foreach ($subgroups as $group => $settings) {
      $lines[] = '';
      $lines[] = '---';
      $lines[] = '';
      $lines[] = "### $group";
      $lines[] = '';
      $lines[] = '<table class="table table-striped table-responsive">';
      $lines[] = '  <thead>';
      $lines[] = '    <tr>';
      $lines[] = '      <th class="col-xs-3">Setting name</th>';
      $lines[] = '      <th>Description and default value</th>';
      $lines[] = '    </tr>';
      $lines[] = '  </thead>';
      $lines[] = '  <tbody>';
      foreach ($settings as $definition) {
        $lines[] = '  <tr>';
        $lines[] = '    <td class="col-xs-3">';
        $lines[] = $definition['id'];
        $lines[] = '    </td>';
        $lines[] = '    <td>';
        if ($description = trim(str_replace('&quot;', '"', $definition['description']))) {
          $lines[] = '      <div class="help-block">' . $description . '</div>';
        }
        if ($example = trim(Yaml::encode([$definition['id'] => $definition['defaultValue']]))) {
          $lines[] = '      <pre class="language-yaml"><code>' . $example . '</code></pre>';
        }
        $lines[] = '    </td>';
        $lines[] = '  </tr>';
      }
      $lines[] = '  </tbody>';
      $lines[] = '</table>';
    }
  }
  $lines[] = $marker_end;

  // Ensure we have link references at the bottom.
  $output = implode("\n", array_merge($start, $lines, $end)) . "\n";

  // Save the generated output to the appropriate file.
  return file_put_contents($filename, $output) !== FALSE;
}
