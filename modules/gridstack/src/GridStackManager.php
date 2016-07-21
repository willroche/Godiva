<?php

/**
 * @file
 * Contains \Drupal\gridstack\GridStackManager.
 */

namespace Drupal\gridstack;

use Drupal\Core\Cache\Cache;
use Drupal\Component\Utility\NestedArray;
use Drupal\blazy\BlazyManagerBase;
use Drupal\blazy\BlazyManagerInterface;
use Drupal\gridstack\Entity\GridStack;

/**
 * Implements GridStackManagerInterface.
 */
class GridStackManager extends BlazyManagerBase implements BlazyManagerInterface, GridStackManagerInterface {

  /**
   * Returns defined skins as registered via hook_gridstack_skins_info().
   */
  public function getSkins() {
    $skins = &drupal_static(__METHOD__, NULL);
    if (!isset($skins)) {
      $skins = $this->buildSkins('gridstack', '\Drupal\gridstack\GridStackSkin');
    }
    return $skins;
  }

  /**
   * Returns array of needed assets suitable for #attached for the given gridstack.
   */
  public function attach($attach = []) {
    $attach += ['skin' => FALSE, 'lazy' => 'blazy'];
    $load   = parent::attach($attach);

    if ($this->configLoad('customized', 'gridstack.settings')) {
      $load['library'][] = 'gridstack/customized';
    }
    else {
      if ($this->configLoad('jquery_ui', 'gridstack.settings')) {
        $load['library'][] = 'gridstack/ui';
      }
      $load['library'][] = 'gridstack/load';
    }

    if (!empty($attach['width']) && $attach['width'] < 12) {
      $load['library'][] = 'gridstack/gridstack.' . $attach['width'];
    }

    // Breakpoints: xs sm md lg requires separate CSS files.
    if (!empty($attach['breakpoints'])) {
      foreach ($attach['breakpoints'] as $breakpoint) {
        if (!empty($breakpoint['column']) && $breakpoint['column'] < 12) {
          $load['library'][] = 'gridstack/gridstack.' . $breakpoint['column'];
        }
      }
    }

    if ($skin = $attach['skin']) {
      $skins = $this->getSkins();
      $provider = isset($skins[$skin]['provider']) ? $skins[$skin]['provider'] : 'gridstack';
      $load['library'][] = 'gridstack/' . $provider . '.' . $skin;
    }

    $load['drupalSettings']['gridstack'] = GridStack::load('default')->getOptions('settings');

    $this->moduleHandler->alter('gridstack_attach', $load, $attach);
    return $load;
  }

  /**
   * {@inheritdoc}
   */
  public function build($build = []) {
    foreach (['items', 'optionset', 'settings'] as $key) {
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    $gridstack = [
      '#theme'      => 'gridstack',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderGridStack']],
    ];

    $settings            = $build['settings'];
    $suffixes[]          = count($build['items']);
    $suffixes[]          = count(array_filter($settings));
    $suffixes[]          = $settings['cache'];
    $cache['tags']       = Cache::buildTags('gridstack:' . $settings['id'], $suffixes, '.');
    $cache['contexts']   = ['languages'];
    $cache['max-age']    = $settings['cache'];
    $cache['keys']       = isset($settings['cache_metadata']['keys']) ? $settings['cache_metadata']['keys'] : [$settings['id']];
    $gridstack['#cache'] = $cache;

    return $gridstack;
  }

  /**
   * {@inheritdoc}
   */
  public function preRenderGridStack($element) {
    $build = $element['#build'];
    unset($element['#build']);

    if (empty($build['items'])) {
      return [];
    }

    // Build gridstack elements.
    $defaults    = GridStack::htmlSettings();
    $settings    = $build['settings'] ? array_merge($defaults, $build['settings']) : $defaults;
    $optionset   = $build['optionset'] ?: GridStack::load($settings['optionset']);
    $settings    = array_merge($settings, $optionset->getOptions('settings'));
    $attachments = $this->attach($settings);

    $element['#optionset'] = $optionset;
    $element['#settings']  = $settings;
    $element['#attached']  = empty($build['attached']) ? $attachments : NestedArray::mergeDeep($build['attached'], $attachments);
    $element['#items']     = $build['items'];

    return $element;
  }

}
