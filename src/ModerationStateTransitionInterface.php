<?php

/**
 * @file
 * Contains \Drupal\moderation_state\ModerationStateTransitionInterface.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Moderation state transition entities.
 */
interface ModerationStateTransitionInterface extends ConfigEntityInterface {

  /**
   * Gets the from state for the given transition.
   *
   * @return string
   *   The moderation state ID for the from state.
   */
  public function getFromState();

  /**
   * Gets the to state for the given transition.
   *
   * @return string
   *   The moderation state ID for the to state.
   */
  public function getToState();

}
