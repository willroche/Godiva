<?php

/**
 * @file
 * Contains \Drupal\smart_ip\SmartIpLocation.
 */

namespace Drupal\smart_ip;

/**
 * Implements wrapper and utility methods for Smart IP's data location.
 *
 * @package Drupal\smart_ip
 */
class SmartIpLocation implements SmartIpLocationInterface {
  /**
   * All Smart IP location data.
   *
   * @var array
   */
  protected $allData = [];

  /**
   * Original or raw location data from Smart IP data source.
   *
   * @var mixed
   */
  protected $originalData;

  /**
   * The source ID.
   *
   * @var integer
   */
  protected $source;

  /**
   * The IP address.
   *
   * @var string
   */
  protected $ipAddress;

  /**
   * The IP address version.
   *
   * @var string
   */
  protected $ipVersion;

  /**
   * The country.
   *
   * @var string
   */
  protected $country;

  /**
   * The ISO 3166 2-character country code.
   *
   * @var string
   */
  protected $countryCode;

  /**
   * The city.
   *
   * @var string
   */
  protected $city;

  /**
   * The region (FIPS).
   *
   * @var string
   */
  protected $region;

  /**
   * The region code (FIPS).
   *
   * @var string
   */
  protected $regionCode;

  /**
   * The postal / ZIP code.
   *
   * @var string
   */
  protected $zip;

  /**
   * The longitute.
   *
   * @var integer
   */
  protected $longitute;

  /**
   * The latitute.
   *
   * @var integer
   */
  protected $latitute;

  /**
   * The timestamp of the request made.
   *
   * @var integer
   */
  protected $timestamp;

  /**
   * The time zone.
   *
   * @var string
   */
  protected $timeZone;

  /**
   * Constructs Smart IP location.
   *
   * @param array $values
   *   Array of values for the Smart IP location.
   */
  public function __construct(array $values = []) {
    if (!empty($values)) {
      $this->setData($values);
    }
    else {
      // Populate its location variables with stored data
      $this->getData();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($key = NULL) {
    if (!empty($key)) {
      $value = $this->{$key};
      if (!empty($value)) {
        return $value;
      }
      $this->getData();
      if (isset($this->allData[$key])) {
        return $this->allData[$key];
      }
      else {
        return NULL;
      }
    }
    return $this->getData();
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    if (empty($this->allData)) {
      $user = \Drupal::currentUser();
      SmartIp::updateUserLocation();
      // Get current user's stored location from session
      $data = SmartIp::getSession('smart_ip');
      if (empty($data['location']) && $user->id() != 0) {
        /** @var \Drupal\user\UserData $userData */
        $userData = \Drupal::service('user.data');
        // Get current user's stored location from user_data
        $data = $userData->get('smart_ip', $user->id(), 'geoip_location');
      }
      if (!empty($data['location'])) {
        // Populate the Smart IP location from current user's data or session
        $this->setData($data['location']);
      }
    }
    return $this->allData;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->{$key} = $value;
    $this->allData[$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $values = []) {
    foreach ($values as $key => $value) {
      $this->set($key, $value);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $user = \Drupal::currentUser();
    $data['location'] = $this->allData;
    // Allow other modules to modify country list via hook_smart_ip_user_save_alter()
    \Drupal::moduleHandler()->alter('smart_ip_user_save', $user, $data);
    // Save the Smart IP location in current user's session
    SmartIp::setSession('smart_ip', $data);
    if ($user->id() != 0) {
      /** @var \Drupal\user\UserData $userData */
      $userData = \Drupal::service('user.data');
      // Save the Smart IP location to current user's user_data
      $userData->set('smart_ip', $user->id(), 'geoip_location', $data);
    }
    return $this;
  }
}