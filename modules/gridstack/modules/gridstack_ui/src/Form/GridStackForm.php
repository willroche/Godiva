<?php

/**
 * @file
 * Contains \Drupal\gridstack_ui\Form\GridStackForm.
 */

namespace Drupal\gridstack_ui\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\gridstack\Entity\GridStack;
use Drupal\blazy\Form\BlazyAdminInterface;
use Drupal\blazy\BlazyManagerInterface;

/**
 * Extends base form for gridstack instance configuration form.
 */
class GridStackForm extends EntityForm {

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface.
   */
  protected $blazyAdmin;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface.
   */
  protected $blazyManager;

  /**
   * Constructs a GridStackForm object.
   */
  public function __construct(BlazyAdminInterface $blazy_admin, BlazyManagerInterface $blazy_manager) {
    $this->blazyAdmin = $blazy_admin;
    $this->blazyManager = $blazy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('blazy.admin'), $container->get('blazy.manager'));
  }

  /**
   * {@inheritdoc}
   *
   * @todo Bootstrap v3 integration.
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Change page title for the duplicate operation.
    if ($this->operation == 'duplicate') {
      $form['#title'] = $this->t('<em>Duplicate gridstack optionset</em>: @label', ['@label' => $this->entity->label()]);
      $this->entity = $this->entity->createDuplicate();
    }

    // Change page title for the edit operation.
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit gridstack optionset</em>: @label', ['@label' => $this->entity->label()]);
    }

    $entity     = $this->entity;
    $path       = drupal_get_path('module', 'gridstack');
    $tooltip    = ['class' => ['is-tooltip']];
    $readme     = Url::fromUri('base:' . $path . '/README.txt')->toString();
    $default    = GridStack::load('default');
    $options    = $entity->getOptions();
    $settings   = $entity->getOptions('settings');
    $json_grids = $entity->getJsonGrids('node');
    $grids      = $entity->getGrids(FALSE);
    $is_nested  = $entity->getSetting('isNested');
    $admin_css  = $this->blazyManager->configLoad('admin_css', 'blazy.settings');
    $renderer   = $this->blazyManager->getRenderer();

    $num_grids = $form_state->get('num_grids') ?: count($grids);

    if (is_null($num_grids)) {
      $num_grids = 1;
    }
    $form_state->set('num_grids', $num_grids);

    $form['#attributes']['class'][] = 'form--gridstack';
    $form['#attributes']['class'][] = 'form--slick';
    $form['#attributes']['class'][] = 'form--optionset';
    $form['#attributes']['class'][] = 'form--gridstack--ui';
    $form['#attributes']['class'][] = 'form--gridstack--expanded';

    $form['#attached']['library'][] = 'blazy/blazy';
    $form['#attached']['library'][] = 'gridstack/gridstack';
    $form['#attached']['library'][] = 'gridstack/admin';
    $form['#attached']['drupalSettings']['gridstack'] = $default->load('default')->getOptions('settings');

    // Load all grids to get live preview going.
    foreach (range(1, 11) as $key) {
      $form['#attached']['library'][] = 'gridstack/gridstack.' . $key;
    }

    if ($admin_css) {
      $form['#attached']['library'][] = 'blazy/admin';
    }

    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#default_value' => $entity->label(),
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#description'   => $this->t("Label for the GridStack optionset."),
      '#attributes'    => $tooltip,
      '#prefix'        => '<div class="form__header form__half form__half--first has-tooltip clearfix">',
    ];

    // Keep the legacy CTools ID, i.e.: name as ID.
    $form['name'] = [
      '#type'          => 'machine_name',
      '#default_value' => $entity->id(),
      '#maxlength'     => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name'  => [
        'source' => ['label'],
        'exists' => '\Drupal\gridstack\Entity\GridStack::load',
      ],
      '#attributes'    => $tooltip,
      '#disabled'      => !$entity->isNew(),
      '#suffix'        => '</div>',
    ];

    $data_preview_settings = $this->jsonify($entity->getOptions('settings'), TRUE);
    $data_preview_grids    = Json::encode($entity->getGrids());

    $storage = 'edit-json-grids-node';
    $js_settings = [
      'lazy'       => '',
      'background' => FALSE,
      'breakpoint' => 'lg',
      'optionset'  => $entity->id(),
      '_admin'     => TRUE,
    ];

    $common_preview_elements = [
      '#type'           => 'container',
      '#theme_wrappers' => ['gridstack_admin'],
      '#breakpoints'    => $entity->getJson('breakpoints'),
      '#optionset'      => $entity,
    ];

    // Dummy template.
    $image_style_box = [
      '#type'         => 'select',
      '#options'      => image_style_options(TRUE),
      '#empty_option' => t('- IMG style -'),
      '#attributes'   => ['class' => ['form-select--image-style', 'form-select--original'], 'data-imageid' => '', 'id' => ''],
    ];

    // $image_style_box = $renderer->renderPlain($image_style_box);
    $form['template'] = [
      '#type'        => 'container',
      '#attributes'  => ['id' => 'gridstack-template', 'class' => ['visually-hidden']],
      '#dummies'     => TRUE,
      '#theme'       => ['gridstack_dummy'],
      '#image_style' => $image_style_box,
    ];

    // Preview template.
    $js_settings = array_merge($js_settings, $settings);
    $js_settings['display'] = 'main';
    $form['preview'] = $common_preview_elements + [
      '#storage'  => $storage,
      '#items'    => $this->getDummyItems($grids, $js_settings, $image_style_box),
      '#grids'    => $grids,
      '#settings' => $js_settings,
      '#content_attributes' => [
        'class'               => ['gridstack--main'],
        'data-config'         => $data_preview_settings,
        'data-preview-grids'  => $data_preview_grids,
        'data-storage'        => $storage,
        'data-current-column' => $settings['width'],
      ],
    ];

    $form['json'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => ['class' => 'gridstack-json'],
    ];

    $form['json']['grids'] = [
      '#type' => 'container',
    ];

    $form['json']['grids']['node'] = [
      '#type'          => 'hidden',
      '#default_value' => $data_preview_grids,
    ];

    // @todo or @nottodo, or just @toomuch @todo.
    $form['json']['grids']['nested'] = [
      '#type'          => 'hidden',
      '#default_value' => $entity->getJsonGrids('nested'),
    ];

    $form['json']['breakpoints'] = [
      '#type'          => 'hidden',
      '#default_value' => $entity->getJsonGrids('breakpoints'),
    ];

    $form['json']['settings'] = [
      '#type'          => 'hidden',
      '#default_value' => $entity->getJson('settings'),
    ];

    $form['options'] = [
      '#type'          => 'container',
      '#tree'          => TRUE,
      '#open'          => TRUE,
      '#title'         => $this->t('Options'),
      '#title_display' => 'invisible',
      '#attributes'    => ['class' => ['details--settings', 'has-tooltip']],
      '#access'        => $entity->id() == 'default' ? FALSE : TRUE,
    ];

    // Main JS options.
    $form['options']['settings'] = [
      '#type'       => 'container',
      '#tree'       => TRUE,
      '#open'       => FALSE,
      '#title'      => $this->t('Settings'),
      '#attributes' => ['class' => ['form-wrapper--gridstack-settings']],
    ];

    $form['options']['settings']['isNested'] = [
      '#type'               => 'checkbox',
      '#title'              => $this->t('isNested'),
      '#description'        => $this->t('Check to enable nested grids. Warning! @todo not working yet.'),
      '#wrapper_attributes' => ['class' => ['visually-hidden']],
    ];

    $form['options']['settings']['auto'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Auto'),
      '#description' => $this->t("If unchecked, gridstack will not initialize existing items, means broken."),
    ];

    $form['options']['settings']['cellHeight'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Cell height'),
      '#description' => $this->t("One cell height. <strong>0</strong> means the library will not generate styles for rows. Everything must be defined in CSS files. <strong>auto (-1)</strong> means height will be calculated from cell width. Default 60. Be aware, auto has issues with responsive displays. Put <strong>-1</strong> if you want <strong>auto</strong> as this is an integer type."),
    ];

    $form['options']['settings']['float'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Float'),
      '#description' => $this->t("Enable floating widgets. See http://troolee.github.io/gridstack.js/demo/float.html. Default FALSE."),
    ];

    $form['options']['settings']['minWidth'] = [
      '#type'         => 'textfield',
      '#title'        => $this->t('Min width'),
      '#field_suffix' => 'px',
      '#description'  => $this->t('If window width is less, grid will be shown in one-column mode, with added class: <strong>gridstack--disabled</strong>. Recommended the same as or less than XS below, if provided. Default 768.'),
    ];

    $form['options']['settings']['width'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Amount of columns'),
      '#options'       => $this->getColumnOptions(),
      '#attributes'    => [
        'class'       => ['form-select--column'],
        'data-target' => '.gridstack--main',
      ],
      '#description'  => $this->t('The amount of columns. <strong>Important!</strong> This desktop column is overridden and ignored by LG below if provided.'),
    ];

    $form['options']['settings']['height'] = [
      '#type'         => 'textfield',
      '#title'        => $this->t('Maximum rows'),
      '#field_suffix' => 'px',
      '#description'  => $this->t("Maximum rows amount. Default is <strong>0</strong> which means no maximum rows."),
    ];

    $form['options']['settings']['rtl'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('RTL'),
      '#description' => $this->t("If <strong>true</strong> turns grid to RTL. Possible values are <strong>true</strong>, <strong>false</strong>, <strong>auto</strong> -- default. See http://troolee.github.io/gridstack.js/demo/rtl.html."),
    ];

    $form['options']['settings']['verticalMargin'] = [
      '#type'         => 'textfield',
      '#title'        => $this->t('Vertical margin'),
      '#field_suffix' => 'px',
      '#description'  => $this->t("Vertical gap size. Default 20."),
    ];

    $form['options']['settings']['noMargin'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('No horizontal margin'),
      '#description' => $this->t('If checked, be sure to put 0 for Vertical margin to avoid improper spaces.'),
    ];

    $form['options']['settings']['staticGrid'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Static grid'),
      '#description' => $this->t("Makes grid static. If true widgets are not movable/resizable. You don't even need jQueryUI draggable/resizable. A CSS class <strong>grid-stack-static</strong> is also added to the container. Be sure to CHECK this to have static HTML at front end."),
      '#prefix'      => '<h2 class="form__title">' . t('jQuery UI related options. <small>It does not affect Admin preview. Manage global options <a href=":url" target="_blank">here</a>.</small>', [':url' => Url::fromRoute('gridstack.settings')->toString()]) . '</h2>',
    ];

    // Admin UI related options.
    $form['options']['settings']['draggable'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Draggable'),
      '#description' => $this->t('Allows to override jQuery UI draggable options. Be sure to UNCHECK this to have static HTML at front end.'),
    ];

    $form['options']['settings']['resizable'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Resizable'),
      '#description' => $this->t('Allows to override jQuery UI resizable options. Be sure to UNCHECK this to have static HTML at front end.'),
    ];

    $form['options']['settings']['disableDrag'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Disable drag'),
      '#description' => $this->t('Disallows dragging of widgets. Be sure to CHECK this to have static HTML at front end.'),
    ];

    $form['options']['settings']['disableResize'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Disable resize'),
      '#description' => $this->t('Disallows resizing of widgets. Be sure to CHECK this to have static HTML at front end.'),
    ];

    $form['options']['settings']['alwaysShowResizeHandle'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Show resize handle'),
      '#description' => $this->t('Be sure to UNCHECK this to have static HTML at front end.'),
    ];

    $form['options']['grids'] = [
      '#type'        => 'details',
      '#tree'        => TRUE,
      '#open'        => TRUE,
      '#title'       => $this->t('Grids'),
      '#description' => $this->t('Grids are managed by the draggable boxes.'),
      '#prefix'      => '<div id="edit-grids-wrapper" class="form-wrapper-box">',
      '#suffix'      => '</div>',
      '#attributes'  => ['class' => ['form-wrapper--gridstack-grid', 'form-wrapper--gridstack-grids', 'visually-hidden']],
    ];

    $available_breakpoints = GridStack::getConstantBreakpoints();
    for ($i = 0; $i < $num_grids; $i++) {
      if (!isset($form['options']['grids'][$i])) {
        $form['options']['grids'][$i] = ['#type' => 'container'];
        $form['options']['grids'][$i]['node'] = ['#type' => 'container'];

        $form['options']['grids'][$i]['node']['x'] = [
          '#type'          => 'hidden',
          '#default_value' => $entity->getOptionsGrids($i, 'x'),
        ];
        $form['options']['grids'][$i]['node']['y'] = [
          '#type'          => 'hidden',
          '#default_value' => $entity->getOptionsGrids($i, 'y'),
        ];
        $form['options']['grids'][$i]['node']['width'] = [
          '#type'          => 'hidden',
          '#default_value' => $entity->getOptionsGrids($i, 'width'),
        ];
        $form['options']['grids'][$i]['node']['height'] = [
          '#type'          => 'hidden',
          '#default_value' => $entity->getOptionsGrids($i, 'height'),
        ];
        $form['options']['grids'][$i]['node']['image_style'] = [
          '#type'          => 'hidden',
          '#default_value' => $entity->getOptionsGrids($i, 'image_style'),
          '#attributes'    => ['data-imageid' => ($i + 1), 'class' => ['form-select--target']],
        ];

        // @todo isNested.
        // if ($is_nested) {
        // @todo or @nottodo, or just @toomuch @todo.
        // }
      }
    }

    $definition['settings'] = $options;
    $definition['breakpoints'] = $available_breakpoints;
    $breakpoint_elements = $this->blazyAdmin->breakpointElements($definition);

    $form['options']['breakpoints'] = [
      '#type'       => 'table',
      '#tree'       => TRUE,
      '#header'     => [$this->t('Breakpoint'), $this->t('Max width (not Min width)'), $this->t('Image style'), $this->t('Column'), $this->t('Grids')],
      '#prefix'     => '<h2 class="form__title">' . t('Responsive multi-serving images. <small>The following will extend the above. XS is expected for disabled state defined by <strong>Min width</strong>. LG will use the topmost grid display for the maximum width layout.<br />Image styles will be forced uniformly, if provided. The column will be updated at the given breakpoint.<br />Be sure to follow the natural order keyed by index if trouble with multiple breakpoint image styles.</small>') . '</h2>',
      '#attributes' => ['class' => ['form-wrapper--table', 'form-wrapper--table-gridstack-responsive']],
    ];

    foreach ($breakpoint_elements as $column => $elements) {
      $js_settings['display'] = 'responsive';
      $js_settings['breakpoint'] = $column;

      $storage = 'edit-options-breakpoints-' . $column . '-grids';
      $columns = isset($options['breakpoints']) && isset($options['breakpoints'][$column]) ? $options['breakpoints'][$column] : [];

      // Fallback for totally empty before any string inserted.
      $column_json_grids = empty($columns['grids']) ? Json::encode($grids) : $columns['grids'];
      $column_grids = is_string($column_json_grids) ? Json::decode($column_json_grids) : $column_json_grids;

      // Fallback for not so empty when json grids deleted leaving to a string.
      if (empty($column_grids)) {
        $column_json_grids = Json::encode($grids);
        $column_grids = $grids;
      }

      $current_column = isset($columns['column']) ? $columns['column'] : 12;
      $current_width  = isset($columns['width'])  ? $columns['width']  : '';

      $current_column === -1 ? 'auto' : $current_column;
      if (in_array($column, ['xs', 'lg'])) {
        $lg = $this->t('<small>Grids and image styles are managed at the topmost display.</small>');
        $sm = $this->t('<small>Grids are in one column mode here.</small>');
        $small = $column == 'xs' ? $sm : $lg;
        $form['options']['breakpoints'][$column . '_preview']['preview'] = [
          '#markup' => '<h3 class="form__title">' . $column . $small . '</h3>',
          '#attributes' => ['class' => ['form-item--center']],
          '#wrapper_attributes' => ['colspan' => 4],
        ];
      }
      else {
        $form['options']['breakpoints'][$column . '_preview']['preview'] = $common_preview_elements + [
          '#image_styles'   => FALSE,
          '#storage'        => $storage,
          '#items'          => $this->getDummyItems($column_grids, $js_settings, $image_style_box),
          '#grids'          => $column_grids,
          '#settings'       => $js_settings,
          '#content_attributes' => [
            'class'                 => ['gridstack--responsive', 'gridstack--' . $column],
            'data-config'           => $data_preview_settings,
            'data-preview-grids'    => $column_json_grids,
            'data-storage'          => $storage,
            'data-current-column'   => $current_column,
            'data-responsive-width' => $current_width,
          ],
          '#wrapper_attributes' => ['colspan' => 4],
          '#prefix' => '<h3 class="form__title">' . $column . '</h3>',
        ];
      }

      foreach ($elements as $key => $element) {
        $form['options']['breakpoints'][$column][$key] = $element;
        $form['options']['breakpoints'][$column]['width']['#weight'] = -10;
        $form['options']['breakpoints'][$column]['width']['#attributes']['data-target'] = '.gridstack--' . $column;
        $form['options']['breakpoints'][$column]['width']['#description'] = $this->t('The minimum value must be larger, or similar to the <strong>Min width</strong> defined above.');
        $form['options']['breakpoints'][$column]['image_style']['#weight'] = 10;
        $value = isset($columns[$key]) ? $columns[$key] : '';
        $form['options']['breakpoints'][$column][$key]['#default_value'] = $value;

        $form['options']['breakpoints'][$column]['column'] = [
          '#type'          => 'select',
          '#title'         => $this->t('Column'),
          '#title_display' => 'invisible',
          '#options'       => $this->getColumnOptions(),
          '#empty_option'  => $this->t('- None -'),
          '#default_value' => $current_column,
          '#weight'        => -9,
          '#attributes'    => [
            'class' => ['form-select--column'],
            'data-target' => '.gridstack--' . $column,
          ],
          '#description'   => $this->t('The minimum column for this breakpoint. Try changing this if some grid/box is accidentally hidden to bring them back into the viewport.'),
        ];

        if ($column == 'lg') {
          $form['options']['breakpoints'][$column]['image_style']['#type'] = 'item';
          $form['options']['breakpoints'][$column]['image_style']['#markup'] = $this->t('Defined above');
          $form['options']['breakpoints'][$column]['column']['#attributes']['data-target'] = '.gridstack--main';
          $form['options']['breakpoints'][$column]['width']['#attributes']['data-target'] = '.gridstack--main';
          $form['options']['breakpoints'][$column]['column']['#description'] .= ' ' . $this->t('<strong>Important!</strong> Once provided, this will override the above main <strong>Amount of columns</strong>. Be sure to update the Amount of columns to match this new value to avoid confusion.');
        }
        else {
          $form['options']['breakpoints'][$column]['image_style']['#description'] = $this->t('This will use uniform image style as a fallback if provided.');
        }
        if (!in_array($column, ['xs', 'lg'])) {
          $form['options']['breakpoints'][$column]['grids'] = [
            '#type'          => 'hidden',
            '#title'         => $this->t('Grids'),
            '#title_display' => 'invisible',
            '#default_value' => $column_json_grids,
            '#weight'        => 20,
            '#wrapper_attributes' => ['colspan' => 0, 'class' => ['visually-hidden']],
          ];
        }
      }
    }

    $excludes = ['container', 'details', 'item', 'hidden', 'submit'];
    foreach ($default->getOptions('settings') as $name => $value) {
      if (in_array($form['options']['settings'][$name]['#type'], $excludes) && !isset($form['options']['settings'][$name])) {
        continue;
      }
      if ($admin_css) {
        if ($form['options']['settings'][$name]['#type'] == 'checkbox') {
          $form['options']['settings'][$name]['#field_suffix'] = '&nbsp;';
          $form['options']['settings'][$name]['#title_display'] = 'before';
        }
      }
      if (!isset($form['options']['settings'][$name]['#default_value'])) {
        $form['options']['settings'][$name]['#default_value'] = isset($settings[$name]) ? $settings[$name] : $value;
      }
    }
    return $form;
  }

  /**
   * Returns dummy items.
   */
  public function getDummyItems($grids = [], $js_settings = [], $image_style_box = '') {
    $items  = [];
    $theme  = [
      '#theme' => 'gridstack_dummy',
      '#image_style' => $image_style_box,
      '#settings' => $js_settings,
    ];

    foreach ($grids as $key => $grid) {
      $index = $key + 1;
      $box['box']['#allowed_tags'] = ['button', 'div', 'span', 'select', 'option'];
      $box['attributes']['data-index'] = $index;
      if ($js_settings['display'] == 'responsive') {
        $breakpoint_style = $this->entity->getItemBreakpointGrids($js_settings['breakpoint'], $key, 'image_style');
        $image_style = empty($breakpoint_style) ? '' : $breakpoint_style;
      }
      else {
        $fallback = isset($grid['image_style']) ? $grid['image_style'] : '';
        $image_style = isset($grid['node']['image_style']) ? $grid['node']['image_style'] : $fallback;
      }

      $box['attributes']['data-image-style'] = $image_style;
      $theme['#image_style']['#attributes']['data-image-style'] = $image_style;
      $theme['#image_style']['#default_value'] = $image_style;

      $box['box'] = $theme;

      $items[] = $box;
    }
    return $items;
  }

  /**
   * Returns supported breakpoints.
   */
  public function getColumnOptions() {
    $range = range(1, 12);
    return array_combine($range, $range);
  }

  /**
   * Convert the config into a JSON object to reduce logic at frontend.
   */
  public function jsonify($options, $preview = FALSE) {
    $json    = [];
    $default = GridStack::load('default')->getOptions('settings');

    $cellHeight = $options['cellHeight'];
    if (!empty($options)) {
      foreach ($options as $name => $value) {
        if (isset($options[$name]) && !is_array($options[$name])) {
          if (isset($options['noMargin'])) {
            unset($options['noMargin']);
          }

          if (isset($options['width']) && $options['width'] == 12) {
            unset($options['width']);
          }

          if (!in_array($name, ['cellHeight', 'rtl'])) {
            $cast = gettype($default[$name]);
            settype($options[$name], $cast);
          }

          $json[$name] = $options[$name];

          $json['cellHeight'] = ($cellHeight === -1) ? 'auto' : (int) $cellHeight;

          if (empty($options['rtl'])) {
            unset($json['rtl']);
          }
        }
        // Be sure frontend options do not break admin preview.
        if ($preview && in_array($name, ['disableDrag', 'disableResize', 'draggable', 'resizable', 'staticGrid'])) {
          unset($json[$name]);
        }
      }
    }

    return Json::encode($json);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->hasValue(['json', 'grids', 'node'])) {
      $grids = $form_state->getValue(['options', 'grids']);

      // The stored grids are based on JS input which is to extract and map into
      // its separate form element for a clear structure, less frontend logic.
      $nodes = $form_state->getValue(['json', 'grids', 'node']);

      $json = [];
      if ($nodes) {
        $nodes = Json::decode($nodes);
        $nodes = array_filter($nodes);
        $total = count($nodes);

        $form_state->set('num_grids', $total);

        foreach ($nodes as $i => $node) {
          // Fill in every grid form element by the extracted JSON data.
          foreach (['x', 'y', 'width', 'height', 'image_style'] as $key) {
            // Pass JSON grid nodes to real form element grouped by node.
            if (isset($node[$key])) {
              $form_state->setValue(['options', 'grids', $i, 'node', $key], $node[$key]);
            }

            // JSON to frontend JSON with removed image_style: json.grids.node.
            if ($key != 'image_style' && isset($node[$key])) {
              $json[$i][$key] = $node[$key];
            }
          }
        }

        // Clean out possible surplus due to removal.
        foreach ($grids as $i => $grid) {
          if ($i >= $total) {
            $form_state->unsetValue(['options', 'grids', $i]);
          }
        }
      }

      if ($json) {
        // Final JSON output has no image_style.
        $form_state->setValue(['json', 'grids', 'node'], Json::encode($json));
      }
    }

    // Map settings into JSON.
    $settings = $form_state->getValue(['options', 'settings']);
    $width = $settings['width'];
    $min_width = $settings['minWidth'];
    $form_state->setValue(['json', 'settings'], $this->jsonify($settings));

    // Columns. options[breakpoints][md][grids]
    $base_breakpoints = GridStack::getConstantBreakpoints();
    $options_breakpoints = $form_state->getValue(['options', 'breakpoints']);

    foreach ($options_breakpoints as $key => $breakpoints) {
      foreach ($breakpoints as $k => $value) {
        // Respect 0 value for future mobile first when Blazy supports it.
        if (!empty($breakpoints['column'])) {
          $form_state->setValue(['options', 'breakpoints', $key, $k], $value);
        }
        elseif (empty($breakpoints['column'])) {
          $form_state->unsetValue(['options', 'breakpoints', $key]);
        }

        // Clean out stuffs, either stored somewhere else, or no use.
        $form_state->unsetValue(['options', 'breakpoints', $key, 'breakpoint']);
        $form_state->unsetValue(['options', 'breakpoints', 'lg', 'grids']);
        $form_state->unsetValue(['options', 'breakpoints', 'lg', 'image_style']);

        $form_state->unsetValue(['options', 'breakpoints', 'xs_preview', 'preview']);
        $form_state->unsetValue(['options', 'breakpoints', 'lg_preview', 'preview']);
      }
    }

    // JSON breakpoints to reduce frontend logic for responsive JS interaction.
    $json_breakpoints = [];
    foreach ($options_breakpoints as $key => $breakpoints) {
      foreach ($breakpoints as $k => $value) {
        // Respect 0 value for future mobile first when Blazy supports it.
        if ($k != 'image_style' && !empty($breakpoints['column'])) {
          $json_breakpoints[$breakpoints['width']] = (int) $breakpoints['column'];
        }
      }
    }

    // Append the desktop version as well to reduce JS logic.
    if ($json_breakpoints) {
      $form_state->setValue(['json', 'breakpoints'], Json::encode($json_breakpoints));
    }

    $form_state->unsetValue(['template', 'image_style']);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $entity = $this->entity;

    // Prevent leading and trailing spaces in gridstack names.
    $entity->set('label', trim($entity->label()));
    $entity->set('id', $entity->id());

    $enable = $entity->id() == 'default' ? FALSE : TRUE;
    $entity->setStatus($enable);

    $status        = $entity->save();
    $label         = $entity->label();
    $edit_link     = $entity->link($this->t('Edit'));
    $config_prefix = $entity->getEntityType()->getConfigPrefix();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity.
      // @todo #2278383.
      drupal_set_message($this->t('@config_prefix %label has been updated.', ['@config_prefix' => $config_prefix, '%label' => $label]));
      $this->logger('gridstack')->notice('@config_prefix %label has been updated.', ['@config_prefix' => $config_prefix, '%label' => $label, 'link' => $edit_link]);
    }
    else {
      // If we created a new entity.
      drupal_set_message($this->t('@config_prefix %label has been added.', ['@config_prefix' => $config_prefix, '%label' => $label]));
      $this->logger('gridstack')->notice('@config_prefix %label has been added.', ['@config_prefix' => $config_prefix, '%label' => $label, 'link' => $edit_link]);
    }

    // Invalidate the library discovery cache to update new assets.
    \Drupal::service('library.discovery')->clearCachedDefinitions();

    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

}
