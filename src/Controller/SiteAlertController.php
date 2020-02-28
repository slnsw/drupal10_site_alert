<?php

namespace Drupal\site_alert\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\site_alert\GetAlertsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Implements SiteAlertController class.
 */
class SiteAlertController extends ControllerBase {

  /**
   * The service that retrieves site alerts.
   *
   * @var \Drupal\site_alert\GetAlertsInterface
   */
  protected $getAlerts;

  /**
   * Constructs a new SiteAlertController.
   *
   * @param \Drupal\site_alert\GetAlertsInterface $getAlerts
   *   The service that retrieves site alerts.
   */
  public function __construct(GetAlertsInterface $getAlerts) {
    $this->getAlerts = $getAlerts;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('site_alert.get_alerts')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdatedAlerts() {
    \Drupal::service('page_cache_kill_switch')->trigger();

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
        ],
      ];
    }

    $html = \Drupal::service('renderer')->renderRoot($build);
    $response = new Response();
    $response->setContent($html);

    return $response;
  }

}
