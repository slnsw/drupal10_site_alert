<?php

namespace Drupal\site_alert\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
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
   * The service that retrieves site alerts.
   *
   * @var \Drupal\site_alert\GetAlertsInterface
   */
  protected $getAlerts;

  /**
   * Constructs a new SiteAlertBlock.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\site_alert\GetAlertsInterface $getAlerts
   *   The service that retrieves site alerts.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GetAlertsInterface $getAlerts) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->getAlerts = $getAlerts;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('site_alert.get_alerts')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $alerts = $this->getAlerts->getActiveAlerts();
    foreach ($alerts as $alert) {
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
        '#attached' => [
          'library' => ['site_alert/drupal.site_alert'],
          'drupalSettings' => [
            'siteAlert' => [
              'timeout' => SITE_ALERT_TIMEOUT_DEFAULT,
            ],
          ],
        ],
      ];
    }

    if (!empty($build)) {
      $build['#prefix'] = '<div class="site-alert">';
      $build['#suffix'] = '</div>';
    }

    $build['#cache']['max-age'] = 0;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['site_alert_block']);
  }

}
