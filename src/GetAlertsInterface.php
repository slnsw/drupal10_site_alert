<?php

namespace Drupal\site_alert;

/**
 * Interface for services that retrieve site alerts.
 */
interface GetAlertsInterface {

  /**
   * Returns the currently active site alerts.
   *
   * @return \Drupal\site_alert\Entity\SiteAlert[]
   *   Array of currently active site alert entities.
   */
  public function getActiveAlerts();

}
