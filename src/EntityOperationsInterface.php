<?php

/**
 * @file
 * Contains \Drupal\moderation_state\EntityOperationsInterface.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for entity_operations service.
 */
interface EntityOperationsInterface {

  /**
   * Acts on an entity and set published status based on the moderation state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  public function entityPresave(EntityInterface $entity);

}
