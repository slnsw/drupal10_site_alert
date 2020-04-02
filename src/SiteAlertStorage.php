<?php

namespace Drupal\site_alert;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Entity storage handler for SiteAlert entities.
 */
class SiteAlertStorage extends SqlContentEntityStorage implements SiteAlertStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $current_datetime = new DrupalDateTime('now', DateTimeItemInterface::STORAGE_TIMEZONE);
    $query = 'SELECT MIN(t) AS t FROM (SELECT scheduling__value t FROM {' . $this->getBaseTable() . '} WHERE scheduling__value > :current_time AND active = :active UNION SELECT scheduling__end_value FROM {' . $this->getBaseTable() . '} WHERE scheduling__end_value > :current_time AND active = :active) AS u;';
    $next_scheduled_datetime = $this->database->query($query, [
      ':current_time' => $current_datetime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      ':active' => SiteAlertStorageInterface::ACTIVE,
    ])->fetchField();

    // If there are no scheduled alerts, there is no need to expire the cache.
    if (empty($next_scheduled_datetime)) {
      return CacheBackendInterface::CACHE_PERMANENT;
    }

    // Return the remaining time in seconds, making sure that we return minimum
    // 1 second so we will not accidentally make the element uncacheable.
    $current_unix = (int) $current_datetime->format('U');
    $next_scheduled_unix = (int) (new DrupalDateTime($next_scheduled_datetime, DateTimeItemInterface::STORAGE_TIMEZONE))->format('U');
    return max(1, $next_scheduled_unix - $current_unix);
  }

}
