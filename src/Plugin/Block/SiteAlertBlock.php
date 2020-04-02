<?php

namespace Drupal\site_alert\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * The default timeout for refreshing site alerts.
   */
  const TIMEOUT_DEFAULT = 300;

  /**
   * The service that retrieves site alerts.
   *
   * @var \Drupal\site_alert\GetAlertsInterface
   */
  protected $getAlerts;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GetAlertsInterface $getAlerts, EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler) {
    if (empty($entity_type_manager)) {
      @trigger_error('Omitting the entity type manager when instantiating ' . __METHOD__ . ' is deprecated in site_alert:8.x-1.1 and will throw an error in site_alert:9.x-1.0. Make sure to pass the entity type manager instead. See https://www.drupal.org/project/site_alert/issues/3118227', E_USER_DEPRECATED);
      $entityTypeManager = \Drupal::entityTypeManager();
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->getAlerts = $getAlerts;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
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
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $metadata = new CacheableMetadata();

    // Due to a bug in Drupal core the cache max age is ignored when the core
    // page_cache module is enabled. This bug prevents any cached pages from
    // being invalidated in time for a scheduled alert to be displayed. We can
    // work around this by injecting the JS code in the page and letting it
    // retrieve the first alert on page load. This means that even if the page
    // is cached it will poll the server for any available alerts and display
    // them. The drawback is that this will always cause a second request to be
    // sent which increases server load, so only do this if any alerts are
    // actually scheduled.
    // @todo Remove this workaround when the core bug is fixed.
    // @see https://www.drupal.org/project/site_alert/issues/3121988
    /** @var \Drupal\site_alert\SiteAlertStorage $storage */
    $storage = $this->entityTypeManager->getStorage('site_alert');
    $scheduled_alerts_present = $storage->getCacheMaxAge() !== CacheBackendInterface::CACHE_PERMANENT;
    $workaround_needed = $scheduled_alerts_present && $this->moduleHandler->moduleExists('page_cache');

    $alerts = $this->getAlerts->getActiveAlerts();
    foreach ($alerts as $alert) {
      $metadata->addCacheableDependency($alert);
      // Avoid displaying the alert if we need to work around the core bug. This
      // prevents site alerts from briefly flashing in the page in case the bug
      // prevents a cached page that contains an alert from being invalidated.
      if ($workaround_needed) {
        continue;
      }
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
    if ($timeout > 0 || $workaround_needed) {
      $build['#attached'] = [
        'library' => ['site_alert/drupal.site_alert'],
        'drupalSettings' => [
          'siteAlert' => [
            'timeout' => $timeout,
            'workaround_needed' => $workaround_needed,
          ],
        ],
      ];
    }

    if (!empty($build)) {
      $build['#prefix'] = '<div class="site-alert" aria-live="polite">';
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
      'timeout' => self::TIMEOUT_DEFAULT,
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

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['active_site_alerts']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    /** @var \Drupal\site_alert\SiteAlertStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('site_alert');
    return $storage->getCacheMaxAge();
  }

}
