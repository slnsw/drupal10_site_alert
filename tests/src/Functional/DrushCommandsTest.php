<?php

declare(strict_types = 1);

namespace Drupal\Tests\site_alert\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\site_alert\Traits\SiteAlertTestTrait;
use Drush\TestTraits\DrushTestTrait;

/**
 * Execute drush commands on site_alert.
 *
 * @group site_alert
 */
class DrushCommandsTest extends BrowserTestBase {

  use DrushTestTrait;
  use SiteAlertTestTrait;

  /**
   * The site alert entity storage handler.
   *
   * @var \Drupal\site_alert\SiteAlertStorageInterface
   */
  protected $siteAlertStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'site_alert',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->siteAlertStorage = $entity_type_manager->getStorage('site_alert');
  }

  /**
   * Tests site-alert:create minimal.
   */
  public function testCreateMinimalDelete(): void {
    $label = 'automated-test-alert';
    $message = 'A site alert test.';
    $this->drush('site-alert:create', [$label, $message]);
    $this->assertErrorOutputEquals("[success] Created site alert 'automated-test-alert'.");
    $this->assertAlertCount(1);
    $this->assertAlertByLabel($label, $message);

    $this->drush('site-alert:delete', [$label]);
    $this->assertErrorOutputEquals("[success] Deleted 1 site alerts labelled 'automated-test-alert'.");
    $this->assertAlertCount(0);
  }

  /**
   * Tests site-alert:delete non-existent alert.
   */
  public function testDeleteNone(): void {
    $this->drush('site-alert:delete', ['crazy8342111hash65923label']);
    $this->assertErrorOutputEquals("[notice] Found no site alerts with label 'crazy8342111hash65923label' to delete.");
  }

  /**
   * Tests site-alert:create with start, but no end.
   */
  public function testCreateStartNoEnd(): void {
    $label = 'automated-test-alert-no-end';
    $message = 'A site alert with a start date but no end date.';
    // Set the end date comfortably in the future.
    $next_year = date('Y') + 1;
    $start_time = $next_year . '-10-15T15:00:00';
    $scheduling_options = ['start' => $start_time];
    $this->drush(
      'site-alert:create',
      [$label, $message],
      $scheduling_options
    );
    $this->assertErrorOutputEquals("[success] Created site alert 'automated-test-alert-no-end'.");
    $this->assertAlertCount(1);
    $this->assertAlertByLabel($label, $message, 'medium', TRUE, $scheduling_options);

    $this->drush('site-alert:delete', [$label]);
    $this->assertErrorOutputEquals("[success] Deleted 1 site alerts labelled 'automated-test-alert-no-end'.");
    $this->assertAlertCount(0);
  }

  /**
   * Tests site-alert:create with and end, but no start.
   *
   * When the start date is omitted it should default to now.
   */
  public function testCreateEndNoStart(): void {
    $label = 'automated-test-alert-no-start';
    $message = 'A site alert test.';
    // Set the end date comfortably in the future.
    $next_year = date('Y') + 1;
    $end_time = $next_year . '-10-15T15:00:00';
    $scheduling_options = ['end' => $end_time];
    $this->drush(
      'site-alert:create',
      [$label, $message],
      $scheduling_options
    );
    $this->assertErrorOutputEquals("[success] Created site alert 'automated-test-alert-no-start'.");
    $this->assertAlertCount(1);
    $this->assertAlertByLabel($label, $message, 'medium', TRUE, $scheduling_options);

    $this->drush('site-alert:delete', [$label]);
    $this->assertErrorOutputEquals("[success] Deleted 1 site alerts labelled 'automated-test-alert-no-start'.");
    $this->assertAlertCount(0);
  }

  /**
   * Tests site-alert:disable [label].
   */
  public function testDisableWithLabel(): void {
    $this->drush(
      'site-alert:create',
      ['automated-test-alert', 'A site alert test.'],
      []
    );
    $this->assertActiveAlertCount(1);
    $this->drush('site-alert:disable', ['automated-test-alert']);
    $this->assertErrorOutputEquals("[success] Disabled site alert 'automated-test-alert'.");
    $this->assertAlertCount(1);
    $this->assertActiveAlertCount(0);
  }

  /**
   * Tests site-alert:disable --all.
   */
  public function testDisableAll(): void {
    $this->drush(
      'site-alert:create',
      ['automated-test-alert', 'A test site alert.'],
      []
    );
    $this->drush(
      'site-alert:create',
      ['automated-test-alert-2', 'Another test site alert.'],
      []
    );
    $this->assertActiveAlertCount(2);
    $this->drush('site-alert:disable', [], []);
    $this->assertErrorOutputEquals("[success] All active site alerts have been disabled.");
    $this->assertAlertCount(2);
    $this->assertActiveAlertCount(0);
  }

  /**
   * Tests site-alert:disable with invalid input.
   */
  public function testDisableInput(): void {
    $this->drush('site-alert:disable', ['automated-test-alert'], [], NULL, NULL, 1);
    $this->assertErrorOutputEquals("[error] No active site alerts found with the label 'automated-test-alert'.");
    $this->drush('site-alert:disable', [], [], NULL, NULL, 0);
    $this->assertErrorOutputEquals('[notice] There were no site alerts to disable.');
  }

}
