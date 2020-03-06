<?php

namespace Drupal\Tests\block\FunctionalJavascript;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\site_alert\Entity\SiteAlert;

/**
 * Tests refreshing site alerts at regular intervals by setting a timeout.
 *
 * @group site_alert
 */
class SiteAlertTimeoutTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo Remove the 'options' module once issue #3111058 is fixed.
   * @see https://www.drupal.org/project/site_alert/issues/3111058
   */
  protected static $modules = ['block', 'options', 'site_alert', 'user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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

  /**
   * Checks that the alert with the given message appears on the page.
   *
   * @param string $message
   *   The message contained in the alert that is expected to appear.
   */
  protected function assertSiteAlertAppears($message) {
    $condition = 'jQuery(\'.site-alert div.text:contains("' . $message . '")\').length > 0;';
    $this->assertJsCondition($condition);
  }

  /**
   * Checks that the alert with the given message disappears from the page.
   *
   * @param string $message
   *   The message contained in the alert that is expected to disappear.
   */
  protected function assertSiteAlertDisappears($message) {
    $condition = 'jQuery(\'.site-alert div.text:contains("' . $message . '")\').length == 0;';
    $this->assertJsCondition($condition);
  }

  /**
   * Checks that the site alert with the given message is visible.
   *
   * @param string $message
   *   The message that should be present in a visible alert.
   */
  protected function assertSiteAlertVisible($message) {
    $selector = 'div:contains("' . $message . '")';
    $this->assertElementPresent($selector);
  }

  /**
   * Checks that the site alert with the given message is not visible.
   *
   * @param string $message
   *   The message that should not be present in any of the visible alerts.
   */
  protected function assertSiteAlertNotVisible($message) {
    $selector = 'div:contains("' . $message . '")';
    $this->assertElementNotPresent($selector);
  }

  /**
   * Checks that the expected number of alerts is present on the page.
   *
   * @param int $count
   *   The number of alerts that are expected to be visible.
   */
  protected function assertSiteAlertCount($count) {
    $alerts = $this->getSession()->getPage()->findAll('css', '.site-alert div.text');
    $this->assertCount($count, $alerts);
  }

  /**
   * Asserts that the site alert block is present on the page.
   */
  protected function assertSiteAlertBlockPresent() {
    $this->assertElementPresent('#block-site-alert-block');
  }

  /**
   * Asserts that the site alert block is not present on the page.
   */
  protected function assertSiteAlertBlockNotPresent() {
    $this->assertElementNotPresent('#block-site-alert-block');
  }

  /**
   * Asserts that the JavaScript code to refresh the alerts is present.
   */
  protected function assertJavaScriptPresent() {
    $this->assertElementPresent('script[src*="site_alert.js"]');
  }

  /**
   * Asserts that the JavaScript code to refresh the alerts is present.
   */
  protected function assertJavaScriptNotPresent() {
    $this->assertElementNotPresent('script[src*="site_alert.js"]');
  }

}
