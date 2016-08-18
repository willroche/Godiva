<?php

/**
 * @file
 * Contains \Drupal\smart_ip\SmartIpDataSourceInterface.
 */

namespace Drupal\smart_ip;

/**
 * Provides an interface for Smart IP's data source modules.
 *
 * @package Drupal\smart_ip
 */
interface SmartIpDataSourceInterface {
  /**
   * Smart IP data source module's source ID.
   *
   * @return string
   */
  public static function sourceId();

  /**
   * Get the config name of this Smart IP data source module.
   *
   * @return string
   */
  public static function configName();

  /**
   * Act on \Drupal\smart_ip\SmartIp::query() when executed and if selected as
   * Smart IP data source, query the IP address against its database.
   *
   * @param \Drupal\smart_ip\GetLocationEvent $event
   */
  public function processQuery(GetLocationEvent $event);

  /**
   * Add Smart IP source module's config name.
   *
   * @param \Drupal\smart_ip\AdminSettingsEvent $event
   */
  public function includeEditableConfigNames(AdminSettingsEvent $event);

  /**
   * Add the form elements of this Smart IP data source to main admin settings
   * page of Smart IP.
   *
   * @param \Drupal\smart_ip\AdminSettingsEvent $event
   */
  public function formSettings(AdminSettingsEvent $event);

  /**
   * Act on validation of main Smart IP admin settings form.
   *
   * @param \Drupal\smart_ip\AdminSettingsEvent $event
   */
  public function validateFormSettings(AdminSettingsEvent $event);

  /**
   * Act on submission of main Smart IP admin settings form.
   *
   * @param \Drupal\smart_ip\AdminSettingsEvent $event
   */
  public function submitFormSettings(AdminSettingsEvent $event);

  /**
   * Act on manual database update.
   *
   * @param \Drupal\smart_ip\DatabaseFileEvent $event
   */
  public function manualUpdate(DatabaseFileEvent $event);

  /**
   * Act on Drupal cron run.
   *
   * @param \Drupal\smart_ip\DatabaseFileEvent $event
   */
  public function cronRun(DatabaseFileEvent $event);
}