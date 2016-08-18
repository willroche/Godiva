<?php

/**
 * @file
 * Contains \Drupal\smart_ip_maxmind_geoip2_web_service\EventSubscriber\SmartIpEventSubscriber.
 */

namespace Drupal\smart_ip_maxmind_geoip2_web_service\EventSubscriber;

use Drupal\smart_ip_maxmind_geoip2_web_service\WebServiceUtility;
use Drupal\smart_ip\GetLocationEvent;
use Drupal\smart_ip\AdminSettingsEvent;
use Drupal\smart_ip\DatabaseFileEvent;
use Drupal\smart_ip\SmartIpEventSubscriberBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Core functionalty of this Smart IP data source module.
 * Listens to Smart IP override events.
 *
 * @package Drupal\smart_ip_maxmind_geoip2_web_service\EventSubscriber
 */
class SmartIpEventSubscriber extends SmartIpEventSubscriberBase {
  /**
   * MaxMind GeoIP2 Precision web service base query URL.
   */
  const BASE_URL = 'geoip.maxmind.com/geoip/v2.1';

  /**
   * MaxMind GeoIP2 Precision web service Country service
   */
  const COUNTRY_SERVICE = 'country';

  /**
   * MaxMind GeoIP2 Precision web service City service
   */
  const CITY_SERVICE = 'city';

  /**
   * MaxMind GeoIP2 Precision web service Insights service
   */
  const INSIGHTS_SERVICE = 'insights';

  /**
   * {@inheritdoc}
   */
  public static function sourceId() {
    return 'maxmind_geoip2_web_service';
  }

  /**
   * {@inheritdoc}
   */
  public static function configName() {
    return 'smart_ip_maxmind_geoip2_web_service.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function processQuery(GetLocationEvent $event) {
    if ($event->getDataSource() == self::sourceId()) {
      $location  = $event->getLocation();
      $ipAddress = $location->get('ipAddress');
      $record = WebServiceUtility::getGeolocation($ipAddress);

      $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
      if (!isset($record['country']['names'][$lang])) {
        // The current language is not yet supported by MaxMind, use English as
        // default language.
        $lang = 'en';
      }
      $location->set('originalData', $record);
      $location->set('country', isset($record['country']['names'][$lang]) ? $record['country']['names'][$lang] : '');
      $location->set('countryCode', isset($record['country']['iso_code']) ? Unicode::strtoupper($record['country']['iso_code']) : '');
      $location->set('region', isset($record['subdivisions'][0]['names'][$lang]) ? $record['subdivisions'][0]['names'][$lang] : '');
      $location->set('regionCode', isset($record['subdivisions'][0]['iso_code']) ? $record['subdivisions'][0]['iso_code'] : '');
      $location->set('city', isset($record['city']['names'][$lang]) ? $record['city']['names'][$lang] : '');
      $location->set('zip', isset($record['postal']['code']) ? $record['postal']['code'] : '');
      $location->set('latitude', isset($record['location']['latitude']) ? $record['location']['latitude'] : '');
      $location->set('longitude', isset($record['location']['longitude']) ? $record['location']['longitude'] : '');
      $location->set('timeZone', isset($record['location']['time_zone']) ? $record['location']['time_zone'] : '');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formSettings(AdminSettingsEvent $event) {
    $config = \Drupal::config($this->configName());
    $form   = $event->getForm();
    $form['smart_ip_data_source_selection']['smart_ip_data_source']['#options'][self::sourceId()] = t(
      "Use @maxmind web service. A user ID and license key is required here. You 
      will need to @buy one of their services and they will provide you the 
      login details. You can view your user ID and license key inside your 
      @account.", [
        '@maxmind' => Link::fromTextAndUrl(t('MaxMind GeoIP2 Precision'), Url::fromUri('http://dev.maxmind.com/geoip/geoip2/web-services'))->toString(),
        '@buy'     => Link::fromTextAndUrl(t('buy'), Url::fromUri('https://www.maxmind.com/en/geoip2-precision-services'))->toString(),
        '@account' => Link::fromTextAndUrl(t('MaxMind account'), Url::fromUri('https://www.maxmind.com/en/my_license_key'))->toString(),
      ]);
    $form['smart_ip_data_source_selection']['maxmind_geoip2_web_service_type'] = [
      '#type'        => 'select',
      '#title'       => t('MaxMind GeoIP2 Precision Web Services'),
      '#description' => t('Choose type of service.'),
      '#options' => [
        self::COUNTRY_SERVICE  => t('Country'),
        self::CITY_SERVICE     => t('City'),
        self::INSIGHTS_SERVICE => t('Insights'),
      ],
      '#default_value' => $config->get('service_type'),
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['maxmind_geoip2_web_service_uid'] = [
      '#type'        => 'textfield',
      '#title'       => t('MaxMind GeoIP2 Precision user ID'),
      '#description' => t(
        "Enter your MaxMind GeoIP2 Precision account's user ID (view your user 
        ID @here).", [
          '@here' => Link::fromTextAndUrl(t('here'), Url::fromUri('https://www.maxmind.com/en/my_license_key'))->toString(),
        ]),
      '#default_value' => $config->get('user_id'),
      '#size' => 30,
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['maxmind_geoip2_web_service_license_key'] = [
      '#type'          => 'textfield',
      '#title'         => t('MaxMind GeoIP2 Precision license key'),
      '#default_value' => $config->get('license_key'),
      '#description'   => t(
        "Enter your MaxMind GeoIP2 Precision account's license key (view your 
        license key @here).", [
          '@here' => Link::fromTextAndUrl(t('here'), Url::fromUri('https://www.maxmind.com/en/my_license_key'))->toString(),
       ]),
      '#size' => 30,
      '#states' => [
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
    if ($formState->isValueEmpty('maxmind_geoip2_web_service_uid')) {
      $formState->setErrorByName('maxmind_geoip2_web_service_uid', t('Please enter your Maxmind GeoIP2 Precision Web Services user ID.'));
    }
    if ($formState->isValueEmpty('maxmind_geoip2_web_service_license_key')) {
      $formState->setErrorByName('maxmind_geoip2_web_service_license_key', t('Please enter your Maxmind GeoIP2 Precision Web Services license key.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormSettings(AdminSettingsEvent $event) {
    $config = \Drupal::configFactory()->getEditable(self::configName());
    /** @var \Drupal\Core\Form\FormStateInterface $formState */
    $formState = $event->getFormState();
    $config->set('service_type', $formState->getValue('maxmind_geoip2_web_service_type'))
      ->set('user_id', $formState->getValue('maxmind_geoip2_web_service_uid'))
      ->set('license_key', $formState->getValue('maxmind_geoip2_web_service_license_key'))
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