<?php
/**
 * @file
 * Contains Drupal\moderation_state\BlockContentCustomizations.
 */

namespace Drupal\moderation_state;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Config\Entity\ConfigEntityInterface;


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

  /**
   * {@inheritdoc}
   */
  function getEntityBundleClass() {
    return BlockContentType::class;
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityModerationFormSubmit(ConfigEntityInterface $bundle) {
    /** @var BlockContentType $bundle */
    $bundle->set('revision', TRUE);
    $bundle->save();
  }

}
