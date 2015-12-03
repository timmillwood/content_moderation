<?php

/**
 * Contains Drupal\moderation_state\EntityTypeInfo.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityType;

/**
 * Service class for manipulating entity type information.
 */
class EntityTypeInfo {

  /**
   * Adds Workflow configuration to approprite entity types.
   *
   * This is an alter hook bridge.
   *
   * @param EntityType[] $entity_types
   *   The master entity type list to alter.
   */
  public function entityTypeAlter(array &$entity_types) {
    foreach ($this->revisionableEntityTypes($entity_types) as $type_name => $type) {
      $entity_types[$type_name] = $this->addWorkflow($type, $type_name);
    }
  }

  protected function addWorkflow(ConfigEntityType $type, $type_name) {
    drupal_set_message($type_name);
    // @todo Add another form handler and link, and a route provider to generate routes to the new form.
    return $type;
  }

  /**
   * Filters the provided entity types to just the ones we are interested in.
   *
   * @param EntityType[] $entity_types
   *   The master entity type list to alter.
   * @return \CallbackFilterIterator
   *   An iterator producing only the
   */
  protected function revisionableEntityTypes($entity_types) {
    $filtered_list = new \CallbackFilterIterator(
      new \CallbackFilterIterator(
        new \ArrayIterator($entity_types),
        [$this, 'typeFilter']),
      [$this, 'bundleFilter']);

    // This one needs to be inlined since it calls back to the $entity_types variable.
    $filtered_list = new \CallbackFilterIterator($filtered_list, function(EntityType $current, $key) use ($entity_types) {
      /** @var ContentEntityType $content_entity */
      $content_entity = $entity_types[$current->get('bundle_of')];
      return $content_entity->isRevisionable();
    });

    return $filtered_list;
  }

  /**
   * Determines if the provided entity type is a config entity.
   *
   * This method is only public to make it work with \CallbackFilterIterator.
   *
   * @param \Drupal\Core\Entity\EntityType $type
   *   The entity type to check.
   * @return bool
   *   True of the entity type is a Config entity, False otherwise.
   */
  public function typeFilter(EntityType $type) {
    return $type instanceof ConfigEntityType;
  }

  /**
   * Determines if the provided entity type is a bundle.
   *
   * This method is only public to make it work with \CallbackFilterIterator.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityType $type
   * @return bool
   *   True if the entity type is a Bundle definition for a Content entity.
   */
  public function bundleFilter(ConfigEntityType $type) {
    return (bool)$type->get('bundle_of');
  }
}
