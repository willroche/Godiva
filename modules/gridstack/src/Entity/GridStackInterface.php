<?php

/**
 * @file
 * Contains \Drupal\gridstack\Entity\GridStackInterface.
 */

namespace Drupal\gridstack\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining GridStack entity.
 */
interface GridStackInterface extends ConfigEntityInterface {

  /**
   * Returns the GridStack options by group, or property.
   *
   * @param string $group
   *   The name of setting group: breakpoints, grids, settings.
   * @param string $property
   *   The name of specific property: resizable, draggable, etc.
   *
   * @return mixed|array|NULL
   *   Available options by $group, $property, all, or NULL.
   */
  public function getOptions($group = NULL, $property = NULL);

  /**
   * Returns the value of a gridstack setting.
   *
   * @param string $option_name
   *   The option name.
   *
   * @return mixed
   *   The option value.
   */
  public function getSetting($option_name);

  /**
   * Returns the GridStack json suitable for HTML data-attribute.
   *
   * @param string $group
   *   The option group can be settings or grids.
   *
   * @return string
   *   The output of the GridStack json.
   */
  public function getJson($group = 'settings');

}
