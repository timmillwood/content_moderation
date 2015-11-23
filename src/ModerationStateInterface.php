<?php

/**
 * @file
 * Contains \Drupal\moderation_state\ModerationStateInterface.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Moderation state entities.
 * @todo phpdoc
 */
interface ModerationStateInterface extends ConfigEntityInterface {

  public function isPublished();

}
