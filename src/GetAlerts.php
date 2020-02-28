<?php

namespace Drupal\site_alert;

use DateTimeZone;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Service for retrieving site alerts.
 */
class GetAlerts implements GetAlertsInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GetAlerts service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveAlerts() {
    $storage = $this->entityTypeManager->getStorage('site_alert');

    $now = $this->dateNow();

    $query = $storage->getQuery();

    $start_value = $query
      ->orConditionGroup()
      ->condition('scheduling.value', $now, '<=')
      ->notExists('scheduling.value');

    $end_value = $query
      ->orConditionGroup()
      ->condition('scheduling.end_value', $now, '>')
      ->notExists('scheduling.end_value');

    $query
      ->condition('active', 1, '=')
      ->condition($start_value)
      ->condition($end_value);

    $result = $query->execute();

    if (!empty($result)) {
      return $storage->loadMultiple($result);
    }

    return [];
  }

  /**
   * Returns the date in correct timezone and format to compare with database.
   *
   * @return string
   *   The date string representing the current time.
   */
  protected function dateNow() {
    $now = new DrupalDateTime();
    $now->setTimezone(new DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
    return $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
  }

}
