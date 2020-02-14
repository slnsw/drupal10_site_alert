<?php

namespace Drupal\site_alert\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements SiteAlertForm class.
 */
class SiteAlertForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    $form_state->setRedirect('entity.site_alert.collection');

    \Drupal::service('cache_tags.invalidator')->invalidateTags(['site_alert_block']);
  }

}
