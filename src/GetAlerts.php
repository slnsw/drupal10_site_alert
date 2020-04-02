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
  public function getActiveAlertIds() {
    $now = $this->dateNow();

    $query = $this->getStorage()->getQuery();

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

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveAlerts() {
    $ids = $this->getActiveAlertIds();

    if (!empty($ids)) {
      return $this->getStorage()->loadMultiple($ids);
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

  /**
   * Returns the entity storage for site alert entities.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage.
   */
  protected function getStorage() {
    return $this->entityTypeManager->getStorage('site_alert');
  }

}
