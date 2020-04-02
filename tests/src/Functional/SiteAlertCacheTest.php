<?php

namespace Drupal\Tests\site_alert\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\site_alert\Entity\SiteAlert;
use Drupal\site_alert\Plugin\Block\SiteAlertBlock;
use Drupal\Tests\system\Functional\Cache\PageCacheTagsTestBase;

/**
 * Tests that the page cache correctly varies on the active site alerts.
 *
 * @group site_alert
 */
class SiteAlertCacheTest extends PageCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'site_alert',
    'block',
  ];

  /**
   * Tests that the page cache varies correctly by the currently active alerts.
   *
   * @dataProvider pageCacheProvider
   */
  public function testPageCache($timeout) {
    // Enable the site alerts block.
    $this->drupalPlaceBlock('site_alert_block', [
      'id' => 'site_alert_block',
      // Enable or disable the AJAX timeout depending on the test case.
      'timeout' => $timeout,
    ]);

    // Warm the page cache. The first request is always a cache miss.
    $url = Url::fromRoute('<front>');
    $this->verifyPageCache($url, 'MISS');

    // On the second request the page should be cached. We will also check that
    // the site alert list cache tag is present, in addition to the standard
    // tags added by the system, user, and block modules.
    $expected_tags = [
      'block_view',
      'config:block.block.site_alert_block',
      'config:block_list',
      'config:system.site',
      'config:user.role.anonymous',
      'http_response',
      'rendered',
      'site_alert_list',
    ];
    $this->verifyPageCache($url, 'HIT', $expected_tags);

    // Create an inactive site alert.
    $inactive_alert = SiteAlert::create([
      'active' => FALSE,
      'severity' => 'medium',
      'message' => [
        'value' => 'Inactive alert',
        'format' => 'plain_text',
      ],
      'label' => 'Inactive',
    ]);
    $inactive_alert->save();

    // The page cache should be invalidated because the list of site alerts has
    // changed.
    $this->verifyPageCache($url, 'MISS');

    // On a second page load it should again be served from cache. The new alert
    // is not visible, so the cache tags should not change.
    $this->verifyPageCache($url, 'HIT', $expected_tags);

    // No site alerts should be visible.
    $this->assertSiteAlertCount(0);

    // Create an active site alert. This should also invalidate the page cache.
    $active_alert = SiteAlert::create([
      'active' => TRUE,
      'severity' => 'low',
      'message' => [
        'value' => 'Active alert',
        'format' => 'plain_text',
      ],
      'label' => 'Active',
    ]);
    $active_alert->save();

    $this->verifyPageCache($url, 'MISS');

    // Now the cache tag of the new site alert should be present.
    $expected_tags[] = "site_alert:{$active_alert->id()}";
    $this->verifyPageCache($url, 'HIT', $expected_tags);

    // There should be 1 alert on the page.
    $this->assertSiteAlertCount(1);

    // Activate the inactive alert. This should invalidate the page cache, and
    // the alert should appear on the page. Also its cache tag should be
    // included now.
    $inactive_alert->set('active', TRUE)->save();
    $this->verifyPageCache($url, 'MISS');
    $expected_tags['inactive'] = "site_alert:{$inactive_alert->id()}";
    $this->verifyPageCache($url, 'HIT', $expected_tags);
    $this->assertSiteAlertCount(2);

    // Deactivate it again. Page cache should be invalidated, the alert and its
    // cache tag should no longer be present.
    $inactive_alert->set('active', FALSE)->save();
    $this->verifyPageCache($url, 'MISS');
    unset($expected_tags['inactive']);
    $this->verifyPageCache($url, 'HIT', $expected_tags);
    $this->assertSiteAlertCount(1);

    // @todo The remainder of the test is currently disabled due to a bug in the
    //   core page_cache module. We are working around this bug using JavaScript
    //   code which in tested in `CacheWorkAroundTest`. Once the bug is fixed
    //   this can be completed.
    // @see https://www.drupal.org/project/site_alert/issues/3121988
    // @see \Drupal\Tests\block\FunctionalJavascript\SiteAlertCacheWorkaroundTest
    $this->markTestIncomplete();

    // Create a site alert that is scheduled to appear in a few seconds and
    // disappear again a few seconds later.
    $scheduled_alert = SiteAlert::create([
      'active' => TRUE,
      'severity' => 'high',
      'message' => [
        'value' => 'Scheduled alert',
        'format' => 'plain_text',
      ],
      'label' => 'Scheduled',
      'scheduling' => [
        'value' => (new DrupalDateTime('+1 second', DateTimeItemInterface::STORAGE_TIMEZONE))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
        'end_value' => (new DrupalDateTime('+2 seconds', DateTimeItemInterface::STORAGE_TIMEZONE))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      ],
    ]);
    $scheduled_alert->save();

    // Wait until the alert appears.
    $end_time = microtime(TRUE) + 5;
    do {
      $this->drupalGet($url);
    } while (microtime(TRUE) < $end_time && !$this->getSession()->getPage()->hasContent('Scheduled alert'));
    $expected_tags['scheduled'] = "site_alert:{$scheduled_alert->id()}";
    $this->verifyPageCache($url, 'MISS', $expected_tags);
    $this->assertSiteAlertCount(2);

    $end_time = microtime(TRUE) + 5;
    do {
      $this->drupalGet($url);
    } while (microtime(TRUE) < $end_time && $this->getSession()->getPage()->hasContent('Scheduled alert'));
    $this->assertEquals($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $this->assertSiteAlertCount(1);
  }

  /**
   * Provides test cases for ::testPageCache().
   *
   * @return array
   *   An array of test cases, each test case an array with a single value: the
   *   timeout for AJAX refreshing.
   *
   * @see ::testPageCache()
   */
  public function pageCacheProvider() {
    return [
      // Test case using a block that enjoys AJAX refreshments.
      [
        SiteAlertBlock::TIMEOUT_DEFAULT,
      ],
      // Test case using a block with AJAX refreshing of site alerts disabled.
      [
        0,
      ],
    ];
  }

  /**
   * Asserts that the expected number of site alerts is visible on the page.
   *
   * @param int $count
   *   The expected number of site alerts.
   */
  protected function assertSiteAlertCount($count) {
    $alerts = $this->getSession()->getPage()->findAll('css', '.site-alert div.text');
    $this->assertCount($count, $alerts);
  }

}
