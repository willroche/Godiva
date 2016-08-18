<?php
/**
 * @file
 * Contains \Drupal\smart_ip\GetLocationEvent.
 */

namespace Drupal\smart_ip;

use Symfony\Component\EventDispatcher\Event;

/**
 * Provides Smart IP query location override event for event listeners.
 *
 * @package Drupal\smart_ip
 */
class GetLocationEvent extends Event {
  /**
   * Contains user's location
   *
   * @var \Drupal\smart_ip\SmartIpLocationInterface
   */
  protected $location;

  /**
   * Contains Smart IP data source info
   *
   * @var string
   */
  protected $dataSource;

  /**
   * Constructs a Smart IP event.
   *
   * @param \Drupal\smart_ip\SmartIpLocationInterface $location
   */
  public function __construct(SmartIpLocationInterface $location) {
    $this->setLocation($location);
    $this->dataSource = \Drupal::config('smart_ip.settings')->get('data_source');
  }

  /**
   * @return \Drupal\smart_ip\SmartIpLocationInterface
   */
  public function getLocation() {
    return $this->location;
  }

  /**
   * @param \Drupal\smart_ip\SmartIpLocationInterface $location
   */
  public function setLocation(SmartIpLocationInterface $location) {
    $this->location = $location;
  }

  /**
   * @return string
   */
  public function getDataSource() {
    return $this->dataSource;
  }
}