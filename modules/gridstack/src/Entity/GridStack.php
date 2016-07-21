<?php

/**
 * @file
 * Contains \Drupal\gridstack\Entity\GridStack.
 */

namespace Drupal\gridstack\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the GridStack configuration entity.
 *
 * @ConfigEntityType(
 *   id = "gridstack",
 *   label = @Translation("GridStack optionset"),
 *   list_path = "admin/structure/gridstack",
 *   config_prefix = "optionset",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "label",
 *     "status",
 *     "weight",
 *     "options",
 *     "json",
 *   }
 * )
 */
class GridStack extends ConfigEntityBase implements GridStackInterface {

  /**
   * The supported $breakpoints.
   *
   * @const $breakpoints.
   */
  private static $breakpoints = ['xs', 'sm', 'md', 'lg'];

  /**
   * The excluded $breakpoints.
   *
   * @const $excludedBreakpoints.
   */
  private static $excludedBreakpoints = ['xs',  'lg'];

  /**
   * The legacy CTools ID for the configurable optionset.
   *
   * @var string
   */
  protected $name;

  /**
   * The human-readable name for the optionset.
   *
   * @var string
   */
  protected $label;

  /**
   * The weight to re-arrange the order of gridstack optionsets.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The plugin instance json to reduce frontend logic.
   *
   * @var string
   */
  protected $json = [];

  /**
   * The plugin instance options.
   *
   * @var array
   */
  protected $options = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type = 'gridstack') {
    parent::__construct($values, $entity_type);
  }

  /**
   * Returns the supported breakpoints.
   */
  public static function getConstantBreakpoints() {
    return self::$breakpoints;
  }

  /**
   * Returns the excluded breakpoints.
   */
  public static function getExcludedBreakpoints() {
    return self::$excludedBreakpoints;
  }

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($group = NULL, $property = NULL) {
    $default = $this->load('default');
    $options = $this->options ?: $default->options;
    if ($group) {
      if (is_array($group)) {
        return NestedArray::getValue($options, $group);
      }
      elseif (isset($property) && isset($options[$group])) {
        return isset($options[$group][$property]) ? $options[$group][$property] : NULL;
      }
      return isset($options[$group]) ? $options[$group] : $options;
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($option_name) {
    $default  = $this->load('default');
    $settings = $default->options['settings'];
    $default  = isset($settings[$option_name]) ? $settings[$option_name] : NULL;
    return isset($this->options['settings'][$option_name]) ? $this->options['settings'][$option_name] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getJson($group = '') {
    $default  = $this->load('default');
    $defaults = isset($default->json[$group]) ? $default->json[$group] : '';
    return $group && isset($this->json[$group]) ? $this->json[$group] : $defaults;
  }

  /**
   * The string json grids contains: node, nested, breakpoints definitions.
   */
  public function getJsonGrids($group = 'node') {
    $grids = $this->getJson('grids');
    return isset($grids[$group]) ? $grids[$group] : '';
  }

  /**
   * Returns the grids with/without node property.
   */
  public function getGrids($grid_only = TRUE) {
    $default = $this->load('default');
    $grids   = $this->getOptions('grids') ?: $default->getOptions('grids');
    $build   = [];
    foreach ($grids as $key => $grid) {
      $build[] = $grid_only && isset($grid['node']) ? $grid['node'] : $grid;
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsGrids($i = 0, $property = '', $node = 'node') {
    $grids = $this->getGrids(FALSE);
    $nodes  = isset($grids[$i][$node]) ? $grids[$i][$node] : [];
    return $property && isset($nodes[$property]) ? $nodes[$property] : '';
  }

  /**
   * Returns options.breakpoints.sm, etc.
   */
  public function getBreakpoints($breakpoint = NULL) {
    $breakpoints = $this->getOptions('breakpoints') ?: [];
    if ($breakpoint && isset($breakpoints[$breakpoint])) {
      return $breakpoints[$breakpoint];
    }
    return $breakpoints;
  }

  /**
   * Returns options.breakpoints.sm.[width, column, image_style, grids], etc.
   */
  public function getBreakpointGrids($breakpoint = '', $index = '', $property = '') {
    if ($breakpoint) {
      $grids = $this->getBreakpoints($breakpoint)['grids'];
      if ($grids && is_string($grids)) {
        $grids = Json::decode($grids);
        $grid = isset($index) && isset($grids[$index]) ? $grids[$index] : $grids;
        return $property && isset($grid[$property]) ? $grid[$property] : $grid;
      }
      return $grids;
    }
    return [];
  }

  /**
   * Returns options.breakpoints.sm.[width, column, image_style, grids], etc.
   */
  public function getItemBreakpointGrids($breakpoint = '', $index = '', $property = '') {
    if ($breakpoint) {
      $grid = $this->getBreakpointGrids($breakpoint, $index);
      return $property && isset($grid[$property]) ? $grid[$property] : '';
    }
    return '';
  }

  /**
   * Returns JSON for options.breakpoints.
   */
  public function getJsonBreakpointGrids($breakpoint = '', $exclude_image_style = FALSE, $no_keys = FALSE) {
    if ($breakpoint) {
      $grids = $this->getBreakpointGrids($breakpoint);
      if ($grids) {
        $values = [];
        foreach ($grids as $key => &$grid) {
          if ($exclude_image_style && isset($grid['image_style'])) {
            array_pop($grid);
          }
          $values[] = array_values($grid);
        }
        // Simplify and remove keys:
        // Original: [{"x":1,"y":0,"width":2,"height":8},
        // Now: [[1,0,2,8],
        if ($no_keys) {
          $grids = $values;
        }
      }
      return $grids ? Json::encode($grids) : '';
    }
    return '';
  }

  /**
   * Returns HTML or layout related settings, none of JS to shutup notices.
   */
  public static function htmlSettings() {
    return [
      'background'   => TRUE,
      'id'           => '',
      'media_switch' => '',
      'optionset'    => 'default',
      'skin'         => '',
    ];
  }

}
