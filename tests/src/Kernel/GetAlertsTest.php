<?php

namespace Drupal\Tests\site_alert\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\site_alert\Entity\SiteAlert;

/**
 * Tests retrieving of currently active alerts.
 *
 * @group site_alert
 * @coversDefaultClass \Drupal\site_alert\GetAlerts
 */
class GetAlertsTest extends KernelTestBase {

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
   * The service to retrieve site alerts. This is the system under test.
   *
   * @var \Drupal\site_alert\GetAlertsInterface
   */
  protected $getAlerts;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('site_alert');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->getAlerts = $this->container->get('site_alert.get_alerts');
  }

  /**
   * Tests the retrieval of active alerts.
   *
   * @param array $alerts_to_create
   *   An array of identifiers for the site alerts that should be created at the
   *   start of the test.
   * @param array $expected_alerts
   *   An array of identifiers for the site alerts that are expected to be
   *   returned by the method under test.
   *
   * @covers ::getActiveAlerts
   * @dataProvider getActiveAlertsProvider
   */
  public function testGetActiveAlerts(array $alerts_to_create, array $expected_alerts) {
    // Populate the database with the alerts as specified in the test case.
    $this->createAlerts($alerts_to_create);

    // Retrieve the active alerts.
    $actual_alerts = $this->getAlerts->getActiveAlerts();

    // Get the entity IDs of the actual and expected alerts so we can compare.
    $actual_alert_ids = array_keys($actual_alerts);

    $expected_alert_ids = array_map(function ($alert_id) {
      return $this->alerts[$alert_id]->id();
    }, $expected_alerts);

    sort($actual_alert_ids);
    sort($expected_alert_ids);

    $this->assertEquals($actual_alert_ids, $expected_alert_ids);
  }

  /**
   * Data provider for ::testGetActiveAlerts().
   *
   * @return array
   *   An array of test cases, each test case an array consisting of:
   *   - An array of identifiers for the site alerts that should be created at
   *     the start of the test.
   *   - An array of identifiers for the site alerts that are expected to be
   *     returned by the method under test.
   *
   * @see testGetActiveAlerts()
   */
  public function getActiveAlertsProvider() {
    return [
      [
        [],
        [],
      ],

      [
        ['unscheduled-active'],
        ['unscheduled-active'],
      ],

      [
        ['unscheduled-inactive'],
        [],
      ],

      [
        ['past-active'],
        [],
      ],

      [
        ['past-inactive'],
        [],
      ],

      [
        ['current-active'],
        ['current-active'],
      ],

      [
        ['current-inactive'],
        [],
      ],

      [
        ['future-active'],
        [],
      ],

      [
        ['future-inactive'],
        [],
      ],

      [
        ['unscheduled-active', 'present-active'],
        ['unscheduled-active', 'present-active'],
      ],

      [
        [
          'unscheduled-inactive',
          'past-active',
          'past-inactive',
          'future-active',
          'future-inactive',
        ],
        [],
      ],

      [
        [
          'unscheduled-active',
          'unscheduled-inactive',
          'past-active',
          'past-inactive',
          'present-active',
          'present-inactive',
          'future-active',
          'future-inactive',
        ],
        ['unscheduled-active', 'present-active'],
      ],

      [
        [
          'unscheduled-active',
          'unscheduled-active2',
          'present-active',
          'present-active2',
        ],
        [
          'unscheduled-active',
          'unscheduled-active2',
          'present-active',
          'present-active2',
        ],
      ],

      [
        [
          'unscheduled-inactive',
          'unscheduled-inactive2',
          'past-active',
          'past-active2',
          'past-inactive',
          'past-inactive2',
          'present-inactive',
          'present-inactive2',
          'future-active',
          'future-active2',
          'future-inactive',
          'future-inactive2',
        ],
        [],
      ],

      [
        [
          'unscheduled-active',
          'unscheduled-active2',
          'unscheduled-inactive',
          'unscheduled-inactive2',
          'past-active',
          'past-active2',
          'past-inactive',
          'past-inactive2',
          'present-active',
          'present-active2',
          'present-inactive',
          'present-inactive2',
          'future-active',
          'future-active2',
          'future-inactive',
          'future-inactive2',
        ],
        [
          'unscheduled-active',
          'unscheduled-active2',
          'present-active',
          'present-active2',
        ],
      ],
    ];
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
            'value' => $format_date('-2 hours'),
            'end_value' => $format_date('-1 hours'),
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
            'value' => $format_date('+1 hours'),
            'end_value' => $format_date('+2 hours'),
          ];
          break;
      }

      $this->alerts[$identifier] = $storage->create($values);
      $this->alerts[$identifier]->save();
    }
  }

}
