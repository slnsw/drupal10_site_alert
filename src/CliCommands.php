<?php

declare(strict_types = 1);

namespace Drupal\site_alert;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\site_alert\Entity\SiteAlert;

/**
 * Service with shared code for CLI tools to perform common tasks.
 */
class CliCommands implements CliCommandsInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs a new CliCommands service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function create(string $label, string $message, array $options): void {
    $this->validateCreateInput($label, $message, $options);

    // Set possible options.
    $start = $options['start'] ?? NULL;
    $end = $options['end'] ?? NULL;
    $active = $options['active'] ?? NULL !== FALSE;
    $severity = $this->normalizeSeverity($options['severity'] ?? 'medium');

    $storage = $this->entityTypeManager->getStorage('site_alert');
    $entity_values = [
      'active' => $active,
      'label' => $label,
      'severity' => $severity,
      'message' => $message,
    ];

    if (!empty($start) || !empty($end)) {
      $entity_values['scheduling'] = [
        'value' => $start,
        'end_value' => $end,
      ];
    }

    $site_alert = $storage->create($entity_values);
    $storage->save($site_alert);
  }

  /**
   * {@inheritdoc}
   */
  public function validateCreateInput(string $label, string $message, array &$options): void {
    // Validate the label parameter.
    if (empty($label) || !is_string($label)) {
      throw new \InvalidArgumentException('A label is required.');
    }

    // Validate the message parameter.
    if (empty($message) || !is_string($message)) {
      throw new \InvalidArgumentException('A message is required.');
    }

    // Validate the 'start' and 'end' options.
    foreach (['start', 'end'] as $option) {
      if (!empty($options[$option])) {
        if (strtotime($options[$option]) === FALSE) {
          throw new \InvalidArgumentException(sprintf("Invalid date format for '%s' option.", $option));
        }
        $options[$option] = (new DrupalDateTime($options[$option], DateTimeItemInterface::STORAGE_TIMEZONE))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
      }
    }

    // Validate the 'active' option.
    if (isset($options['active']) && !is_bool($options['active'])) {
      throw new \InvalidArgumentException("The 'active' option should be a boolean value.");
    }

    // Validate the 'severity' option.
    if (!empty($options['severity'])) {
      $severity_options = array_keys(SiteAlert::SEVERITY_OPTIONS);
      if (!in_array($options['severity'], $severity_options)) {
        throw new \InvalidArgumentException(sprintf("The 'severity' option should be one of %s.", implode(',', $severity_options)));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $label): int {
    if (empty($label) || !is_string($label)) {
      throw new \InvalidArgumentException('A label is required.');
    }

    $site_alerts = $this->getAlertsByLabel($label);
    $count = count($site_alerts);

    $this->entityTypeManager->getStorage('site_alert')->delete($site_alerts);

    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function disable(?string $label = NULL): int {
    if (empty($label)) {
      $site_alerts = $this->entityTypeManager->getStorage('site_alert')->loadByProperties(['active' => 1]);
    }
    else {
      $site_alerts = $this->getAlertsByLabel($label, TRUE);
      if (empty($site_alerts)) {
        throw new \InvalidArgumentException(sprintf("No active site alerts found with the label '%s'.", $label));
      }
    }

    $count = 0;
    foreach ($site_alerts as $site_alert) {
      $site_alert->set('active', FALSE)->save();
      $count++;
    }
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function enable(string $label): int {
    $site_alerts = $this->getAlertsByLabel($label, FALSE);
    if (empty($site_alerts)) {
      throw new \InvalidArgumentException(sprintf("No inactive site alerts found with the label '%s'.", $label));
    }

    $count = 0;
    foreach ($site_alerts as $site_alert) {
      $site_alert->set('active', TRUE)->save();
      $count++;
    }
    return $count;
  }

  /**
   * Returns all site alerts that match the given label.
   *
   * @param string $label
   *   The label to match.
   * @param bool|null $active
   *   When TRUE or FALSE only active or inactive site alerts are returned. If
   *   NULL, both are returned.
   *
   * @return \Drupal\site_alert\Entity\SiteAlert[]
   *   An array of site alert entities that match the label.
   */
  protected function getAlertsByLabel(string $label, ?bool $active = NULL): array {
    $site_alerts = [];
    if (!empty($label)) {
      $storage = $this->entityTypeManager->getStorage('site_alert');
      $query = $storage->getQuery();
      $query->condition('label', $label, '=');
      if ($active !== NULL) {
        $query->condition('active', $active);
      }
      $result = $query->execute();

      if (!empty($result)) {
        $site_alerts = $storage->loadMultiple($result);
      }
    }

    return $site_alerts;
  }

  /**
   * Normalizes to one of the three allowed values.
   *
   * @param string $severity
   *   One of the three values low, medium, high.
   *
   * @return string
   *   The normalized severity.
   */
  protected function normalizeSeverity(string $severity = 'medium'): string {
    $severity = trim($severity);
    $severity = strtolower($severity);
    $allowed_severities = ['low', 'medium', 'high'];
    if (!in_array($severity, $allowed_severities)) {
      $severity = 'medium';
    }

    return $severity;
  }

}
