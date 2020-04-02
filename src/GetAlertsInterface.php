<?php

namespace Drupal\site_alert;

/**
 * Interface for services that retrieve site alerts.
 */
interface GetAlertsInterface {

  /**
   * Returns the entity IDs of the currently active site alerts.
   *
   * @return array
   *   Array of currently active site alert entity IDs.
   */
  public function getActiveAlertIds();

  /**
   * Returns the currently active site alerts.
   *
   * @return \Drupal\site_alert\Entity\SiteAlert[]
   *   Array of currently active site alert entities.
   */
  public function getActiveAlerts();

}
