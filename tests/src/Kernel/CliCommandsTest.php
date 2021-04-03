<?php

declare(strict_types = 1);

namespace Drupal\Tests\site_alert\Kernel;

use Drupal\Tests\site_alert\Traits\SiteAlertTestTrait;

/**
 * Tests for the site alert CLI Commands service.
 *
 * @group site_alert
 * @coversDefaultClass \Drupal\site_alert\CliCommands
 */
class CliCommandsTest extends SiteAlertKernelTestBase {

  use SiteAlertTestTrait;

  /**
   * The service to execute CLI commands. This is the system under test.
   *
   * @var \Drupal\site_alert\CliCommandsInterface
   */
  protected $cliCommands;

  /**
   * The site alert entity storage handler.
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
    $this->cliCommands = $this->container->get('site_alert.cli_commands');
  }

  /**
   * Test site alert creation with message and severity.
   *
   * @covers ::create
   */
  public function testCreateSiteAlert(): void {
    // Create a simple alert with a message, and a severity.
    $label = 'phpunit_test_create';
    $message = "A nice bit of message here.";
    $severity = 'low';
    $options = ['severity' => $severity];
    $this->cliCommands->create($label, $message, $options);

    // Check that the created alert contains the expected data.
    $site_alert = $this->loadAlertByLabel($label);
    $this->assertAlert($site_alert, $label, $message, $severity, TRUE);
  }

  /**
   * Test site alert creation with severity and scheduling options.
   *
   * @param string|null $severity
   *   Optional severity.
   * @param string|null $start
   *   Optional start time.
   * @param string|null $end
   *   Optional end time.
   *
   * @covers ::create
   * @dataProvider createSiteAlertWithOptionsProvider
   */
  public function testCreateSiteAlertWithOptions(?string $severity, ?string $start = NULL, ?string $end = NULL): void {
    // Create a simple alert with a message, and a severity.
    $label = 'phpunit_test_create';
    $message = "A nice bit of message here.";
    $severity = 'low';
    $start = '2022-09-12T15:30:01';
    $end = '2022-09-13T15:45:01';
    $options = [
      'severity' => $severity,
      'start' => $start,
      'end' => $end,
    ];
    $this->cliCommands->create($label, $message, $options);

    // Check that the created alert contains the expected data.
    $expected_severity = $severity ?? 'medium';
    $site_alert = $this->loadAlertByLabel($label);
    $this->assertAlert($site_alert, $label, $message, $expected_severity, TRUE, $options);
  }

  /**
   * Data provider for ::testCreateSiteAlertWithOptions().
   *
   * @return array
   *   An array of test cases, each test case an array with the following
   *   elements:
   *   - An optional severity.
   *   - An optional start time.
   *   - An optional end time.
   */
  public function createSiteAlertWithOptionsProvider(): array {
    return [
      [
        'low',
        '2022-09-12T15:30:01',
        '2022-09-13T15:45:01',
      ],
      [
        NULL,
        '2022-09-12T15:30:01',
        '2022-09-13T15:45:01',
      ],
      [
        'medium',
        NULL,
        '2022-09-13T15:45:01',
      ],
      [
        'high',
        '2022-09-12T15:30:01',
        NULL,
      ],
      [
        NULL,
        NULL,
        '2022-09-13T15:45:01',
      ],
      [
        NULL,
        '2022-09-12T15:30:01',
        NULL,
      ],
      [
        'low',
        NULL,
        NULL,
      ],
      [
        NULL,
        NULL,
        NULL,
      ],
    ];
  }

  /**
   * Test site alert delete.
   *
   * @covers ::delete
   */
  public function testDeleteSiteAlert(): void {
    // Create a simple alert with a message, and a severity.
    $label = 'phpunit_test_delete';
    $message = "A nice bit of message here.";
    $options = ['severity' => 'low'];
    $this->cliCommands->create($label, $message, $options);

    // Delete should result in at least one removal.
    $result = $this->cliCommands->delete($label);
    $this->assertGreaterThan(0, $result);
    // If it was deleted, there should be no more found.
    $siteAlerts = $this->loadAlertsByLabel($label);
    $this->assertCount(0, $siteAlerts);
  }

  /**
   * Checks that invalid combinations of parameters throw an exception.
   *
   * @covers ::disable
   */
  public function testDisableSiteAlertValidation(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->cliCommands->disable('some-non-existing-site-alert');
  }

  /**
   * Checks that a site alert can be disabled by passing the label.
   *
   * @covers ::disable
   */
  public function testDisableSiteAlertByLabel(): void {
    // Create an active alert.
    $label = 'phpunit_test_disable';
    $message = 'This alert is active, for now.';
    $this->cliCommands->create($label, $message, []);

    // There should be 1 active alert.
    $this->assertActiveAlertCount(1);

    // Disable the alert. This should return the number of alerts that were
    // disabled.
    $count = $this->cliCommands->disable('phpunit_test_disable');
    $this->assertEquals(1, $count);
    $this->assertActiveAlertCount(0);

    // Try to disable the alert again. This should throw an exception indicating
    // that no alerts were disabled.
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("No active site alerts found with the label 'phpunit_test_disable'.");
    $this->cliCommands->disable('phpunit_test_disable');
  }

  /**
   * Checks that all site alerts can be disabled by passing the 'all' option.
   *
   * @covers ::disable
   */
  public function testDisableAllSiteAlerts(): void {
    // Create a range of active and inactive alerts.
    $alerts = [
      [
        'phpunit_test_disable_1',
        'An active alert',
        [],
      ],
      [
        'phpunit_test_disable_2',
        'Another active alert',
        [],
      ],
      [
        'phpunit_test_disable_3',
        'An inactive alert',
        ['active' => FALSE],
      ],
    ];
    foreach ($alerts as [$label, $message, $options]) {
      $this->cliCommands->create($label, $message, $options);
    }

    // There should be 2 active alerts.
    $this->assertActiveAlertCount(2);

    // Disable all alerts. This should return the number of alerts that were
    // disabled.
    $count = $this->cliCommands->disable(NULL, ['all' => TRUE]);
    $this->assertEquals(2, $count);
    $this->assertActiveAlertCount(0);

    // Try to disable all alerts again. This should return 0 indicating that no
    // alerts were disabled.
    $count = $this->cliCommands->disable(NULL, ['all' => TRUE]);
    $this->assertEquals(0, $count);
    $this->assertActiveAlertCount(0);
  }

  /**
   * Checks that invalid combinations of parameters throw an exception.
   *
   * @covers ::enable
   */
  public function testEnableSiteAlertValidation(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->cliCommands->enable('some-non-existing-site-alert');
  }

  /**
   * Checks that a site alert can be enabled by passing the label.
   *
   * @covers ::enable
   */
  public function testEnableSiteAlertByLabel(): void {
    // Create an inactive alert.
    $label = 'phpunit_test_enable';
    $message = 'This alert is not yet active.';
    $this->cliCommands->create($label, $message, ['active' => FALSE]);

    // There should be 1 inactive alert.
    $this->assertInactiveAlertCount(1);

    // Enable the alert. This should return the number of alerts that were
    // enabled.
    $count = $this->cliCommands->enable('phpunit_test_enable');
    $this->assertEquals(1, $count);
    $this->assertActiveAlertCount(1);
    $this->assertInactiveAlertCount(0);

    // Try to enable the alert again. This should throw an exception indicating
    // that no alerts were enabled.
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("No inactive site alerts found with the label 'phpunit_test_enable'.");
    $this->cliCommands->enable('phpunit_test_enable');
  }

}
