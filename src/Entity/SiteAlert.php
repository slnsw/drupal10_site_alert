<?php

namespace Drupal\site_alert\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Implements SiteAlert class.
 *
 * @ContentEntityType(
 *   id = "site_alert",
 *   label = @Translation("Site Alert"),
 *   label_collection = @Translation("Site Alerts"),
 *   label_singular = @Translation("site alert"),
 *   label_plural = @Translation("site alerts"),
 *   label_count = @PluralTranslation(
 *     singular = "@count site alert",
 *     plural = "@count site alerts",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\site_alert\SiteAlertListBuilder",
 *     "form" = {
 *       "default" = "Drupal\site_alert\Entity\Form\SiteAlertForm",
 *       "add" = "Drupal\site_alert\Entity\Form\SiteAlertForm",
 *       "edit" = "Drupal\site_alert\Entity\Form\SiteAlertForm",
 *       "delete" = "Drupal\site_alert\Entity\Form\SiteAlertDeleteForm",
 *     },
 *     "access" = "Drupal\site_alert\SiteAlertAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "storage" = "Drupal\site_alert\SiteAlertStorage",
 *   },
 *   base_table = "site_alerts",
 *   admin_permission = "administer site alert",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/config/system/site-alerts",
 *     "add-form" = "/admin/config/system/site-alerts/add",
 *     "edit-form" = "/admin/config/system/site-alerts/{site_alert}/edit",
 *     "delete-form" = "/admin/config/system/site-alerts/{site_alert}/delete",
 *   },
 * )
 */
class SiteAlert extends ContentEntityBase {

  /**
   * The alert severities.
   */
  const SEVERITY_OPTIONS = [
    'low' => 'Low',
    'medium' => 'Medium',
    'high' => 'High',
  ];

  /**
   * {@inheritdoc}
   */
  public function getActive() {
    return $this->get('active')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeverity() {
    return $this->get('severity')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->get('label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->get('message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartTime() {
    if ($start_time = $this->get('scheduling')->value) {
      $date = new DrupalDateTime($start_time, 'UTC');
      return $date->format('Y-m-d H:i:s', ['timezone' => date_default_timezone_get()]);
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getEndTime() {
    if ($end_time = $this->get('scheduling')->end_value) {
      $date = new DrupalDateTime($end_time, 'UTC');
      return $date->format('Y-m-d H:i:s', ['timezone' => date_default_timezone_get()]);
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function isCurrentlyScheduled() {
    $now = new DrupalDateTime();

    if ($start_time = $this->get('scheduling')->value) {
      $date = new DrupalDateTime($start_time, 'UTC');

      if ($now < $date) {
        return FALSE;
      }
    }

    if ($end_time = $this->get('scheduling')->end_value) {
      $date = new DrupalDateTime($end_time, 'UTC');

      if ($now > $date) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Site Alert entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Site Alert entity.'))
      ->setReadOnly(TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setRequired(TRUE);

    $fields['active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('If checked, site alert is active.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 1,
      ]);

    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Severity'))
      ->setSetting('allowed_values', self::SEVERITY_OPTIONS)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setRequired(TRUE);

    $fields['message'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Message'))
      ->setDescription(t('This is the text of the alert that will be shown'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 3,
      ])
      ->setRequired(TRUE);

    $fields['scheduling'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Scheduling'))
      ->setDisplayOptions('form', [
        'type' => 'daterange_default',
        'weight' => 4,
      ]);

    return $fields;
  }

}
