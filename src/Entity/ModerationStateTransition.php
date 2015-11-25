<?php

/**
 * @file
 * Contains \Drupal\moderation_state\Entity\ModerationStateTransition.
 */

namespace Drupal\moderation_state\Entity;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\moderation_state\ModerationStateTransitionInterface;

/**
 * Defines the Moderation state transition entity.
 *
 * @ConfigEntityType(
 *   id = "moderation_state_transition",
 *   label = @Translation("Moderation state transition"),
 *   handlers = {
 *     "list_builder" = "Drupal\moderation_state\ModerationStateTransitionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\moderation_state\Form\ModerationStateTransitionForm",
 *       "edit" = "Drupal\moderation_state\Form\ModerationStateTransitionForm",
 *       "delete" = "Drupal\moderation_state\Form\ModerationStateTransitionDeleteForm"
 *     }
 *   },
 *   config_prefix = "moderation_state_transition",
 *   admin_permission = "administer moderation state transitions",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/moderation-state/transitions/{moderation_state_transition}",
 *     "edit-form" = "/admin/structure/moderation-state/transitions/{moderation_state_transition}/edit",
 *     "delete-form" = "/admin/structure/moderation-state/transitions/{moderation_state_transition}/delete",
 *     "collection" = "/admin/structure/moderation-state/transitions"
 *   }
 * )
 */
class ModerationStateTransition extends ConfigEntityBase implements ModerationStateTransitionInterface {
  /**
   * The Moderation state transition ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Moderation state transition label.
   *
   * @var string
   */
  protected $label;

  /**
   * ID of from state.
   *
   * @var string
   */
  protected $stateFrom;

  /**
   * ID of to state.
   *
   * @var
   */
  protected $stateTo;

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    // @todo Can we use DI here?
    $prefix = \Drupal::entityTypeManager()->getDefinition('moderation_state')->getConfigPrefix() . '.';
    if ($this->stateFrom) {
      $this->addDependency('config', $prefix . $this->stateFrom);
    }
    if ($this->stateTo) {
      $this->addDependency('config', $prefix . $this->stateTo);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFromState() {
    return $this->stateFrom;
  }

  /**
   * {@inheritdoc}
   */
  public function getToState() {
    return $this->stateTo;
  }

}
