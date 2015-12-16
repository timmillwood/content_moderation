<?php
/**
 * @file
 * Contains Drupal\moderation_state\NodeCustomizations.
 */

namespace Drupal\moderation_state;


use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Customizations for node entities.
 */
class NodeCustomizations extends GenericCustomizations {

  /**
   * {@inheritdoc}
   */
  function getEntityTypeId() {
    return 'node';
  }

  /**
   * {@inheritdoc}
   */
  function getEntityBundleClass() {
    return NodeType::class;
  }

  /**
   * {@inheritdoc}
   */
  public function onPresave(ContentEntityInterface $entity, $published_state) {
    parent::onPresave($entity, $published_state);

    // Only nodes have a concept of published.
    /** @var $entity Node */
    $entity->setPublished($published_state);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityModerationFormSubmit(ConfigEntityInterface $bundle) {
    /** @var NodeType $bundle */
    $bundle->setNewRevision(TRUE);
    $bundle->save();
  }

}
