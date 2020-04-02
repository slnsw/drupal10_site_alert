<?php

namespace Drupal\site_alert\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;
use Drupal\site_alert\GetAlertsInterface;

/**
 * A cache context that varies by the currently active site alerts.
 *
 * Cache context ID: 'active_site_alerts'.
 */
class ActiveSiteAlertsCacheContext implements CacheContextInterface {

  /**
   * A cache context key indicating that there are no active alerts right now.
   */
  const NO_ACTIVE_ALERTS = 'no-active-alerts';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The service that handles retrieval of site alerts.
   *
   * @var \Drupal\site_alert\GetAlertsInterface
   */
  protected $getAlerts;

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The hash that represents the currently active site alerts.
   *
   * @var string
   */
  protected $hash;

  /**
   * Constructs an ActiveSiteAlertsCacheContext.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\site_alert\GetAlertsInterface $getAlerts
   *   The service that handles retrieval of site alerts.
   * @param \Drupal\Core\PrivateKey $privateKey
   *   The private key service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, GetAlertsInterface $getAlerts, PrivateKey $privateKey) {
    $this->entityTypeManager = $entityTypeManager;
    $this->getAlerts = $getAlerts;
    $this->privateKey = $privateKey;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Active site alerts');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // Due to cache metadata bubbling this can be called often. Only compute the
    // hash once.
    if (empty($this->hash)) {
      // Retrieve the IDs of the currently active site alerts.
      $ids = $this->getAlerts->getActiveAlertIds();

      // Return a human readable string if there are no active alerts.
      if (empty($ids)) {
        return self::NO_ACTIVE_ALERTS;
      }

      // Sort the IDs, so that the same key can be generated even if the IDs
      // would be returned in a different order.
      sort($ids);

      // Generate a hash that uniquely identifies the currently active alerts.
      $this->hash = hash('sha256', $this->privateKey->get() . Settings::getHashSalt() . serialize($ids));
    }

    return $this->hash;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    // Return a cache max age that matches the time period until the next alert
    // appears. This allows caching implementations that do not leverage cache
    // contexts to correctly invalidate their caches.
    /** @var \Drupal\site_alert\SiteAlertStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('site_alert');
    return (new CacheableMetadata())->setCacheMaxAge($storage->getCacheMaxAge());
  }

}
