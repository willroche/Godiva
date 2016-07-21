<?php

/**
 * @file
 * Contains \Drupal\gridstack_ui\Form\GridStackSettingsForm.
 */

namespace Drupal\gridstack_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the GridStack admin settings form.
 */
class GridStackSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'gridstack_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gridstack.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gridstack.settings');

    $form['customized'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Use customized GridStack library'),
      '#description'   => $this->t('<strong>Warning!</strong> This is a proof of concept that GrisStack can work without jQuery UI for the static grid at frontend. Be sure to disable this when jQuery UI related issues are resolved. This customized library is meant temporary, and may not always stay updated! <br /><strong>Until then, use at your own risk.</strong>'),
      '#default_value' => $config->get('customized'),
    ];

    $form['jquery_ui'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Load jQuery UI library'),
      '#description'   => $this->t('Check if trouble at frontend till GridStack library is decoupled from jQuery UI, or at least till jQuery UI related issues are resolved, for its static grid. Uncheck if decoupled/resolved. Ignored if the above is checked.'),
      '#default_value' => $config->get('jquery_ui'),
      '#states'        => [
        'visible' => [':input[name="customized"]' => ['checked' => FALSE]],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('gridstack.settings')
      ->set('customized', $form_state->getValue('customized'))
      ->set('jquery_ui', $form_state->getValue('jquery_ui'))
      ->save();

    // Invalidate the library discovery cache to update new assets.
    \Drupal::service('library.discovery')->clearCachedDefinitions();

    parent::submitForm($form, $form_state);
  }

}
