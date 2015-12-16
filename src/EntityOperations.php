<?php

/**
 * @file
 * Contains \Drupal\moderation_state\EntityOperations.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class for reacting to entity events.
 */
class EntityOperations {

  /**
   * @var \Drupal\moderation_state\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * @var \Drupal\moderation_state\EntityCustomizationInterface
   */
  protected $customizations;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\moderation_state\ModerationInformationInterface $moderation_info
   *   Entity type manager service.
   * @param \Drupal\moderation_state\EntityCustomizationInterface $customizations
   *   Entity customizations service.
   */
  public function __construct(ModerationInformationInterface $moderation_info, EntityCustomizationInterface $customizations) {
    $this->moderationInfo = $moderation_info;
    $this->customizations = $customizations;
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
        $published_state = $entity->moderation_state->entity->isPublishedState();
        $this->customizations->onPresave($entity, $published_state);
      }
    }
  }
}
