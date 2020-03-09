<?php

namespace Drupal\Tests\site_alert\Functional;

/**
 * Tests that the Site Alert is working correctly.
 *
 * @group site_alert
 */
class SiteAlertUiTest extends SiteAlertTestBase {

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
   * A test user with permission to administer site alerts.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer site alert',
      'add site alerts',
      'update site alerts',
      'delete site alerts',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the creation and deletion of site alerts through the user interface.
   */
  public function testUi() {
    $assert = $this->assertSession();

    // Check that the empty text is shown when no site alerts have been created.
    $this->drupalGet('admin/config/system/site-alerts');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('There are no site alerts yet.');

    // Creating new alert.
    $this->drupalGet('admin/config/system/site-alerts/add');
    $this->fillField('Label', 'Test alert');
    $this->fillField('Active', '1');
    $this->fillField('Severity', 'high');
    $this->fillField('Message', 'Test alert.');
    $this->pressButton('Save');
    $assert->statusCodeEquals(200);

    // Place block.
    $block_id = strtolower($this->randomMachineName(8));
    $this->drupalPlaceBlock('site_alert_block', [
      'id' => $block_id,
      // Disable the JS timeout, we cannot test JS behavior in a browser test.
      // This functionality is tested in SiteAlertTimeoutTest.
      'timeout' => 0,
    ]);

    // Checks that the block containing the alert is displayed on the frontpage.
    $this->drupalGet('<front>');
    $assert->pageTextContains('Test alert.');
    $assert->elementExists('css', '#block-' . $block_id);

    // Now that we have an alert, check that the empty text is no longer shown.
    $this->drupalGet('admin/config/system/site-alerts');
    $assert->pageTextNotContains('There are no site alert entities yet.');

    // Test that the site alert can be deleted.
    $this->clickLink('Delete');
    $assert->pageTextContains('This action cannot be undone.');
    $this->pressButton('Delete');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('The Site Alert Test alert has been deleted.');

    // The block and the alert should no longer be shown.
    $this->drupalGet('<front>');
    $assert->pageTextNotContains('Test alert.');
    $assert->elementNotExists('css', '#block-' . $block_id);
  }

}
