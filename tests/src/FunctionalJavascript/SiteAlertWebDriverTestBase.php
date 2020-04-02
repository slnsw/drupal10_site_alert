<?php

namespace Drupal\Tests\site_alert\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for functional JS tests for the Site Alerts module.
 *
 * @group site_alert
 */
abstract class SiteAlertWebDriverTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'site_alert', 'user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable page caching.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 3600);
    $config->save();
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
