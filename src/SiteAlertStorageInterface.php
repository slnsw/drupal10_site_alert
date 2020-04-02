<?php

namespace Drupal\site_alert;

/**
 * Interface for SiteAlert entity storage handlers.
 */
interface SiteAlertStorageInterface {

  /**
   * Defines the value of the 'active' column for active alerts.
   */
  public const ACTIVE = 1;

  /**
   * Defines the value of the 'active' column for inactive alerts.
   */
  public const INACTIVE = 0;

  /**
   * Returns the max age for elements that display site alerts.
   *
   * @return int
   *   The time in seconds until the next alert is scheduled to appear or
   *   disappear, or -1 if there are no scheduled alerts.
   */
  public function getCacheMaxAge();

}
