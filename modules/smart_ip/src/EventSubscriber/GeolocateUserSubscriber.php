<?php

/**
 * @file
 * Contains \Drupal\smart_ip\EventSubscriber\GeolocateUserSubscriber.
 */

namespace Drupal\smart_ip\EventSubscriber;

use Drupal\smart_ip\SmartIp;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Allows Smart IP to act on HTTP request event.
 *
 * @package Drupal\smart_ip\EventSubscriber
 */
class GeolocateUserSubscriber implements EventSubscriberInterface {
  /**
   * Initiate user geolocation.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function GeolocateUser(GetResponseEvent $event) {
    // Check to see if the page is one of those allowed for geolocation
    if (!SmartIp::checkAllowedPage()) {
      // This page is not on the list to acquire/update user's geolocation
      return;
    }
    /** @var \Drupal\smart_ip\SmartIpLocation $location */
    $location = \Drupal::service('smart_ip.smart_ip_location');
    // Save a database hit. If there's already session data, don't check if we
    // need to geolocate the anonymous role.
    if (empty($location->getData())) {
      $roles = \Drupal::config('smart_ip.settings')->get('roles_to_geolocate');
      if (in_array(AccountInterface::ANONYMOUS_ROLE, $roles)) {
        SmartIp::updateUserLocation();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents(){
    $events[KernelEvents::REQUEST][] = ['GeolocateUser'];
    return $events;
  }
}