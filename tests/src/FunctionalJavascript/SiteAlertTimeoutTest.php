<?php

namespace Drupal\Tests\site_alert\FunctionalJavascript;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\site_alert\Entity\SiteAlert;

/**
 * Tests refreshing site alerts at regular intervals by setting a timeout.
 *
 * @group site_alert
 */
class SiteAlertTimeoutTest extends SiteAlertWebDriverTestBase {

  /**
   * Tests the automatic refreshing of site alerts.
   */
  public function testSiteAlertTimeouts() {
    $this->drupalPlaceBlock('site_alert_block', [
      'id' => 'site_alert_block',
      // Use a fast timeout of 1 second so we don't have to wait too long.
      'timeout' => 1,
    ]);

    // There are no alerts yet. The block should be present on the page so it
    // can display any alerts that become active, but it should be empty.
    $this->drupalGet('<front>');
    $this->assertSiteAlertBlockPresent();
    $this->assertSiteAlertCount(0);

    // The JavaScript code that is responsible for refreshing the alerts should
    // be loaded in the page.
    $this->assertJavaScriptPresent();

    // Create two site alerts: an active and an inactive one.
    SiteAlert::create([
      'active' => TRUE,
      'severity' => 'low',
      'message' => [
        'value' => 'Active alert',
        'format' => 'plain_text',
      ],
      'label' => 'Active',
    ])->save();

    SiteAlert::create([
      'active' => FALSE,
      'severity' => 'medium',
      'message' => [
        'value' => 'Inactive alert',
        'format' => 'plain_text',
      ],
      'label' => 'Inactive',
    ])->save();

    // Wait until the active site alert appears.
    $this->assertSiteAlertAppears('Active');

    // There should be one alert on the page right now. The "inactive" alert
    // should not have appeared.
    $this->assertSiteAlertCount(1);
    $this->assertSiteAlertNotVisible('Inactive');

    // Create a site alert that is scheduled to appear in a few seconds.
    SiteAlert::create([
      'active' => TRUE,
      'severity' => 'high',
      'message' => [
        'value' => 'Scheduled alert',
        'format' => 'plain_text',
      ],
      'label' => 'Scheduled',
      'scheduling' => [
        'value' => (new DrupalDateTime('+2 seconds', DateTimeItemInterface::STORAGE_TIMEZONE))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      ],
    ])->save();

    // The scheduled alert should not be initially visible, but it should appear
    // after a few seconds.
    $this->assertSiteAlertNotVisible('Scheduled alert');
    $this->assertSiteAlertAppears('Scheduled alert');

    // Create a site alert that is immediately active and scheduled to disappear
    // in a few seconds.
    SiteAlert::create([
      'active' => TRUE,
      'severity' => 'low',
      'message' => [
        'value' => 'Disappearing',
        'format' => 'plain_text',
      ],
      'label' => 'Disappearing',
      'scheduling' => [
        'end_value' => (new DrupalDateTime('+3 seconds', DateTimeItemInterface::STORAGE_TIMEZONE))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      ],
    ])->save();
    $this->assertSiteAlertAppears('Disappearing');
    $this->assertSiteAlertDisappears('Disappearing');
  }

  /**
   * Tests that automatic refreshing of site alerts can be disabled.
   */
  public function testSiteAlertTimeoutsDisabled() {
    // Place the Site Alerts block but disable the refreshing by setting the
    // timeout to 0.
    $this->drupalPlaceBlock('site_alert_block', [
      'id' => 'site_alert_block',
      'timeout' => 0,
    ]);

    // There are no alerts yet. The block should not be present on the page.
    $this->drupalGet('<front>');
    $this->assertSiteAlertBlockNotPresent();

    // The JavaScript code to refresh the alerts should not be included.
    $this->assertJavaScriptNotPresent();

    // Create an active and an inactive site alert.
    SiteAlert::create([
      'active' => TRUE,
      'severity' => 'low',
      'message' => [
        'value' => 'Active alert',
        'format' => 'plain_text',
      ],
      'label' => 'Active',
    ])->save();

    SiteAlert::create([
      'active' => FALSE,
      'severity' => 'medium',
      'message' => [
        'value' => 'Inactive alert',
        'format' => 'plain_text',
      ],
      'label' => 'Inactive',
    ])->save();

    // The alert should appear when we manually refresh the page.
    $this->drupalGet('<front>');
    $this->assertSiteAlertBlockPresent();
    $this->assertSiteAlertVisible('Active alert');
    $this->assertSiteAlertNotVisible('Inactive alert');
    $this->assertSiteAlertCount(1);

    // The JavaScript code to refresh the alerts should still not be present.
    $this->assertJavaScriptNotPresent();
  }

}
