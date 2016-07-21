<?php

/**
 * @file
 * Contains \Drupal\gridstack\GridStackSkin.
 */

namespace Drupal\gridstack;

/**
 * Implements GridStackSkinInterface.
 */
class GridStackSkin implements GridStackSkinInterface {

  /**
   * {@inheritdoc}
   */
  public function skins() {
    $skins = [
      'default' => [
        'name' => t('Default'),
        'provider' => 'gridstack',
        'css' => [
          'theme' => [
            'css/theme/gridstack.theme--default.css' => [],
          ],
        ],
      ],
      'selena' => [
        'name' => t('Selena'),
        'provider' => 'gridstack',
        'css' => [
          'theme' => [
            'css/theme/gridstack.theme--selena.css' => [],
          ],
        ],
        'description' => t('Provide Selena skin.'),
      ],
    ];

    return $skins;
  }

}
