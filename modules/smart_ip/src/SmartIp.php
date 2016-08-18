<?php
/**
 * @file
 * Contains \Drupal\smart_ip\SmartIp.
 */

namespace Drupal\smart_ip;

/**
 * Smart IP static basic methods wrapper.
 *
 * @package Drupal\smart_ip
 */
class SmartIp {
  /**
   * Get the geolocation from the IP address
   *
   * @param string $ip
   *   IP address to query for geolocation. It will use current user's IP
   *   address if empty.
   * @return array
   *   Geolocation details of queried IP address.
   */
  public static function query($ip = NULL) {
    if (empty($ip)) {
      $ip = \Drupal::request()->getClientIp();
    }
    // Use a static cache if this function is called more often
    // for the same ip on the same page.
    $results = &drupal_static(__FUNCTION__);
    if (isset($results[$ip])) {
      return $results[$ip];
    }
    /** @var \Drupal\smart_ip\GetLocationEvent $event */
    $event    = \Drupal::service('smart_ip.get_location_event');
    $location = $event->getLocation();
    $location->set('source', SmartIpLocationInterface::SMART_IP);
    $location->set('ipAddress', $ip);
    $location->set('ipVersion', self::ipAddressVersion($ip));
    $location->set('timestamp', REQUEST_TIME);
    // Allow Smart IP source module populate the variable
    \Drupal::service('event_dispatcher')->dispatch(SmartIpEvents::QUERY_IP, $event);
    $result = $location->getData();
    if (isset($result['latitude']) && isset($result['longitude'])) {
      // If coordinates are (0, 0) there was no match
      if ($result['latitude'] === 0 && $result['longitude'] === 0) {
        $result['latitude']  = NULL;
        $result['longitude'] = NULL;
      }
    }
    // Make sure external data in UTF-8.
    foreach ($result as &$item) {
      if (is_string($item) && !mb_detect_encoding($item, 'UTF-8', TRUE)) {
        $item = mb_convert_encoding($item, 'UTF-8', 'ISO-8859-1');
      }
    }
    $location->setData($result);
    // Allow other modules to modify the result via Symfony Event Dispatcher
    \Drupal::service('event_dispatcher')->dispatch(SmartIpEvents::DATA_ACQUIRED, $event);
    $result = $location->getData();
    $results[$ip] = $result;
    return $result;
  }

  /**
   * Write session variable.
   *
   * @param string $key
   *   The session variable to write. Pass 'smart_ip' to write the smart_ip data.
   * @param mixed $value
   *   The value of session variable.
   */
  public static function setSession($key, $value) {
    if (\Drupal::moduleHandler()->moduleExists('session_cache')) {
      session_cache_set($key, $value);
    }
    else {
      /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
      $session = \Drupal::service('session');
      $session->set($key, $value);
    }
  }

  /**
   * Read session variable.
   *
   * @param string $key
   *   The session variable to read. Pass 'smart_ip' to read the smart_ip data.
   * @return array
   *   Session value.
   */
  public static function getSession($key) {
    if (\Drupal::moduleHandler()->moduleExists('session_cache')) {
      $smartIpSession = session_cache_get($key);
    }
    else {
      /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
      $session = \Drupal::service('session');
      $smartIpSession = $session->get($key);
    }
    return $smartIpSession;
  }

  /**
   * Update user's location only if the IP address stored in session is not the
   * same as the IP address detected by the server.
   */
  public static function updateUserLocation() {
    $debug = \Drupal::config('smart_ip.settings')->get('debug_mode');
    if (!$debug) {
      $ip = \Drupal::request()->getClientIp();
      $smartIpSession = self::getSession('smart_ip');
      if (!isset($smartIpSession['location']['ipAddress']) || $smartIpSession['location']['ipAddress'] != $ip) {
        $result = self::query();
        self::userLocationFallback($result);
        /** @var \Drupal\smart_ip\SmartIpLocation $location */
        $location = \Drupal::service('smart_ip.smart_ip_location');
        $location->setData($result);
        $location->save();
      }
    }
  }

