<?php
/**
 * @file
 * Contains Drupal\moderation_state\BlockContentCustomizations.
 */

namespace Drupal\moderation_state;

/**
 * Customizations for block content entities.
 */
class BlockContentCustomizations extends GenericCustomizations {

  /**
   * {@inheritdoc}
   */
  function getEntityTypeId() {
    return 'block_content';
  }

}
