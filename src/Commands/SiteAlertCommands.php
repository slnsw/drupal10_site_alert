<?php

declare(strict_types = 1);

namespace Drupal\site_alert\Commands;

use Drupal\site_alert\CliCommandsInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file for the Site Alert module.
 */
class SiteAlertCommands extends DrushCommands {

  /**
   * The CLI service for doing CLI operations on site_alert.
   *
   * @var \Drupal\site_alert\CliCommandsInterface
   */
  protected $cli;

  /**
   * Construct a new Drush command object.
   *
   * @param \Drupal\site_alert\CliCommandsInterface $cli_commands
   *   The shared service for CLI commands.
   */
  public function __construct(CliCommandsInterface $cli_commands) {
    parent::__construct();

    $this->cli = $cli_commands;
  }

  /**
   * Create a site alert.
   *
   * @param string $label
   *   The label of the site alert. This is an internal identifier which will
   *   not be shown to end users.
   * @param string $message
   *   The text content of the site alert.
   * @param array $options
   *   Optional array of options keyed by option [start, end, severity].
   *
   * @command site-alert:create
   *
   * @option start
   *   Optional time when the site alert should appear. Can be in ISO 8601
   *   format ("2020-10-22T14:30:00-05:00") or in a human readable format like
   *   "October 22, 2020" or "Saturday 12:30".
   * @option end
   *   Optional time when the site alert should disappear. Can be in ISO 8601
   *   format ("2020-10-22T14:30:00-05:00") or in a human readable format like
   *   "+6 hours" or "midnight".
   * @option severity
   *   Optional severity of the site alert [low, medium (default), high].
   * @option active
   *   Marks the site alert as active.
   *
   * @usage drush site-alert:create "label" "Message"
   *   Create a site-alert with the label and message with medium severity. The
   *   alert will be immediately visible and will remain so until manually
   *   disabled or deleted.
   * @usage drush site-alert:create "label name" "message" --severity=high --no-active.
   *   Create a site-alert with the label and message with high severity. The
   *   alert is inactive and will not be visible until activated.
   * @usage drush site-alert:create "label name" "message" --start=2022-10-15T15:00:00 --end=2022-10-15T17:00:00
   *   Create a site alert with the label and message that will be displayed
   *   between the start and end dates provided.
   * @usage drush site-alert:create "label name" "message" --start=13:45 --end="tomorrow 13:45"
   *   Create a site alert with the label and message that will be displayed
   *   this afternoon at 13:45 and will end tomorrow at the same time.
   * @usage drush site-alert:create "label name" "message" --start="2 hours 30 minutes"
   *   Create a site alert with the label and message that will be displayed
   *   150 minutes from now and will remain visible until manually disabled or
   *   deleted.
   * @usage drush site-alert:create "label name" "message" --end="15 minutes"
   *   Create a site alert with the label and message that will be displayed
   *   immediately and will disappear after 15 minutes.
   */
  public function create(string $label, string $message, array $options = [
    'start' => NULL,
    'end' => NULL,
    'severity' => NULL,
    'active' => TRUE,
  ]): int {
    $vars = [
      '@name' => 'site alert',
      '@label' => $label,
    ];

    try {
      $this->cli->create($label, $message, $options);
    }
    catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
      return self::EXIT_FAILURE;
    }

    $this->logger()->success((string) dt("Created @name '@label'.", $vars));
    return self::EXIT_SUCCESS;
  }

  /**
   * Delete site alert(s) matching the label.
   *
   * @param string $label
   *   The label of the site alert(s) to delete.
   *
   * @command site-alert:delete
   *
   * @usage drush site-alert:delete "label"
   *   Delete any site alerts that are active and have the label of "label".
   */
  public function delete(string $label): int {
    if (!$this->io()->confirm(dt("Are you sure you want to delete the site alert labeled '@label'?", [
      '@label' => $label,
    ]))) {
      $this->logger()->warning('Operation cancelled by user');
      return self::EXIT_FAILURE;
    }

    try {
      $count = $this->cli->delete($label);
    }
    catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
      return self::EXIT_FAILURE;
    }

    $vars = [
      '@name' => 'site alerts',
      '@count' => $count,
      '@label' => $label,
    ];

    if ($count >= 1) {
      $this->logger()->success((string) dt("Deleted @count @name labelled '@label'.", $vars));
    }
    else {
      $this->logger()->notice((string) dt("Found no @name with label '@label' to delete.", $vars));
    }
    return self::EXIT_SUCCESS;
  }

  /**
   * Disable site alert(s).
   *
   * @param string|null $label
   *   The label of site alert to disable. If no label is passed all site alerts
   *   will be disabled.
   *
   * @command site-alert:disable
   *
   * @usage drush site-alert:disable
   *   Disable all site alerts.
   * @usage drush site-alert:disable "my-alert"
   *   Disable the site alert with the label "my-alert".
   */
  public function disable(?string $label = NULL): int {
    try {
      $count = $this->cli->disable($label);
    }
    catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
      return self::EXIT_FAILURE;
    }

    if ($count === 0) {
      // If a specific alert was given and it could not be disabled, then the
      // user has given invalid input. Alert the user by returning an error.
      if (!empty($label)) {
        $vars = ['@label' => $label];
        $this->logger()->error((string) dt("No active site alerts found with the label '@label'.", $vars));
        return self::EXIT_FAILURE;
      }
      else {
        $this->logger()->notice('There were no site alerts to disable.');
      }
    }
    elseif (empty($label)) {
      $this->logger()->success('All active site alerts have been disabled.');
    }
    else {
      $vars = ['@label' => $label];
      $this->logger()->success((string) dt("Disabled site alert '@label'.", $vars));
    }
    return self::EXIT_SUCCESS;
  }

  /**
   * Enable a site alert.
   *
   * @param string $label
   *   The label of site alert to enable.
   *
   * @command site-alert:enable
   *
   * @usage drush site-alert:enable my-alert
   *   Enable the site alert with the label "my-alert".
   */
  public function enable(string $label): int {
    try {
      $count = $this->cli->enable($label);
    }
    catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
      return self::EXIT_FAILURE;
    }

    if ($count === 0) {
      $vars = ['@label' => $label];
      $this->logger()->error((string) dt("No inactive site alerts found with the label '@label'.", $vars));

      return self::EXIT_FAILURE;
    }

    $vars = ['@label' => $label];
    $this->logger()->success((string) dt("Enabled site alert '@label'.", $vars));

    return self::EXIT_SUCCESS;
  }

}
