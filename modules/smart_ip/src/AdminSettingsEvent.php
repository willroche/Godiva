<?php

/**
 * @file
 * Contains \Drupal\smart_ip\AdminSettingsEvent.
 */

namespace Drupal\smart_ip;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides Smart IP admin settings override event for event listeners.
 *
 * @package Drupal\smart_ip
 */
class AdminSettingsEvent extends Event {
  /**
   * Contains array of configuration names that will be editable.
   *
   * @var array
   */
  protected $editableConfigNames;

  /**
   * Contains Smart IP admin settings $form
   *
   * @var array
   */
  protected $form;

  /**
   * Contains Smart IP admin settings $form
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * @return array
   */
  public function getForm() {
    return $this->form;
  }

  /**
   * @param array $form
   */
  public function setForm(array $form) {
    $this->form = $form;
  }

  /**
   * @return \Drupal\Core\Form\FormStateInterface
   */
  public function getFormState() {
    return $this->formState;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $formState
   */
  public function setFormState(FormStateInterface $formState) {
    $this->formState = $formState;
  }

  /**
   * @return array
   */
  public function getEditableConfigNames() {
    return $this->editableConfigNames;
  }

  /**
   * @param array $editableConfigNames
   */
  public function setEditableConfigNames(array $editableConfigNames) {
    $this->editableConfigNames = $editableConfigNames;
  }
}