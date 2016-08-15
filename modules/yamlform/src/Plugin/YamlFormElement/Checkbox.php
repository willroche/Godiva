<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

use Drupal\yamlform\YamlFormElementBase;

/**
 * Provides a 'checkbox' element.
 *
 * @YamlFormElement(
 *   id = "checkbox",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkbox.php/class/Checkbox",
 *   label = @Translation("Checkbox"),
 *   category = @Translation("Basic"),
 * )
 */
class Checkbox extends YamlFormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties();
    $properties['title_display'] = 'after';
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function formatText(array &$element, $value, array $options = []) {
    $format = $this->getFormat($element);

    switch ($format) {
      case 'value';
        return ($value) ? $this->t('Yes') : $this->t('No');

      default:
        return ($value) ? 1 : 0;
    }
  }

}
