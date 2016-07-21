<?php

/**
 * @file
 * Contains \Drupal\gridstack\GridStackManagerInterface.
 */

namespace Drupal\gridstack;

/**
 * Defines re-usable services and functions for gridstack plugins.
 */
interface GridStackManagerInterface {

  /**
   * Returns a cacheable renderable array of a single gridstack instance.
   *
   * @param array $build
   *   An associative array containing:
   *   - items: An array of gridstack contents: text, image or media.
   *   - options: An array of key:value pairs of custom JS options.
   *   - optionset: The cached optionset object to avoid multiple invocations.
   *   - settings: An array of key:value pairs of HTML/layout related settings.
   *
   * @return array
   *   The cacheable renderable array of a gridstack instance, or empty array.
   */
  public function build($build = []);

}
