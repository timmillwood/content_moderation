<?php

/**
 * @file
 * Contains \Drupal\moderation_state\ModerationStateInterface.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Moderation state entities.
 */
interface ModerationStateInterface extends ConfigEntityInterface {

  /**
   * Determines if this state represents a published node.
   *
   * @return bool
   *   TRUE if this state deems the node published.
   */
  public function isPublishedState();

}
