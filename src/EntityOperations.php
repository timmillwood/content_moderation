<?php

/**
 * @file
 * Contains \Drupal\moderation_state\EntityOperations.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a class for reacting to entity events.
 */
class EntityOperations {

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
  public function __construct(ModerationInformation $moderation_info) {
    $this->moderationInfo = $moderation_info;
  }

  /**
   * Acts on an entity and set the published status based on the moderation state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  public function entityPresave(EntityInterface $entity) {

    if ($entity instanceof ContentEntityInterface && $this->moderationInfo->isModeratableEntity($entity)) {
      // @todo write a test for this.
      if ($entity->moderation_state->entity) {
        // Regardless of if it's a forward revision or not, the publication
        // state is determined by the moderation state.
        $published_state = $entity->moderation_state->entity->isPublishedState();
        $entity->isDefaultRevision($published_state);
        $entity->setPublished($published_state);
      }
    }
  }
}
