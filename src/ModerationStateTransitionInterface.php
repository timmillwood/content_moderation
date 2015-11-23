<?php

/**
 * @file
 * Contains \Drupal\moderation_state\ModerationStateTransitionInterface.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Moderation state transition entities.
 * @todo phpdoc
 */
interface ModerationStateTransitionInterface extends ConfigEntityInterface {

  public function getFromState();

  public function getToState();

}
