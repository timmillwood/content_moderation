<?php

/**
 * @file
 * Contains \Drupal\moderation_state\StateTransitionValidation.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Validates whether a certain state transition is allowed.
 */
class StateTransitionValidation {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $transitionStorage;

  /**
   * Stores the possible state transitions.
   *
   * @var array|NULL
   */
  protected $possibleTransitions = NULL;

  /**
   * Creates a new StateTransitionValidation instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $transitionStorage
   *   The transition storage.
   */
  public function __construct(EntityStorageInterface $transitionStorage) {
    $this->transitionStorage = $transitionStorage;
  }

  public static function create(EntityTypeManagerInterface $entity_type_manager) {
    return new static($entity_type_manager->getStorage('moderation_state_transition'));
  }

  protected function calculatePossibleTransitions() {
    if (isset($this->possibleTransitions)) {
      return $this->possibleTransitions;
    }
    $transitions = $this->transitionStorage->loadMultiple();

    $this->transitionStorage = [];
    foreach ($transitions as $transition) {
      /** @var \Drupal\moderation_state\ModerationStateTransitionInterface $transition */
      $this->transitionStorage[$transition->getFromState()][] = $transition->getToState();
    }
    return $this->transitionStorage;
  }

  /**
   * Determines a transition allowed.
   *
   * @param string $from
   *   The from state.
   * @param string $to
   *   The to state.
   *
   * @return bool
   *   Is the transition allowed.
   */
  public function isTransitionAllowed($from, $to) {
    $allowed_transitions = $this->calculatePossibleTransitions();
    if (isset($allowed_transitions[$from])) {
      return in_array($to, $allowed_transitions[$from], TRUE);
    }
    return FALSE;
  }

}
