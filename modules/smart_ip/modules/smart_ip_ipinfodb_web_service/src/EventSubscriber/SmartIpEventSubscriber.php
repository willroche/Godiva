<?php

/**
 * @file
 * Contains \Drupal\smart_ip_ipinfodb_web_service\EventSubscriber\SmartIpEventSubscriber.
 */

namespace Drupal\smart_ip_ipinfodb_web_service\EventSubscriber;

use Drupal\smart_ip_ipinfodb_web_service\WebServiceUtility;
use Drupal\smart_ip\GetLocationEvent;
use Drupal\smart_ip\AdminSettingsEvent;
use Drupal\smart_ip\DatabaseFileEvent;
use Drupal\smart_ip\SmartIpEventSubscriberBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Core functionalty of this Smart IP data source module.
 * Listens to Smart IP override events.
 *
 * @package Drupal\smart_ip_ipinfodb_web_service\EventSubscriber
 */
class SmartIpEventSubscriber extends SmartIpEventSubscriberBase {
  /**
   * IPInfoDB web service version 2 query URL.
   */
  const V2_URL = 'http://api.ipinfodb.com/v2/ip_query.php';

  /**
   * IPInfoDB web service version 3 query URL.
   */
  const V3_URL = 'http://api.ipinfodb.com/v3/ip-city';

  /**
   * {@inheritdoc}
   */
  public static function sourceId() {
    return 'ipinfodb_web_service';
  }

  /**
   * {@inheritdoc}
   */
  public static function configName() {
    return 'smart_ip_ipinfodb_web_service.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function processQuery(GetLocationEvent $event) {
    if ($event->getDataSource() == self::sourceId()) {
      $location = $event->getLocation();
      $ipAddress = $location->get('ipAddress');
      $record = WebServiceUtility::getGeolocation($ipAddress);
      $config = \Drupal::config($this->configName());
      $version = $config->get('version');
      if ($version == 2) {
        $region = '';
        if (isset($record['RegionCode']) && isset($record['CountryCode'])) {
          $regionResult = smart_ip_get_region_static($record['CountryCode'], $record['RegionCode']);
          $region = $regionResult[$record['CountryCode']][$record['RegionCode']];
        }
        elseif (isset($record['RegionName'])) {
          $region = $record['RegionName'];
        }
        $location->set('originalData', $record);
        $location->set('country', isset($record['CountryName']) ? $record['CountryName'] : '');
        $location->set('countryCode', isset($record['CountryCode']) ? $record['CountryCode'] : '');
        $location->set('region', $region);
        $location->set('regionCode', isset($record['RegionCode']) ? $record['RegionCode'] : '');
        $location->set('city', isset($record['City']) ? $record['City'] : '');
        $location->set('zip', isset($record['ZipPostalCode']) ? $record['ZipPostalCode'] : '');
        $location->set('latitude', isset($record['Latitude']) ? $record['Latitude'] : '');
        $location->set('longitude', isset($record['Longitude']) ? $record['Longitude'] : '');
        $location->set('timeZone', '');
      }
      elseif ($version == 3) {
        $location->set('originalData', $record);
        $location->set('country', isset($record['countryName']) ? $record['countryName'] : '');
        $location->set('countryCode', isset($record['countryCode']) ? $record['countryCode'] : '');
        $location->set('region', isset($record['regionName']) ? $record['regionName'] : '');
        $location->set('regionCode', '');
        $location->set('city', isset($record['cityName']) ? $record['cityName'] : '');
        $location->set('zip', isset($record['zipCode']) ? $record['zipCode'] : '');
        $location->set('latitude', isset($record['latitude']) ? $record['latitude'] : '');
        $location->set('longitude', isset($record['longitude']) ? $record['longitude'] : '');
        $location->set('timeZone', isset($record['timeZone']) ? $record['timeZone'] : '');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formSettings(AdminSettingsEvent $event) {
    $config = \Drupal::config($this->configName());
    $form   = $event->getForm();
    $form['smart_ip_data_source_selection']['smart_ip_data_source']['#options'][self::sourceId()] = t(
      "Use @ipinfodb web service. The @ip2location free version database is used 
      by @ipinfodb in their web service. You will need an API key to use this 
      and you must be @login to get it. Note: if @ipinfodb respond too slow to 
      geolocation request, your site's performance will be affected specially if 
      Smart IP is configured to geolocate anonymous users.", [
        '@ipinfodb'    => Link::fromTextAndUrl(t('IPInfoDB.com'), Url::fromUri('http://www.ipinfodb.com'))->toString(),
        '@ip2location' => Link::fromTextAndUrl(t('IP2Location'), Url::fromUri('http://www.ip2location.com'))->toString(),
        '@login'       => Link::fromTextAndUrl(t('logged in'), Url::fromUri('http://ipinfodb.com/login.php'))->toString(),
      ]);
    $form['smart_ip_data_source_selection']['ipinfodb_api_version'] = [
      '#type'          => 'select',
      '#title'         => t('IPInfoDB API version'),
      '#default_value' => $config->get('version'),
      '#options'       => [2 => 2, 3 => 3],
      '#description'   => t('IPInfoDB.com version 2 do have region code, in version 3 it was removed.'),
      '#states'        => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['ipinfodb_api_key'] = [
      '#type'          => 'textfield',
      '#title'         => t('IPInfoDB key'),
      '#description'   => t(
        'The use of IPInfoDB.com service requires API key. Registration for the 
        new API key is free, sign up @here.', [
          '@here' => Link::fromTextAndUrl(t('here'), Url::fromUri('http://www.ipinfodb.com/register.php'))->toString(),
        ]
      ),
      '#default_value' => $config->get('api_key'),
      '#states'        => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $event->setForm($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateFormSettings(AdminSettingsEvent $event) {
    /** @var \Drupal\Core\Form\FormStateInterface $formState */
    $formState  = $event->getFormState();
    if ($formState->isValueEmpty('ipinfodb_api_key')) {
      $formState->setErrorByName('ipinfodb_api_key', t('Please provide IPInfoDB API key.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormSettings(AdminSettingsEvent $event) {
    $config = \Drupal::configFactory()->getEditable(self::configName());
    /** @var \Drupal\Core\Form\FormStateInterface $formState */
    $formState = $event->getFormState();
    $config->set('version', $formState->getValue('ipinfodb_api_version'))
      ->set('api_key', $formState->getValue('ipinfodb_api_key'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function manualUpdate(DatabaseFileEvent $event) {
  }

  /**
   * {@inheritdoc}
   */
  public function cronRun(DatabaseFileEvent $event) {
  }
}