<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

/**
 * Provides a 'range' element.
 *
 * @YamlFormElement(
 *   id = "range",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Range.php/class/Range",
 *   label = @Translation("Range"),
 *   category = @Translation("Advanced")
 * )
 */
class Range extends NumericBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'min' => '',
      'max' => '',
      'step' => '',
    ];
  }

}
