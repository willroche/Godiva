<?php

/**
 * @file
 * Contains \Drupal\smart_ip_ip2location_bin_db\EventSubscriber\SmartIpEventSubscriber.
 */

namespace Drupal\smart_ip_ip2location_bin_db\EventSubscriber;

use Drupal\smart_ip_ip2location_bin_db\DatabaseFileUtility;
use Drupal\smart_ip\GetLocationEvent;
use Drupal\smart_ip\AdminSettingsEvent;
use Drupal\smart_ip\DatabaseFileEvent;
use Drupal\smart_ip\SmartIpEventSubscriberBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Core functionality of this Smart IP data source module.
 * Listens to Smart IP override events.
 *
 * @package Drupal\smart_ip_ip2location_bin_db\EventSubscriber
 */
class SmartIpEventSubscriber extends SmartIpEventSubscriberBase {
  /**
   * IP2Location licensed version.
   */
  const LINCENSED_VERSION = 'licensed';

  /**
   * IP2Location lite or free version.
   */
  const LITE_VERSION = 'lite';

  /**
   * IP2Location "City" edition.
   */
  const CITY_EDITION = 'DB11';

  /**
   * IP2Location "Coutry" edition.
   */
  const COUNTRY_EDITION = 'DB1';

  /**
   * IP2Location licensed version download URL.
   */
  const LINCENSED_DL_URL = 'http://www.ip2location.com/download';

  /**
   * IP2Location lite or free version download URL.
   */
  const LITE_DL_URL = 'http://lite.ip2location.com/download';

  /**
   * Standard lookup with no cache and directly reads from the database file.
   */
  const NO_CACHE = 'no_cache';

  /**
   * Cache the database into memory to accelerate lookup speed.
   */
  const MEMORY_CACHE = 'memory_cache';

  /**
   * Cache whole database into system memory and share among other scripts and
   * websites.
   */
  const SHARED_MEMORY = 'shared_memory';

  /**
   * {@inheritdoc}
   */
  public static function sourceId() {
    return 'ip2location_bin_db';
  }

