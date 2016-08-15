<?php

namespace Drupal\yamlform_templates\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for YAML form templates.
 */
class YamlFormTemplatesController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * YAML form storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $yamlformStorage;

  /**
   * Constructs a YamlFormTemplatesController object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->yamlformStorage = $entity_manager->getStorage('yamlform');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * Returns the YAML form templates index page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array representing the YAML form templates index page.
   */
  public function index(Request $request) {
    $keys = $request->get('search');

    // Handler autocomplete redirect.
    if ($keys && preg_match('#\(([^)]+)\)$#', $keys, $match)) {
      if ($yamlform = $this->yamlformStorage->load($match[1])) {
        return new RedirectResponse($yamlform->toUrl()->setAbsolute(TRUE)->toString());
      }
    }

    $header = [
      $this->t('Title'),
      ['data' => $this->t('Description'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      $this->t('Operations'),
    ];

    $yamlforms = $this->getTemplates($keys);
    $rows = [];
    foreach ($yamlforms as $yamlform) {
      $route_parameters = ['yamlform' => $yamlform->id()];

      $row['title'] = $yamlform->toLink();
      $row['description']['data']['description']['#markup'] = $yamlform->get('description');
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => [
          'duplicate' => [
            'title' => $this->t('Duplicate'),
            'url' => Url::fromRoute('entity.yamlform.duplicate_form', $route_parameters),
            'attributes' => [
              'class' => [_yamlform_use_ajax('dialog')],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 400,
              ]),
            ],
          ],
          'view' => [
            'title' => $this->t('Preview'),
            'url' => Url::fromRoute('entity.yamlform.canonical', $route_parameters),
          ],
        ],
      ];
      $rows[] = $row;
    }

    $build = [];
    $build['filter_form'] = \Drupal::formBuilder()->getForm('\Drupal\yamlform_templates\Form\YamlFormTemplatesFilterForm', $keys);
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There is no templates yet.'),
      '#cache' => [
        'contexts' => $this->yamlformStorage->getEntityType()->getListCacheContexts(),
        'tags' => $this->yamlformStorage->getEntityType()->getListCacheTags(),
      ],
    ];
    $build['#attached']['library'][] = 'yamlform/yamlform.admin';

    return $build;
  }

  /**
   * Get YAML form templates.
   *
   * @param string $keys
   *   (optional) Filter templates by key word.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   An array YAML form entity that are used as templates.
   */
  protected function getTemplates($keys = '') {
    $query = $this->yamlformStorage->getQuery();
    $query->condition('template', TRUE);
    // Filter by key(word).
    if ($keys) {
      $or = $query->orConditionGroup()
        ->condition('title', $keys, 'CONTAINS')
        ->condition('description', $keys, 'CONTAINS')
        ->condition('elements', $keys, 'CONTAINS');
      $query->condition($or);
    }

    $query->sort('title');

    $entity_ids = $query->execute();
    return ($entity_ids) ? $this->yamlformStorage->loadMultiple($entity_ids) : [];
  }

}
