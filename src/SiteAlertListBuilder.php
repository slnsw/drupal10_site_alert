<?php

namespace Drupal\site_alert;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Implements SiteAlertListBuilder class.
 */
class SiteAlertListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'active' => [
        'data' => $this->t('Active'),
        'field' => 'active',
        'specifier' => 'active',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'label' => [
        'data' => $this->t('Label'),
        'field' => 'label',
        'specifier' => 'label',
      ],
      'message' => [
        'data' => $this->t('Message'),
        'field' => 'message',
        'specifier' => 'message',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'scheduling__value' => [
        'data' => $this->t('Start time'),
        'field' => 'scheduling__value',
        'specifier' => 'scheduling__value',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'scheduling__end_value' => [
        'data' => $this->t('End time'),
        'field' => 'scheduling__end_value',
        'specifier' => 'scheduling__end_value',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [
      'active' => ($entity->getActive()) ? $this->t('Active') : $this->t('Not Active'),
      'label' => $entity->label(),
      'message' => check_markup($entity->get('message')->value, $entity->get('message')->format),
      'scheduling__value' => $entity->getStartTime(),
      'scheduling__end_value' => $entity->getEndTime(),
    ];
    return $row + parent::buildRow($entity);
  }

}
