<?php

/**
 * @file
 * Contains \Drupal\moderation_state\EntityOperations.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a class for reacting to entity events.
 */
class EntityOperations {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\moderation_state\ModerationInformation
   */
  protected $moderationInfo;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModerationInformation $moderation_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInfo = $moderation_info;
  }

  /**
   * Acts on an entity and set the published status based on the moderation state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  public function entityPresave(EntityInterface $entity) {
    if (!$this->moderationInfo->isModeratableEntity($entity)) {
      return;
    }

    // @todo write a test for this.
    if ($entity->moderation_state->entity) {
      $original = !empty($entity->original) ? $entity->original : NULL;
      if ($original && $original->moderation_state->target_id !== $entity->moderation_state->target_id) {
        // We're moving to a new state, so force a new revision.
        $entity->setNewRevision(TRUE);
        if ((!$original->moderation_state->entity && $original->isPublished()) || ($original->moderation_state->entity->isPublishedState() && !$entity->moderation_state->entity->isPublishedState())) {
          // Mark this as a new forward revision.
          $entity->isDefaultRevision(FALSE);
        }
      }

      $entity->setPublished($entity->moderation_state->entity->isPublishedState());
    }
  }
}
