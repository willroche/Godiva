<?php

/**
 * @file
 * Contains \Drupal\gridstack_ui\Controller\GridStackListBuilder.
 */

namespace Drupal\gridstack_ui\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\gridstack\GridStackManagerInterface;

/**
 * Provides a listing of GridStack optionsets.
 */
class GridStackListBuilder extends DraggableListBuilder {

  /**
   * The gridstack manager.
   *
   * @var \Drupal\gridstack\GridStackManagerInterface
   */
  protected $manager;

  /**
   * Constructs a new GridStackListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\gridstack\GridStackManagerInterface $manager
   *   The gridstack manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, GridStackManagerInterface $manager) {
    parent::__construct($entity_type, $storage);
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('gridstack.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gridstack_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
      'label' => t('Optionset'),
      'grids' => t('Grids'),
    );

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = Html::escape($this->getLabel($entity));

    $grids = $entity->getGrids();
    $row['grids']['#markup'] = count($grids);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Configure');
    }

    $operations['duplicate'] = array(
      'title'  => t('Duplicate'),
      'weight' => 15,
      'url'    => $entity->toUrl('duplicate-form'),
    );

    if ($entity->id() == 'default') {
      unset($operations['delete'], $operations['edit']);
    }
    if ($entity->id() == 'frondend') {
      unset($operations['delete']);
    }

    return $operations;
  }

  /**
   * Adds some descriptive text to the gridstack optionsets list.
   *
   * @return array
   *   Renderable array.
   *
   * @see admin/config/development/configuration/single/export
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t("<p>Manage the GridStack optionsets. Optionsets are Config Entities.</p><p>By default, when this module is enabled, two optionsets are created from configuration: Default Admin and Default Frontend. Install GridStack example module to speed up by cloning them. Use the Operations column to edit, clone and delete optionsets.<br /><strong>Important!</strong><br />Avoid overriding Default Admin optionset as it is meant for Default -- checking and cleaning the frontend. Use Duplicate Default Frontend instead. Otherwise possible messes.</p>"),
    );

    $build[] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    drupal_set_message($this->t('The optionsets order has been updated.'));
  }

}
