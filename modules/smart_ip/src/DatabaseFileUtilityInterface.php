<?php

/**
 * @file
 * Contains \Drupal\smart_ip\DatabaseFileUtilityInterface.
 */

namespace Drupal\smart_ip;

/**
 * Provides an interface for Smart IP's data source modules for its database
 * file.
 *
 * @package Drupal\smart_ip
 */
interface DatabaseFileUtilityInterface {
  /**
   * Get Smart IP's data source module's database filename.
   *
   * @return string
   */
  public static function getFilename();

  /**
   * Get Smart IP's data source module's database file's path.
   *
   * @param bool $autoUpdate
   * @param string $customPath
   * @return string
   */
  public static function getPath($autoUpdate, $customPath);

  /**
   * Checks if Smart IP's data source module's database file needs update.
   *
   * @param bool $autoUpdate
   * @param int $frequency
   * @return bool
   */
  public static function needsUpdate($autoUpdate, $frequency);
}