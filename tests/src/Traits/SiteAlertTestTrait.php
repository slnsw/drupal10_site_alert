<?php

declare(strict_types = 1);

namespace Drupal\Tests\site_alert\Traits;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\site_alert\Entity\SiteAlert;

/**
 * Helper methods for testing the Site Alert module.
 */
trait SiteAlertTestTrait {

  /**
   * Checks that the given site alert entity contains the given data.
   *
   * @param \Drupal\site_alert\Entity\SiteAlert $alert
   *   The site alert entity to check.
   * @param string $label
   *   The expected label.
   * @param string $message
   *   The expected message.
   * @param string $severity
   *   The expected severity.
   * @param bool $active
   *   The expected status.
   * @param array $scheduling
   *   The expected start and end time, or an empty array if the site alert is
   *   not scheduled.
   */
  protected function assertAlert(SiteAlert $alert, string $label, string $message, string $severity = 'medium', bool $active = TRUE, array $scheduling = ['start' => '', 'end' => '']): void {
    $this->assertEquals($label, $alert->label());
    $this->assertEquals($message, $alert->getMessage());
    $this->assertEquals($severity, $alert->getSeverity());
    $this->assertEquals($active, (bool) $alert->getActive());

    // Convert start and end times to the default time zone.
    foreach (['start', 'end'] as $source) {
      $date = &$scheduling[$source];
      if (!empty($date)) {
        $date = (new DrupalDateTime($date, 'UTC'))->format('Y-m-d H:i:s', ['timezone' => date_default_timezone_get()]);
      }
    }
    $this->assertEquals($scheduling['start'], $alert->getStartTime(), 'Start date is not as expected.');
    $this->assertEquals($scheduling['end'], $alert->getEndTime(), 'End date is not as expected.');
  }

  /**
   * Checks that the site alert with the given label contains the given data.
   *
   * This assumes that the database only contains a single alert with the given
   * label.
   *
   * @param string $label
   *   The label of the site alert.
   * @param string $message
   *   The expected message.
   * @param string $severity
   *   The expected severity.
   * @param bool $active
   *   The expected status.
   * @param array $scheduling
   *   The expected start and end time, or an empty array if the site alert is
   *   not scheduled.
   */
  protected function assertAlertByLabel(string $label, string $message, string $severity = 'medium', bool $active = TRUE, array $scheduling = ['start' => '', 'end' => '']): void {
    $alert = $this->loadAlertByLabel($label);
    $this->assertAlert($alert, $label, $message, $severity, $active, $scheduling);
  }

  /**
   * Checks that the database contains the expected number of alerts.
   *
   * @param int $expected_count
   *   The expected number of alerts.
   */
  protected function assertAlertCount(int $expected_count): void {
    $query = $this->siteAlertStorage->getQuery();
    $query->count();
    $actual_count = (int) $query->execute();
    $this->assertEquals($expected_count, $actual_count);
  }

  /**
   * Checks that the database contains the expected number of active alerts.
   *
   * @param int $expected_count
   *   The expected number of active alerts.
   */
  protected function assertActiveAlertCount(int $expected_count): void {
    $this->assertEquals($expected_count, $this->getAlertCount(TRUE));
  }

  /**
   * Checks that the database contains the expected number of inactive alerts.
   *
   * @param int $expected_count
   *   The expected number of inactive alerts.
   */
  protected function assertInactiveAlertCount(int $expected_count): void {
    $this->assertEquals($expected_count, $this->getAlertCount(FALSE));
  }

  /**
   * Returns the number of alerts with the given active status.
   *
   * @param bool $active
   *   Whether to count active or inactive alerts.
   *
   * @return int
   *   The number of alerts.
   */
  protected function getAlertCount(bool $active): int {
    $query = $this->siteAlertStorage->getQuery();
    $query
      ->count()
      ->condition('active', $active);
    return (int) $query->execute();
  }

  /**
   * Returns the site alert with the given label.
   *
   * This assumes that there is exactly 1 site alert with the given label.
   *
   * @param string $label
   *   The label to search for.
   *
   * @return \Drupal\site_alert\Entity\SiteAlert
   *   The site alert entity.
   */
  protected function loadAlertByLabel(string $label): SiteAlert {
    $alerts = $this->loadAlertsByLabel($label);
    $this->assertCount(1, $alerts);
    return reset($alerts);
  }

  /**
   * Returns all site alerts with a matching label.
   *
   * @param string $label
   *   The label to search for.
   *
   * @return array
   *   Array of site alerts found.
   */
  protected function loadAlertsByLabel($label) {
    $query = $this->siteAlertStorage->getQuery();
    $query->condition('label', $label, '=');
    $result = $query->execute();
    return $this->siteAlertStorage->loadMultiple($result);
  }

}
