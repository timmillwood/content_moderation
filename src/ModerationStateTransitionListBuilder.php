<?php

/**
 * @file
 * Contains \Drupal\workbench_moderation\ModerationStateTransitionListBuilder.
 */

namespace Drupal\workbench_moderation;

use Drupal\Core\Config\Entity\DraggableListBuilder;

/**
 * Provides a listing of Moderation state transition entities.
 */
class ModerationStateTransitionListBuilder extends DraggableListBuilder {

  public function getFormId() {
    return 'workbench_moderation_transition_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Moderation state transition');
    $header['id'] = $this->t('Machine name');
    $header['from'] = $this->t('From state');
    $header['to'] = $this->t('To state');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(ModerationStateTransitionInterface $entity) {
    $row['label'] = $entity->label();
    $row['id']['#markup'] = $entity->id();
    $row['from']['#markup'] = $entity->getFromState();
    $row['to']['#markup'] = $entity->getToState();

    return $row + parent::buildRow($entity);
  }

}
