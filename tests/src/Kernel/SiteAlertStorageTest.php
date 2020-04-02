<?php

namespace Drupal\Tests\site_alert\Kernel;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Tests for the site alert entity storage handler.
 *
 * @group site_alert
 * @coversDefaultClass \Drupal\site_alert\SiteAlertStorage
 */
class SiteAlertStorageTest extends SiteAlertKernelTestBase {

  /**
   * The site alert entity storage handler. This is the system under test.
   *
   * @var \Drupal\site_alert\SiteAlertStorageInterface
   */
  protected $siteAlertStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->siteAlertStorage = $this->entityTypeManager->getStorage('site_alert');
  }

  /**
   * Tests the retrieval of the cache max age.
   *
   * @param array $alerts_to_create
   *   An array of identifiers for the site alerts that should be created at the
   *   start of the test.
   * @param int $expected_max_age
   *   The max age that is expected to be returned by the method under test.
   *
   * @covers ::getCacheMaxAge
   * @dataProvider getCacheMaxAgeProvider
   */
  public function testGetMaxAge(array $alerts_to_create, int $expected_max_age) {
    // Populate the database with the alerts as specified in the test case.
    $this->createAlerts($alerts_to_create);

    // Retrieve the cache max age.
    $actual_max_age = $this->siteAlertStorage->getCacheMaxAge();

    // Because of possible delays incurred during the setup of the test we allow
    // for a grace period of a few seconds, except in the case where the cache
    // is expected to be permanent.
    if ($expected_max_age === CacheBackendInterface::CACHE_PERMANENT) {
      $this->assertEquals($expected_max_age, $actual_max_age);
    }
    else {
      $this->assertTrue(abs($expected_max_age - $actual_max_age) < 2);
    }
  }

  /**
   * Data provider for ::testGetMaxAge().
   *
   * @return array
   *   An array of test cases, each test case an array consisting of:
   *   - An array of identifiers for the site alerts that should be created at
   *     the start of the test.
   *   - An array of identifiers for the site alerts that are expected to be
   *     returned by the method under test.
   *
   * @see testGetMaxAge()
   */
  public function getCacheMaxAgeProvider() {
    return [
      [
        [],
        CacheBackendInterface::CACHE_PERMANENT,
      ],

      [
        ['unscheduled-active'],
        CacheBackendInterface::CACHE_PERMANENT,
      ],

      [
        ['unscheduled-inactive'],
        CacheBackendInterface::CACHE_PERMANENT,
      ],

      [
        ['past-active'],
        CacheBackendInterface::CACHE_PERMANENT,
      ],

      [
        ['past-inactive'],
        CacheBackendInterface::CACHE_PERMANENT,
      ],

      [
        ['present-active'],
        // The `present-active` alert will disappear in 1 hour.
        1 * 60 * 60,
      ],

      [
        ['present-inactive'],
        CacheBackendInterface::CACHE_PERMANENT,
      ],

      [
        ['future-active'],
        // The `future-active` alert will appear in 2 hours.
        2 * 60 * 60,
      ],

      [
        ['future-inactive'],
        CacheBackendInterface::CACHE_PERMANENT,
      ],

      [
        ['unscheduled-active', 'present-active'],
        // The `present-active` alert will disappear in 1 hour.
        1 * 60 * 60,
      ],

      [
        [
          'unscheduled-inactive',
          'past-active',
          'past-inactive',
          'future-active',
          'future-inactive',
        ],
        // The `future-active` alert will appear in 2 hours.
        2 * 60 * 60,
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
        // The `present-active` alert will disappear in 1 hour.
        1 * 60 * 60,
      ],

      [
        [
          'unscheduled-active',
          'unscheduled-active2',
          'present-active',
          'present-active2',
        ],
        // The `present-active` alerts will disappear in 1 hour.
        1 * 60 * 60,
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
        // The `future-active` alert will appear in 2 hours.
        2 * 60 * 60,
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
        // The `present-active` alerts will disappear in 1 hour.
        1 * 60 * 60,
      ],
    ];
  }

}
