<?php

/**
 * @file
 * Contains \Drupal\gridstack_example\GridStackExampleSkin.
 */

namespace Drupal\gridstack_example;

use Drupal\gridstack\GridStackSkinInterface;

/**
 * Implements GridStackSkinInterface as registered via hook_gridstack_skins_info().
 */
class GridStackExampleSkin implements GridStackSkinInterface {

  /**
   * {@inheritdoc}
   */
  public function skins() {
    $path  = base_path() . drupal_get_path('module', 'gridstack_example');
    $skins = [
      'zoe' => [
        'name' => t('X: Zoe'),
        'description' => t('A sample skin for GridStack.'),
        'provider' => 'gridstack_example',
        'css' => [
          'theme' => [
            $path . '/css/gridstack.theme--zoe.css' => [],
          ],
        ],
      ],
    ];

    return $skins;
  }

}
