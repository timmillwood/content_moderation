<?php

/**
 * @file
 * Contains \Drupal\workbench_moderation\StateTransitionValidation.
 */

namespace Drupal\workbench_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_moderation\Entity\ModerationState;
use Drupal\workbench_moderation\Entity\ModerationStateTransition;

/**
 * Validates whether a certain state transition is allowed.
 */
class StateTransitionValidation {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $transitionStorage;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Stores the possible state transitions.
   *
   * @var array
   */
  protected $possibleTransitions = [];

  /**
   * Creates a new StateTransitionValidation instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $transitionStorage
   *   The transition storage.
   */
  public function __construct(EntityStorageInterface $transitionStorage, EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory) {
    $this->transitionStorage = $transitionStorage;
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $query_factory;
  }

  public static function create(EntityTypeManagerInterface $entity_type_manager) {
    return new static($entity_type_manager->getStorage('moderation_state_transition'));
  }

  protected function calculatePossibleTransitions() {
    $transitions = $this->transitionStorage->loadMultiple();

    $possible_transitions = [];
    /** @var \Drupal\workbench_moderation\ModerationStateTransitionInterface $transition */
    foreach ($transitions as $transition) {
      $possible_transitions[$transition->getFromState()][] = $transition->getToState();
    }
    return $possible_transitions;
  }

  protected function getPossibleTransitions() {
    if (empty($this->possibleTransitions)) {
      $this->possibleTransitions = $this->calculatePossibleTransitions();
    }
    return $this->possibleTransitions;
  }

  /**
   * Gets a list of states a user may transition an entity to.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be transitioned.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The account that wants to perform a transition.
   *
   * @return ModerationState[]
   *   Returns an array of States to which the specified user may transition the
   *   entity.
   */
  public function getValidTransitionTargets(ContentEntityInterface $entity, AccountInterface $user) {
    $bundle = $this->loadBundleEntity($entity->getEntityTypeId(), $entity->bundle());

    $states_for_bundle = $bundle->getThirdPartySetting('workbench_moderation', 'allowed_moderation_states', []);

    /** @var ModerationState $state */
    $state = $entity->moderation_state->entity;
    $current_state_id = $state->id();

    $all_transitions = $this->getPossibleTransitions();
    $destinations = $all_transitions[$current_state_id];

    $destinations = array_intersect($states_for_bundle, $destinations);

    array_filter($destinations, function($state_name) use ($user) {

    });

  }

  /**
   * Loads a specific bundle entity.
   *
   * @param string $bundle_entity_type_id
   *   The bundle entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface|null
   */
  protected function loadBundleEntity($bundle_entity_type_id, $bundle_id) {
    if ($bundle_entity_type_id) {
      return $this->entityTypeManager->getStorage($bundle_entity_type_id)->load($bundle_id);
    }
  }

  public function isTransitionAllowedForUser($from, $to, AccountInterface $user) {
    return $this->isTransitionAllowed($from, $to) && $this->userMayTransition($from, $to, $user);
  }

  /**
   *
   *
   * @param $from
   * @param $to
   * @param \Drupal\Core\Session\AccountInterface $user
   *
   * @return bool
   *   TRUE if the given user may transition between those two states.
   */
  protected function userMayTransition($from, $to, AccountInterface $user) {
    if ($transition = $this->getTransitionFromStates($from, $to)) {
      return $user->hasPermission('use ' . $transition->id() . ' transition');
    }
    return FALSE;
  }

  /**
   * Returns the transition object that transitions from one state to another.
   *
   * @param string $from
   *   The name of the "from" state.
   * @param string $to
   *   The name of the "to" state.
   *
   * @return ModerationStateTransition|null
   *   A transition object, or NULL if there is no such transition in the system.
   */
  protected function getTransitionFromStates($from, $to) {
    $from = $this->transitionStateQuery()
      ->condition('stateFrom', $from)
      ->condition('stateTo', $to)
      ->execute();

    $transitions = $this->transitionStorage()->loadMultiple($from);

    if ($transitions) {
      return current($transitions);
    }
    return NULL;

  }

  /**
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   A transition state query.
   */
  protected function transitionStateQuery() {
    return $this->queryFactory->get('moderation_state_transition', 'AND');
  }

  protected function transitionStorage() {
    return $this->entityTypeManager->getStorage('moderation_state_transition');
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
