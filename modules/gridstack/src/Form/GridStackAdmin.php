<?php

/**
 * @file
 * Contains \Drupal\gridstack\Form\GridStackAdmin.
 */

namespace Drupal\gridstack\Form;

use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\blazy\Form\BlazyAdminInterface;
use Drupal\gridstack\GridStackManagerInterface;

/**
 * Provides resusable admin functions or form elements.
 */
class GridStackAdmin implements GridStackAdminInterface {

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface.
   */
  protected $blazyAdmin;

  /**
   * The gridstack manager service.
   *
   * @var \Drupal\gridstack\GridStackManagerInterface.
   */
  protected $manager;

  /**
   * Constructs a GridStackAdmin object.
   *
   * @param \Drupal\blazy\Form\BlazyAdminInterface $blazy_admin
   *   The blazy admin service.
   * @param \Drupal\gridstack\GridStackManagerInterface $manager
   *   The gridstack manager service.
   */
  public function __construct(BlazyAdminInterface $blazy_admin, GridStackManagerInterface $manager) {
    $this->blazyAdmin = $blazy_admin;
    $this->manager    = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('blazy.admin.extended'), $container->get('gridstack.manager'));
  }

  /**
   * Returns the blazy admin formatter.
   */
  public function blazyAdmin() {
    return $this->blazyAdmin;
  }

  /**
   * Returns the slick manager.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Returns all settings form elements.
   */
  public function buildSettingsForm(array &$form, $definition = []) {
    $definition += [
      'namespace'  => 'gridstack',
      'optionsets' => $this->blazyAdmin->getOptionsetOptions('gridstack'),
      'skins'      => $this->getSkinOptions(),
    ];

    foreach (['background', 'caches', 'fieldable_form', 'id', 'vanilla'] as $key) {
      $definition[$key] = TRUE;
    }

    $definition['layouts'] = isset($definition['layouts']) ? array_merge($this->getLayoutOptions(), $definition['layouts']) : $this->getLayoutOptions();

    $this->openingForm($form, $definition);
    $this->mainForm($form, $definition);
    $this->closingForm($form, $definition);
  }

  /**
   * Returns the opening form elements.
   */
  public function openingForm(array &$form, $definition = []) {
    $path   = drupal_get_path('module', 'gridstack');
    $readme = Url::fromUri('base:' . $path . '/README.txt')->toString();

    if (!isset($form['optionset'])) {
      $this->blazyAdmin->openingForm($form, $definition);
    }

    $form['skin']['#description'] = t('Skins allow various layouts with just CSS. Some options below depend on a skin. Leave empty to DIY. Or use hook_gridstack_skins_info() and implement \Drupal\gridstack\GridStackSkinInterface to register ones.', [':url' => $readme]);
    $form['background']['#description'] = t('If trouble with image sizes not filling the given box, check this to turn the image into CSS background instead. To assign different image style per grid/box, edit the working optionset.');
  }

  /**
   * Returns the main form elements.
   */
  public function mainForm(array &$form, $definition = []) {
    if (!isset($form['image'])) {
      $this->blazyAdmin->fieldableForm($form, $definition);
    }
  }

  /**
   * Returns the closing ending form elements.
   */
  public function closingForm(array &$form, $definition = []) {
    if (!isset($form['cache'])) {
      $this->blazyAdmin->closingForm($form, $definition);
    }

    $form['#attached']['library'][] = 'gridstack/admin';
  }

  /**
   * Returns available skins for select options.
   */
  public function getSkinOptions() {
    $skins = &drupal_static(__METHOD__, NULL);
    if (!isset($skins)) {
      $skins = [];
      foreach ($this->manager->getSkins() as $skin => $properties) {
        $skins[$skin] = Html::escape($properties['name']);
      }
    }

    return $skins;
  }

  /**
   * Returns default layout options for the core Image, or Views.
   */
  public function getLayoutOptions() {
    return [
      'bottom' => t('Caption bottom'),
      'center' => t('Caption center'),
      'top'    => t('Caption top'),
    ];
  }

}
