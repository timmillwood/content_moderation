<?php

namespace Drupal\content_moderation\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the content moderation transition event.
 *
 * @see \Drupal\content_moderation\Event\ContentModerationEvents
 */
class ContentModerationTransitionEvent extends Event {

  /**
   * The entity which was changed.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The state before the transition.
   *
   * @var string
   */
  protected $stateBefore;

  /**
   * The state after the transition.
   *
   * @var string
   */
  protected $stateAfter;

  /**
   * Creates a new ContentModerationTransitionEvent instance.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity which was changed.
   * @param string $state_before
   *   The state before the transition.
   * @param string $state_after
   *   The state after the transition.
   */
  public function __construct(ContentEntityInterface $entity, $state_before, $state_after) {
    $this->entity = $entity;
    $this->stateBefore = $state_before;
    $this->stateAfter = $state_after;
  }

  /**
   * Returns the changed entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The content entity being moderated.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Returns state before the transition.
   *
   * @return string
   *   The state before the transition.
   */
  public function getStateBefore() {
    return $this->stateBefore;
  }

  /**
   * Returns state after the transition.
   *
   * @return string
   *   The state after the transition.
   */
  public function getStateAfter() {
    return $this->stateAfter;
  }

}
