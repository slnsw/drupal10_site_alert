<?php

namespace Drupal\site_alert\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\site_alert\GetAlertsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements SiteAlertController class.
 */
class SiteAlertController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The service that retrieves site alerts.
   *
   * @var \Drupal\site_alert\GetAlertsInterface
   */
  protected $getAlerts;

  /**
   * Constructs a new SiteAlertController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\site_alert\GetAlertsInterface $getAlerts
   *   The service that retrieves site alerts.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RendererInterface $renderer, GetAlertsInterface $getAlerts) {
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
    $this->getAlerts = $getAlerts;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('site_alert.get_alerts')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdatedAlerts() {
    $cache_metadata = new CacheableMetadata();
    // Add the list cache tags so that the response will be invalidated when
    // the alerts change.
    $cache_metadata->addCacheTags($this->entityTypeManager->getDefinition('site_alert')->getListCacheTags());
    // Add the 'rendered' cache tag as this response is not processed by
    // \Drupal\Core\Render\MainContent\HtmlRenderer::renderResponse().
    $cache_metadata->addCacheTags(['rendered']);
    // Set the max age to the first scheduled change in visible alerts.
    $cache_metadata->setCacheMaxAge($this->entityTypeManager->getStorage('site_alert')->getCacheMaxAge());
    // Apply the cache context that varies by the currently active alerts.
    $cache_metadata->setCacheContexts(['active_site_alerts']);

    $build = [];
    foreach ($this->getAlerts->getActiveAlerts() as $alert) {
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
      $cache_metadata->addCacheableDependency($alert);
    }
    $cache_metadata->applyTo($build);

    $response = new HtmlResponse();
    $response
      ->setContent($this->renderer->renderRoot($build))
      ->addCacheableDependency($cache_metadata);

    return $response;
  }

}
