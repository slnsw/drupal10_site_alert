<?php

namespace Drupal\Tests\site_alert\FunctionalJavascript;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\site_alert\Entity\SiteAlert;

/**
 * Tests that the workaround for the core caching bug is working as expected.
 *
 * Due to a bug in Drupal core the cache max age is ignored when the core
 * page_cache module is enabled. This bug prevents any cached pages from being
 * invalidated in time for a scheduled alert to be displayed. We are working
 * around this by injecting JS code which will retrieve the first alert on page
 * load.
 *
 * This workaround is critical for us since most websites will have the page
 * cache enabled.
 *
 * @todo Once the bug is fixed the workaround and this test should be removed.
 * @see https://www.drupal.org/project/site_alert/issues/3121988
 *
 * @group site_alert
 */
class SiteAlertCacheWorkaroundTest extends SiteAlertWebDriverTestBase {

  /**
   * Tests that the workaround to display alerts on cached pages works.
   */
  public function testPageCacheWorkaround() {
    $this->drupalPlaceBlock('site_alert_block', [
      'id' => 'site_alert_block',
      // Disable the timeout. Under normal circumstances this would disable AJAX
      // calls completely, but due to the caching bug we still need to load
      // alerts using JS.
      'timeout' => 0,
    ]);

    // Create a site alert that is scheduled to appear in a few seconds.
    $alert = SiteAlert::create([
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
    ]);
    $alert->save();

    // Load the front page. The scheduled alert should not be visible yet.
    $this->drupalGet('<front>');
    $this->assertSiteAlertNotVisible('Scheduled alert');

    // Since the workaround is in effect, the JavaScript code that is
    // responsible for refreshing the alerts should be loaded in the page.
    $this->assertJavaScriptPresent();

    // Check that the alert appears within a few seconds. Thanks to the
    // workaround this will work regardless of the fact that the page is cached.
    $this->assertSiteAlertAppears('Scheduled alert');

    // Disable the alert. Now there are no more scheduled alerts, so the
    // workaround is not needed and the JS code should not be loaded.
    $alert->set('active', FALSE)->save();

    $this->drupalGet('<front>');
    $this->assertSiteAlertNotVisible('Scheduled alert');
    $this->assertJavaScriptNotPresent();
  }

}
