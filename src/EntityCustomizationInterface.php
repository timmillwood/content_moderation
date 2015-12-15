<?php
/**
 * @file
 * Contains Drupal\moderation_state\EntityCustomizationInterface.
 */

namespace Drupal\moderation_state;


use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines operations that need to vary by entity type.
 *
 * The existence of this interface and implementations of it are a symptom of
 * design flaws in core's Entity API. Ideally, over time it will get removed
 * as core becomes more standardized.
 */
interface EntityCustomizationInterface {

  /**
   * Returns the machine name of the entity type this customization set is for.
   *
   * @return string
   */
  public function getEntityTypeId();



  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to modify.
   * @param bool $published_state
   *   Whether the state being transitioned to is a published state or not.
   */
  public function onPresave(ContentEntityInterface $entity, $published_state);

}
