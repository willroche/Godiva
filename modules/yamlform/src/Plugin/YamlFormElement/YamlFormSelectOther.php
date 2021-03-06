<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

/**
 * Provides a 'select_other' element.
 *
 * @YamlFormElement(
 *   id = "yamlform_select_other",
 *   label = @Translation("Select other"),
 *   category = @Translation("Options")
 * )
 */
class YamlFormSelectOther extends Select {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'other__title' => '',
      'other__placeholder' => '',
      'other__description' => '',
      'other__size' => '',
      'other__maxlength' => '',
    ];
  }

}