  /**
   * Use server's mod_geoip, X-GeoIP and Cloudflare IP Geolocation as fallback
   * if the user's geolocation is empty
   *
   * @param array $location
   */
  private static function userLocationFallback(array &$location) {
    if (!isset($location['country']) && !isset($location['countryCode']) &&
      !isset($location['region']) && !isset($location['regionCode']) &&
      !isset($location['city']) && !isset($location['zip']) &&
      !isset($location['latitude']) && !isset($location['longitude'])) {
      if (function_exists('apache_note')) {
        if ($country = apache_note('GEOIP_COUNTRY_NAME')) {
          $location['country'] = $country;
        }
        if ($country_code = apache_note('GEOIP_COUNTRY_CODE')) {
          $location['countryCode'] = $country_code;
        }
        if ($region = apache_note('GEOIP_REGION_NAME')) {
          $location['region'] = $region;
        }
        if ($region_code = apache_note('GEOIP_REGION')) {
          $location['regionCode'] = $region_code;
        }
        if ($city = apache_note('GEOIP_CITY')) {
          $location['city'] = $city;
        }
        if ($zip = apache_note('GEOIP_POSTAL_CODE')) {
          $location['zip'] = $zip;
        }
        if ($latitude = apache_note('GEOIP_LATITUDE')) {
          $location['latitude'] = $latitude;
        }
        if ($longitude = apache_note('GEOIP_LONGITUDE')) {
          $location['longitude'] = $longitude;
        }
      }
      else {
        if (isset($_SERVER['GEOIP_COUNTRY_NAME'])) {
          $location['country'] = $_SERVER['GEOIP_COUNTRY_NAME'];
        }
        elseif (isset($_SERVER['HTTP_X_GEOIP_COUNTRY'])) {
          module_load_include('inc', 'smart_ip', 'includes/smart_ip.country_list');
          $countries = country_get_predefined_list();
          $location['country'] = $countries[$_SERVER['HTTP_X_GEOIP_COUNTRY']];
        }
        elseif (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
          module_load_include('inc', 'smart_ip', 'includes/smart_ip.country_list');
          $countries = country_get_predefined_list();
          $location['country'] = $countries[$_SERVER['HTTP_CF_IPCOUNTRY']];
        }
        if (isset($_SERVER['GEOIP_COUNTRY_CODE'])) {
          $location['country'] = $_SERVER['GEOIP_COUNTRY_CODE'];
        }
        elseif (isset($_SERVER['HTTP_X_GEOIP_COUNTRY'])) {
          $location['countryCode'] = $_SERVER['HTTP_X_GEOIP_COUNTRY'];
        }
        elseif (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
          $location['countryCode'] = $_SERVER['HTTP_CF_IPCOUNTRY'];
        }
        if (isset($_SERVER['GEOIP_REGION_NAME'])) {
          $location['region'] = $_SERVER['GEOIP_REGION_NAME'];
        }
        if (isset($_SERVER['GEOIP_REGION'])) {
          $location['regionCode'] = $_SERVER['GEOIP_REGION'];
        }
        if (isset($_SERVER['GEOIP_CITY'])) {
          $location['city'] = $_SERVER['GEOIP_CITY'];
        }
        if (isset($_SERVER['GEOIP_POSTAL_CODE'])) {
          $location['zip'] = $_SERVER['GEOIP_POSTAL_CODE'];
        }
        if (isset($_SERVER['GEOIP_LATITUDE'])) {
          $location['latitude'] = $_SERVER['GEOIP_LATITUDE'];
        }
        if (isset($_SERVER['GEOIP_LONGITUDE'])) {
          $location['longitude'] = $_SERVER['GEOIP_LONGITUDE'];
        }
      }
    }
  }

  /**
   * Check the page URL in allowed geolocate list.
   *
   * @return bool
   */
  public static function checkAllowedPage() {
    $pages = \Drupal::config('smart_ip.settings')->get('allowed_pages');
    if (empty($pages)) {
      // No pages specified then all pages are allowed
      return TRUE;
    }
    else {
      if (isset($_GET['uri'])) {
        // Handle "uri" from ajax request
        if (empty($_GET['uri'])) {
          $url = \Drupal::config('system.site')->get('page.front');
        }
        else {
          $url = $_GET['uri'];
        }
      }
      else {
        $url = \Drupal::service('path.current')->getPath();
      }
      /** @var \Drupal\Core\Path\AliasManagerInterface $aliasManager */
      $aliasManager = \Drupal::service('path.alias_manager');
      $pathAlias = $aliasManager->getAliasByPath($url);
      // Convert the Drupal path to lowercase
      $path = \Drupal\Component\Utility\Unicode::strtolower($pathAlias);
      /** @var \Drupal\Core\Path\PathMatcherInterface $pathMatcher */
      $pathMatcher = \Drupal::service('path.matcher');
      // Compare the lowercase internal and lowercase path alias (if any).
      $pageMatch = $pathMatcher->matchPath($path, $pages);
      if ($path != $url) {
        $pageMatch = $pageMatch || $pathMatcher->matchPath($url, $pages);
      }
      elseif ($path == $url) {
        $url = $aliasManager->getPathByAlias($url);
        $pageMatch = $pageMatch || $pathMatcher->matchPath($url, $pages);
      }
      return $pageMatch;
    }
  }

  /**
   * Determine IP address version.
   *
   * @param string $ipAddress
   *   IP address to check for version.
   */
  public static function ipAddressVersion($ipAddress = NULL) {
    if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      return 4;
    }
    elseif (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      return 6;
    }
  }
}