<?php
/**
 * @file
 * Contains Drupal\moderation_state\NodeCustomizations.
 */

namespace Drupal\moderation_state;


use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\Entity\Node;

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
  public function onPresave(ContentEntityInterface $entity, $published_state) {
    parent::onPresave($entity, $published_state);

    // Only nodes have a concept of published.
    /** @var $entity Node */
    $entity->setPublished($published_state);
  }

}
