<?php

/**
 * @file
 * Contains \Drupal\moderation_state\NodeEventSubscriber.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Defines a class for reacting to node events.
 */
class NodeEventSubscriber {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new NodeEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Acts on a node and set the published status based on the moderation state.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node being saved.
   */
  public function nodePresave(NodeInterface $node) {
    /* @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $this->entityTypeManager->getStorage('node_type')->load($node->bundle());
    if (!$node_type->getThirdPartySetting('moderation_state', 'enabled', FALSE)) {
      // @todo write a test for this.
      return;
    }
    // @todo write a test for this.
    if ($node->moderation_state->entity) {
      $original = !empty($node->original) ? $node->original : NULL;
      if ($original && $original->moderation_state->target_id !== $node->moderation_state->target_id) {
        // We're moving to a new state, so force a new revision.
        $node->setNewRevision(TRUE);
        if ($original->moderation_state->entity->isPublished() && !$node->moderation_state->entity->isPublished()) {
          // Mark this as a new forward revision.
          $node->isDefaultRevision(FALSE);
        }
      }

      $node->setPublished($node->moderation_state->entity->isPublished());
    }
  }

}
