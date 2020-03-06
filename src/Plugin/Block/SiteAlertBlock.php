<?php

namespace Drupal\site_alert\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\site_alert\GetAlertsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements SiteAlertBlock class.
 *
 * @Block(
 *   id = "site_alert_block",
 *   admin_label = @Translation("Site Alert"),
 * )
 */
class SiteAlertBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The service that retrieves site alerts.
   *
   * @var \Drupal\site_alert\GetAlertsInterface
   */
  protected $getAlerts;

  /**
   * Constructs a new SiteAlertBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\site_alert\GetAlertsInterface $getAlerts
   *   The service that retrieves site alerts.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GetAlertsInterface $getAlerts, EntityTypeManagerInterface $entity_type_manager) {
    if (empty($entity_type_manager)) {
      @trigger_error('Omitting the entity type manager when instantiating ' . __METHOD__ . ' is deprecated in site_alert:8.1.1 and will throw an error in site_alert:9.0.0. Make sure to pass the entity type manager instead. See https://www.drupal.org/project/site_alert/issues/3118227', E_USER_DEPRECATED);
      $entity_type_manager = \Drupal::entityTypeManager();
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->getAlerts = $getAlerts;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('site_alert.get_alerts'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $metadata = new CacheableMetadata();

    $alerts = $this->getAlerts->getActiveAlerts();
    foreach ($alerts as $alert) {
      $metadata->addCacheableDependency($alert);
      $build[] = [
        '#theme' => 'site_alert',
        '#alert' => [
          'severity' => $alert->getSeverity(),
          'label' => $alert->getLabel(),
          'message' => [
            '#type' => 'markup',
            '#markup' => $alert->getMessage(),
          ],
        ],
      ];
    }

    // Attach the JS code to refresh the site alerts when a timeout is
    // configured.
    $timeout = $this->getConfiguration()['timeout'];
    if ($timeout > 0) {
      $build['#attached'] = [
        'library' => ['site_alert/drupal.site_alert'],
        'drupalSettings' => [
          'siteAlert' => [
            'timeout' => $timeout,
          ],
        ],
      ];
    }

    if (!empty($build)) {
      $build['#prefix'] = '<div class="site-alert">';
      $build['#suffix'] = '</div>';
    }

    $metadata->applyTo($build);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'timeout' => SITE_ALERT_TIMEOUT_DEFAULT,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $timeout = $this->getConfiguration()['timeout'];
    $form['timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Timeout'),
      '#description' => $this->t('After how many seconds the alerts should be refreshed. Set to 0 if you do not wish to poll the server for updates.'),
      '#default_value' => $timeout,
      '#field_suffix' => ' ' . $this->t('seconds'),
      '#required' => TRUE,
      '#min' => 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->setConfigurationValue('timeout', $form_state->getValue('timeout'));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // The block should be invalidated whenever any site alert changes.
    $list_cache_tags = $this->entityTypeManager->getDefinition('site_alert')->getListCacheTags();
    return Cache::mergeTags(parent::getCacheTags(), $list_cache_tags);
  }

}
