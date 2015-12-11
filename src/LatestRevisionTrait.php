<?php

/**
 * @file
 * Contains \Drupal\moderation_state\LatestRevisionTrait.
 */

namespace Drupal\moderation_state;

trait LatestRevisionTrait {

  /**
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected function getEntityTypeManager() {
    if (isset($this->entityTypeManager)) {
      return $this->entityTypeManager;
    }
    if (isset($this->entityManager)) {
      return $this->entityManager;
    }

    return \Drupal::service('entity_type.manager');
  }

  /**
   * Loads the latest revision of a specific entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The latest entity revision or NULL, if the entity type / entity doesn't
   *   exist.
   */
  protected function getLatestRevision($entity_type_id, $entity_id) {
    if ($storage = $this->getEntityTypeManager()->getStorage($entity_type_id)) {
      $revision_ids = $storage->getQuery()
        ->allRevisions()
        ->condition($this->getEntityTypeManager()->getDefinition($entity_type_id)->getKey('id'), $entity_id)
        ->sort($this->getEntityTypeManager()->getDefinition($entity_type_id)->getKey('revision'), 'DESC')
        ->pager(1)
        ->execute();
      if ($revision_ids) {
        $revision_id = array_keys($revision_ids)[0];
        return $storage->loadRevision($revision_id);
      }
    }
  }

}
