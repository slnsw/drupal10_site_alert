<?php

namespace Drupal\Tests\site_alert\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for kernel tests for the Site Alerts module.
 *
 * @group site_alert
 */
abstract class SiteAlertKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime_range',
    'options',
    'site_alert',
    'text',
  ];

  /**
   * Test site alert entities.
   *
   * @var \Drupal\site_alert\Entity\SiteAlert[]
   */
  protected $alerts;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('site_alert');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Creates site alert entities for the given identifiers.
   *
   * @param array $alerts_to_create
   *   An array of identifiers indicating which site alerts to create. Each
   *   identifier starts with the type of alert to create (one of 'active',
   *   'inactive', 'past', 'current' or 'future'), a hyphen, whether or not the
   *   alert is active, and optionally ending in a number if multiple instances
   *   of the same type should be created.
   */
  protected function createAlerts(array $alerts_to_create) {
    $storage = $this->entityTypeManager->getStorage('site_alert');

    $format_date = function (string $time) {
      return (new DrupalDateTime($time, DateTimeItemInterface::STORAGE_TIMEZONE))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    };

    foreach ($alerts_to_create as $identifier) {
      preg_match('/^([a-z]+)-([a-z]+)\d*/', $identifier, $matches);

      $values = [
        'active' => $matches[2] === 'active',
        'label' => $this->randomString(),
        'severity' => 'medium',
        'message' => $this->randomString(),
      ];

      switch ($matches[1]) {
        case 'past':
          $values['scheduling'] = [
            'value' => $format_date('-3 hours'),
            'end_value' => $format_date('-2 hours'),
          ];
          break;

        case 'present':
          $values['scheduling'] = [
            'value' => $format_date('-1 hours'),
            'end_value' => $format_date('+1 hours'),
          ];
          break;

        case 'future':
          $values['scheduling'] = [
            'value' => $format_date('+2 hours'),
            'end_value' => $format_date('+3 hours'),
          ];
          break;
      }

      $this->alerts[$identifier] = $storage->create($values);
      $this->alerts[$identifier]->save();
    }
  }

}
