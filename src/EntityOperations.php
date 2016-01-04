<?php

/**
 * @file
 * Contains \Drupal\workbench_moderation\EntityOperations.
 */

namespace Drupal\workbench_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a class for reacting to entity events.
 */
class EntityOperations {

  /**
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\workbench_moderation\ModerationInformationInterface $moderation_info
   *   Moderation information service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(ModerationInformationInterface $moderation_info, EntityTypeManagerInterface $entity_type_manager) {
    $this->moderationInfo = $moderation_info;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Acts on an entity and set published status based on the moderation state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  public function entityPresave(EntityInterface $entity) {
    if ($entity instanceof ContentEntityInterface && $this->moderationInfo->isModeratableEntity($entity)) {
      // @todo write a test for this.
      if ($entity->moderation_state->entity) {
        $update_live_revision = $entity->moderation_state->entity->isLiveRevisionState();
        $published_state = $entity->moderation_state->entity->isPublishedState();
        $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'moderation')->onPresave($entity, $update_live_revision, $published_state);
      }
    }
  }
}
