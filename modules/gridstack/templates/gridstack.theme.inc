<?php

/**
 * @file
 * Hooks and preprocess functions for the GridStack module.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\blazy\Blazy;
use Drupal\gridstack\Entity\GridStack;

/**
 * Prepares variables for gridstack.html.twig templates.
 */
function template_preprocess_gridstack(&$variables) {
  $element     = $variables['element'];
  $defaults    = GridStack::htmlSettings();
  $settings    = isset($element['#settings']) ? array_merge($defaults, $element['#settings']) : $defaults;
  $optionset   = isset($element['#optionset']) && is_object($element['#optionset']) ? $element['#optionset'] : GridStack::load($settings['optionset']);
  $breakpoints = isset($element['#breakpoints']) ? $element['#breakpoints'] : $optionset->getJson('breakpoints');
  $js          = isset($element['#options']) ? $element['#options'] : $optionset->getJson('settings');
  $grids       = isset($element['#grids']) ? $element['#grids'] : $optionset->getGrids(FALSE);

  // Prepare attributes, and add the configuration as JSON object.
  $id = Blazy::getHtmlId('gridstack', $settings['id']);
  $blazy = empty($settings['blazy_data']) ? '' : Json::encode($settings['blazy_data']);

  $attributes = new Attribute($variables['attributes']);
  $attributes->setAttribute('id', $id);
  $attributes->addClass('blazy');

  // {"320":1,"640":2,"768":3,"1140":12} to update columns by window widths.
  $attributes->setAttribute('data-breakpoints', $breakpoints);
  $attributes->setAttribute('data-config', $js);
  $attributes->setAttribute('data-blazy', $blazy);

  // Gets options.breakpoints.sm.[width, column, image_style, grids], etc.
  $responsives = $optionset->getBreakpoints();
  $exclude_image_style = empty($settings['_admin']);
  if (!empty($responsives)) {
    foreach (GridStack::getConstantBreakpoints() as $breakpoint) {
      // Skip XS and LG. XS is a disabled state. LG is desktop.
      if (in_array($breakpoint, GridStack::getExcludedBreakpoints())) {
        continue;
      }

      $responsive_grids = $optionset->getJsonBreakpointGrids($breakpoint, $exclude_image_style, TRUE);
      if (!empty($responsives[$breakpoint]['width']) && $responsive_grids) {
        $attributes->setAttribute('data-' . $breakpoint . '-width', $responsives[$breakpoint]['width']);
        $attributes->setAttribute('data-' . $breakpoint . '-grids', $responsive_grids);
      }
    }
  }

  // Breakpoint related data-attributes helpers.
  if (!empty($settings['minWidth'])) {
    $attributes->setAttribute('data-min-width', $settings['minWidth']);
  }

  // Pass settings and attributes to twig.
  $settings['nested']      = isset($settings['nested']) ? $settings['nested'] : FALSE;
  $settings['class_by_id'] = strpos($id, 'gridstack-') !== FALSE ? str_replace('gridstack-', 'gridstack--', $id) : '';
  $variables['settings']   = $settings;
  $variables['attributes'] = $attributes;

  // Process individual item.
  $variables['items'] = [];
  foreach ($element['#items'] as $delta => $item) {
    $item_attributes = isset($item['attributes']) ? $item['attributes'] : [];
    $item_settings   = isset($item['settings']) ? array_merge($settings, $item['settings']) : $settings;
    unset($item['settings']);

    if ($grids) {
      // Base: x, y, width, height.
      // Extra: autoPosition, minWidth, maxWidth, minHeight, maxHeight, id.
      foreach (['x', 'y', 'width', 'height'] as $key) {
        $item_attributes['data-gs-' . $key] = isset($grids[$delta]['node'][$key]) ? $grids[$delta]['node'][$key] : $grids[$delta][$key];
      }
    }
    $box = [
      '#theme'      => 'gridstack_box',
      '#item'       => $item,
      '#attributes' => $item_attributes,
      '#delta'      => $delta,
      '#settings'   => $item_settings,
    ];
    $variables['items'][$delta] = $box;
    unset($box);
  }
}

/**
 * Prepares variables for gridstack-box.html.twig templates.
 */
function template_preprocess_gridstack_box(&$variables) {
  foreach (['attributes', 'delta', 'item', 'settings'] as $key) {
    $variables[$key] = isset($variables['element']["#$key"]) ? $variables['element']["#$key"] : [];
  }

  $attributes = &$variables['attributes'];
  $content_attributes = &$variables['content_attributes'];

  // Boxes may have captions.
  foreach (['data', 'link', 'title'] as $key) {
    $variables['item']['caption'][$key] = isset($variables['item']['caption'][$key]) ? $variables['item']['caption'][$key] : [];
  }

  $item     = &$variables['item'];
  $settings = &$variables['settings'];

  if ($settings['background'] && !empty($settings['image_url'])) {
    $content_attributes['data-src'] = $settings['image_url'];

    // Build multi-serving images based on $settings['breakpoints'] if provided.
    Blazy::buildBreakpointAttributes($content_attributes, $settings);
  }

  $item['caption'] = array_filter($item['caption']);
  $variables['content_attributes'] = new Attribute($content_attributes);
}

/**
 * Prepares variables for gridstack-admin.html.twig templates.
 */
function template_preprocess_gridstack_admin(&$variables) {
  template_preprocess_gridstack($variables);
  $element = $variables['element'];
  foreach (['preview_attributes', 'content_attributes', 'image_styles'] as $key) {
    $variables[$key] = isset($variables['element']["#$key"]) ? $variables['element']["#$key"] : [];
  }

  $content_attributes = &$variables['content_attributes'];
  if (!empty($variables['attributes'])) {
    $content_attributes = NestedArray::mergeDeep($variables['attributes'], $content_attributes);
  }

  $variables['storage'] = isset($element['#storage']) ? $element['#storage'] : '';
  $variables['content_attributes'] = new Attribute($content_attributes);
  $variables['preview_attributes'] = new Attribute($variables['preview_attributes']);

  $variables['main_button_texts'] = [
    'save'  => t('Save & Continue'),
    'load'  => t('Load Grid'),
    'clear' => t('Clear Grid'),
  ];

  if ($variables['settings']['display'] == 'main') {
    $variables['main_button_texts']['add'] = t('Add Grid');
  }
}

/**
 * Prepares variables for gridstack-admin.html.twig templates.
 */
function template_preprocess_gridstack_dummy(&$variables) {
  $element = $variables['element'];
  foreach (['storage', 'dummies', 'image_style', 'settings'] as $key) {
    $variables[$key] = isset($element["#$key"]) ? $element["#$key"] : [];
  }
  $variables['content'] = $element['#children'];
}
