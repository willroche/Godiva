<?php

/**
 * @file
 * Contains \Drupal\smart_ip\DatabaseFileUtilityBase.
 */

namespace Drupal\smart_ip;

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Database file utility methods class wrapper.
 *
 * @package Drupal\smart_ip
 */
abstract class DatabaseFileUtilityBase implements DatabaseFileUtilityInterface {

  /**
   * Download Smart IP's data source module's database timout.
   */
  const DOWNLOAD_TIMEOUT = 600;

  /**
   * Fixed Drupal folder path of Smart IP data source module's database file is
   * stored.
   */
  const DRUPAL_FOLDER = 'private://smart_ip';

  /**
   * Download Smart IP's data source module's database file weekly.
   */
  const DOWNLOAD_WEEKLY = 0;

  /**
   * Download Smart IP's data source module's database file monthly.
   */
  const DOWNLOAD_MONTHLY = 1;

  /**
   * Get Smart IP's data source module's database file's path. This should
   * return the fixed Drupal folder if auto update is on or if custom path is
   * empty with auto update off.
   *
   * @param bool $autoUpdate
   * @param string $customPath
   * @return string
   */
  public static function getPath($autoUpdate, $customPath) {
    if ($autoUpdate == TRUE || ($autoUpdate == FALSE && empty($customPath))) {
      /** @var \Drupal\Core\File\FileSystem $filesystem */
      $filesystem = \Drupal::service('file_system');
      return $filesystem->realpath(self::DRUPAL_FOLDER);
    }
    return $customPath;
  }

  /**
   * {@inheritdoc}
   */
  public static function needsUpdate($autoUpdate = TRUE, $frequency = self::DOWNLOAD_MONTHLY) {
    if ($autoUpdate) {
      $timeNow = strtotime('midnight', REQUEST_TIME);
      $lastUpdateTime = \Drupal::state()->get('smart_ip_maxmind_geoip2_bin_db.last_update_time') ?: 0;
      $lastUpdateTime = strtotime('midnight', $lastUpdateTime);
      if ($frequency == self::DOWNLOAD_WEEKLY) {
        $wednesday = strtotime('first Wednesday', $timeNow);
        if ($wednesday <= $timeNow && $wednesday > $lastUpdateTime) {
          return TRUE;
        }
      }
      elseif ($frequency == self::DOWNLOAD_MONTHLY) {
        $firstWed = strtotime('first Wednesday of this month', $timeNow);
        if ($firstWed <= $timeNow && $firstWed > $lastUpdateTime) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Download Smart IP's data source module's database file and extract it.
   *
   * @param string $url
   *   URL of the Smart IP's data source module's service provider.
   * @param string $file
   *   File name of the Smart IP's data source module's database including its
   *   extension name.
   * @param string $sourceId
   *   Smart IP data source module's source ID.
   * @return bool
   *   Returns FALSE if process failed.
   */
  protected static function requestDatabaseFile($url, $file, $sourceId) {
    /** @var \Drupal\Core\File\FileSystem $filesystem */
    $filesystem = \Drupal::service('file_system');
    $realDestination = $filesystem->realpath(self::DRUPAL_FOLDER);
    $success = file_prepare_directory($realDestination, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    if ($success) {
      $downloadCacheDirectory = _update_manager_cache_directory() . '/';
      $realDownloadCache = $filesystem->realpath("$downloadCacheDirectory$file");
      if (file_exists($realDownloadCache)) {
        // Remove old database file from temp download directory if still exist.
        $filesystem->unlink($realDownloadCache);
      }
      // Download the Smart IP's data source module's database file.
      /** @var \Drupal\Core\Http\ClientFactory $client */
      $client = \Drupal::service('http_client_factory');
      $data = $client->fromOptions(['timeout' => self::DOWNLOAD_TIMEOUT])
        ->get($url)
        ->getBody();
      $parsedUrl  = parse_url($url);
      $localPath  = $downloadCacheDirectory . $filesystem->basename($parsedUrl['path']);
      $localCache = file_unmanaged_save_data($data, $localPath, FILE_EXISTS_REPLACE);
      if (!$localCache || $downloadCacheDirectory == $localCache) {
        $message = t('Failed to download %source.', ['%source' => $url]);
        \Drupal::state()->set('smart_ip.request_db_error_source_id', $sourceId);
        \Drupal::state()->set('smart_ip.request_db_error_message', $message);
        \Drupal::logger('smart_ip')->error($message);
        return FALSE;
      }
      $extractDirectory = _update_manager_extract_directory();
      $realExtractDirectory = $filesystem->realpath($extractDirectory);
      $realLocalCache = $filesystem->realpath($localCache);
      $targetFile = "$realExtractDirectory/$file";
      if (file_exists($targetFile)) {
        // Remove old database file from temp extract directory if still exist.
        $filesystem->unlink($targetFile);
      }
      // Extract it.
      try {
        module_load_include('inc', 'update', 'update.manager');
        update_manager_archive_extract($localCache, $extractDirectory);
      }
      catch (\Exception $e) {
        $extractError = TRUE;
        \Drupal::logger('smart_ip')->debug($e->getMessage());
        if (class_exists('PharData')) {
          try {
            $extractError = FALSE;
            $archive = new \PharData($realLocalCache);
            $archive->extractTo($realExtractDirectory);
          }
          catch (\Exception $e) {
            \Drupal::logger('smart_ip')->debug($e->getMessage());
            if (!file_exists($targetFile)) {
              $extractError = TRUE;
            }
          }
        }
        if ($extractError) {
          $sourceFp = gzopen($realLocalCache, 'rb');
          $targetFp = fopen($targetFile, 'w');
          while (!gzeof($sourceFp)) {
            $data = gzread($sourceFp, 4096);
            fwrite($targetFp, $data, strlen($data));
          }
          gzclose($sourceFp);
          fclose($targetFp);
        }
      }
      // Verify it.
      if (!file_exists($targetFile)) {
        $message = t('Failed extracting %file.', ['%file' => $realLocalCache]);
        \Drupal::state()->set('smart_ip.request_db_error_source_id', $sourceId);
        \Drupal::state()->set('smart_ip.request_db_error_message', $message);
        \Drupal::logger('smart_ip')->error($message);
        return FALSE;
      }
      // Delete the old Smart IP data source module's database file.
      $filesystem->unlink("$realDestination/$file");
      if (file_unmanaged_move($targetFile, $realDestination) === FALSE) {
        $message = t('The file %file could not be moved to %destination.', [
          '%file' => $targetFile,
          '%destination' => $realDestination,
        ]);
        \Drupal::state()->set('smart_ip.request_db_error_source_id', $sourceId);
        \Drupal::state()->set('smart_ip.request_db_error_message', $message);
        \Drupal::logger('smart_ip')->error($message);
        return FALSE;
      }
      else {
        // Success! Clear error flag and message.
        \Drupal::state()->set('smart_ip.request_db_error_source_id', '');
        \Drupal::state()->set('smart_ip.request_db_error_message', '');
        \Drupal::logger('smart_ip')->info(t('The database file %file successfully downloaded to %destination', [
            '%file' => $file,
            '%destination' => $realDestination,
          ])
        );
      }
    }
    else {
      $message = t('Your private file system path is not yet configured. Please check your @filesystem.', [
        '@filesystem' => Link::fromTextAndUrl(t('File system'), Url::fromRoute('system.file_system_settings'))->toString(),
      ]);
      \Drupal::state()->set('smart_ip.request_db_error_source_id', $sourceId);
      \Drupal::state()->set('smart_ip.request_db_error_message', $message);
      return FALSE;
    }
    return TRUE;
  }
}