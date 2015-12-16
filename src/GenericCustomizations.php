<?php
/**
 * @file
 * Contains Drupal\moderation_state\GenericCustomizations.
 */

namespace Drupal\moderation_state;


use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Common customizations for most/all entities.
 *
 * This class is intended primarily as a base class.
 */
class GenericCustomizations implements EntityCustomizationInterface {

  /**
   * {@inheritdoc}
   */
  function getEntityTypeId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  function getEntityBundleClass() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function onPresave(ContentEntityInterface $entity, $published_state) {
    // This is *probably* not necessary if configuration is setup correctly,
    // but it can't hurt.
    $entity->setNewRevision(TRUE);

    // A newly-created revision is always the default revision, or else
    // it gets lost.
    $entity->isDefaultRevision($entity->isNew() || $published_state);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityModerationFormSubmit(ConfigEntityInterface $bundle) {
    return;
  }

}