  /**
   * {@inheritdoc}
   */
  public static function configName() {
    return 'smart_ip_ip2location_bin_db.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function processQuery(GetLocationEvent $event) {
    if ($event->getDataSource() == self::sourceId()) {
      $config    = \Drupal::config(self::configName());
      $location  = $event->getLocation();
      $ipAddress = $location->get('ipAddress');
      $ipVersion = $location->get('ipVersion');
      if ($ipVersion == 4) {
        $customPath = $config->get('ipv4_bin_file_custom_path');
      }
      elseif ($ipVersion == 6) {
        $customPath = $config->get('ipv6_bin_file_custom_path');
      }
      if ($config->get('caching_method') == self::NO_CACHE) {
        $cachingMethod = \IP2Location\Database::FILE_IO;
      }
      elseif ($config->get('caching_method') == self::MEMORY_CACHE) {
        $cachingMethod = \IP2Location\Database::MEMORY_CACHE;
      }
      elseif ($config->get('caching_method') == self::SHARED_MEMORY) {
        $cachingMethod = \IP2Location\Database::SHARED_MEMORY;
      }
      if (!empty($customPath) && !empty($cachingMethod)) {
        $reader = new \IP2Location\Database($customPath, $cachingMethod);
        $record = $reader->lookup($ipAddress, \IP2Location\Database::ALL);
        $location->set('originalData', $record);
        $location->set('country', isset($record['countryName']) ? $record['countryName'] : '');
        $location->set('countryCode', isset($record['countryCode']) ? Unicode::strtoupper($record['countryCode']) : '');
        $location->set('region', isset($record['regionName']) ? $record['regionName'] : '');
        $location->set('regionCode', isset($record['regionCode']) ? $record['regionCode'] : '');
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
      "Use IP2Location binary database. It uses two binary database files; one
      is for IPV4 address support and the other is for IPV6 address support. You
      will manually download these two binary database files and upload them to
      your server. Automatic update is not yet supported here and its your 
      responsibility to update them manually every month. Paid and free versions 
      are available. You need to register first for an account @here for lite 
      version and login @here2 in able to download the two binary database files. 
      For licensed version, you need to buy their product and they will provide 
      you the login details and use it to login @here3. You can download the 
      files @here4 for lite version and @here5 for licensed version. Recommended
      product ID are DB1 (if you need country level only and more faster query) 
      or DB11 (if you need more details but this is less faster than DB1).", [
        '@here'  => Link::fromTextAndUrl(t('here'), Url::fromUri('http://lite.ip2location.com/sign-up'))->toString(),
        '@here2' => Link::fromTextAndUrl(t('here'), Url::fromUri('http://lite.ip2location.com/login'))->toString(),
        '@here3' => Link::fromTextAndUrl(t('here'), Url::fromUri('https://www.ip2location.com/login'))->toString(),
        '@here4' => Link::fromTextAndUrl(t('here'), Url::fromUri('http://lite.ip2location.com/database'))->toString(),
        '@here5' => Link::fromTextAndUrl(t('here'), Url::fromUri('https://www.ip2location.com/download'))->toString(),
    ]);
    $form['smart_ip_data_source_selection']['ip2location_bin_db_caching_method'] = [
      '#type'        => 'select',
      '#title'       => t('IP2Location caching method'),
      '#description' => t(
        '"No cache" - standard lookup with no cache and directly reads from the 
        database file. "Memory cache" - cache the database into memory to 
        accelerate lookup speed and read the whole database into a variable for 
        caching. "Shared memory" - cache whole database into system memory and 
        share among other scripts and websites. Please make sure your system 
        have sufficient RAM if enabling "Memory cache" or "Shared memory".'),
      '#options' => [
        self::NO_CACHE      => t('No cache'),
        self::MEMORY_CACHE  => t('Memory cache'),
        self::SHARED_MEMORY => t('Shared memory'),
      ],
      '#default_value' => $config->get('caching_method'),
      '#states'        => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $secureInfo = Link::fromTextAndUrl(t('more information about securing private files'), Url::fromUri('https://www.drupal.org/documentation/modules/file'))->toString();
    $form['smart_ip_data_source_selection']['ip2location_bin_db_ipv4_custom_path'] = [
      '#type'  => 'textfield',
      '#title' => t('IP2Location binary database IPV4 file full path'),
      '#description' => t(
        'Define the full path where the IP2Location IPV4 binary database file is 
        located in your server (Note: it is your responsibility to add security 
        on this path. See the online handbook for @security). Include the 
        filename. Eg. /var/www/sites/default/private/smart_ip/IP2LOCATION-LITE-DB11.BIN', [
          '@security' => $secureInfo,
        ]
      ),
      '#default_value' => $config->get('ipv4_bin_file_custom_path'),
      '#states' => [
        'visible' => [
          ':input[name="smart_ip_data_source"]' => ['value' => self::sourceId()],
        ],
      ],
    ];
    $form['smart_ip_data_source_selection']['ip2location_bin_db_ipv6_custom_path'] = [
      '#type'  => 'textfield',
      '#title' => t('IP2Location binary database IPV6 file full path'),
      '#description' => t(
        'Define the full path where the IP2Location IPV6 binary database file is 
        located in your server (Note: it is your responsibility to add security 
        on this path. See the online handbook for @security). Include the 
        filename. Eg. /var/www/sites/default/private/smart_ip/IP2LOCATION-LITE-DB11.IPV6.BIN', [
          '@security' => $secureInfo,
        ]
      ),
      '#default_value' => $config->get('ipv6_bin_file_custom_path'),
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
    if ($formState->isValueEmpty('ip2location_bin_db_ipv4_custom_path')) {
      $formState->setErrorByName('ip2location_bin_db_ipv4_custom_path', t('Please provide the IP2Location binary database IPV4 file full path.'));
    }
    if ($formState->isValueEmpty('ip2location_bin_db_ipv6_custom_path')) {
      $formState->setErrorByName('ip2location_bin_db_ipv6_custom_path', t('Please provide the IP2Location binary database IPV6 file full path.'));
    }
    $ipv4Path = $formState->getValue('ip2location_bin_db_ipv4_custom_path');
    if (!file_exists($ipv4Path)) {
      $formState->setErrorByName('ip2location_bin_db_ipv4_custom_path', t('The @file does not exist. Please provide a valid path.', [
        '@file' => $ipv4Path,
      ]));
    }
    $ipv6Path = $formState->getValue('ip2location_bin_db_ipv6_custom_path');
    if (!file_exists($ipv6Path)) {
      $formState->setErrorByName('ip2location_bin_db_ipv6_custom_path', t('The @file does not exist. Please provide a valid path.', [
        '@file' => $ipv6Path,
      ]));
    }
    if (!$formState->isValueEmpty('ip2location_bin_db_ipv4_custom_path')) {
      try {
        // Check IP2Location binary database IPV4 file if valid
        $reader = new \IP2Location\Database($ipv4Path, \IP2Location\Database::FILE_IO);
        $record = $reader->lookup('8.8.8.8', \IP2Location\Database::COUNTRY);
        if (strtotime($reader->getDate()) <= 0 || empty($record['countryCode'])) {
          $formState->setErrorByName('ip2location_bin_db_ipv4_custom_path', t('The IP2Location binary database IPV4 file is not valid or corrupted.'));
        }
      }
      catch(\Exception $e) {
        $formState->setErrorByName('ip2location_bin_db_ipv4_custom_path', t('Loading IP2Location binary database IPV4 file failed: @error', [
          '@error' => $e->getMessage(),
        ]));
      }
    }
    if (!$formState->isValueEmpty('ip2location_bin_db_ipv6_custom_path')) {
      try {
        // Check IP2Location binary database IPV6 file if valid
        $reader = new \IP2Location\Database($ipv6Path, \IP2Location\Database::FILE_IO);
        $record = $reader->lookup('2001:4860:4860::8888', \IP2Location\Database::COUNTRY);
        if (strtotime($reader->getDate()) <= 0 || empty($record['countryCode'])) {
          $formState->setErrorByName('ip2location_bin_db_ipv6_custom_path', t('The IP2Location binary database IPV6 file is not valid or corrupted.'));
        }
      }
      catch(\Exception $e) {
        $formState->setErrorByName('ip2location_bin_db_ipv6_custom_path', t('Loading IP2Location binary database IPV6 file failed: @error', [
          '@error' => $e->getMessage(),
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormSettings(AdminSettingsEvent $event) {
    $config = \Drupal::configFactory()->getEditable(self::configName());
    /** @var \Drupal\Core\Form\FormStateInterface $formState */
    $formState = $event->getFormState();
    $config->set('ipv4_bin_file_custom_path', $formState->getValue('ip2location_bin_db_ipv4_custom_path'))
      ->set('ipv6_bin_file_custom_path', $formState->getValue('ip2location_bin_db_ipv6_custom_path'))
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