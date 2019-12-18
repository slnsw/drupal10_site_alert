<?php

namespace Drupal\site_alert\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\site_alert\Entity\SiteAlert;
use Drupal\Core\Cache\Cache;

/**
 * Implements SiteAlertBlock class.
 *
 * @Block(
 *   id = "site_alert_block",
 *   admin_label = @Translation("Site Alert"),
 * )
 */
class SiteAlertBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    foreach (SiteAlert::loadMultiple() as $alert) {
      if ($alert->getActive() && $alert->isCurrentlyScheduled()) {
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
