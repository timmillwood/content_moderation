<?php

/**
 * Contains Drupal\moderation_state\EntityTypeInfo.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\moderation_state\Form\EntityWorkflowForm;
use Drupal\moderation_state\Routing\WorkflowRouteProvider;

/**
 * Service class for manipulating entity type information.
 */
class EntityTypeInfo {

  /**
   * Adds Workflow configuration to approprite entity types.
   *
   * This is an alter hook bridge.
   *
   * @param EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   */
  public function entityTypeAlter(array &$entity_types) {
    foreach ($this->revisionableEntityTypes($entity_types) as $type_name => $type) {
      $entity_types[$type_name] = $this->addWorkflow($type);
    }
  }

  /**
   * Modifies an entity type to include workflow configuration support.
   *
   * That "configuration support" includes a configuration form, a hypermedia
   * link, and a route provider to tie it all together.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $type
   *   The config entity definition to modify.
   * @return \Drupal\Core\Config\Entity\ConfigEntityTypeInterface
   *   The modified config entity definition.
   */
  protected function addWorkflow(ConfigEntityTypeInterface $type) {
    if ($type->hasLinkTemplate('edit-form')) {
      $type->setLinkTemplate('workflow-form', $type->getLinkTemplate('edit-form') . '/workflow');
    }

    $type->setFormClass('workflow', EntityWorkflowForm::class);

    // @todo Core forgot to add a direct way to manipulate route_provider, so
    // we have to do it the sloppy way for now.
    $providers = $type->getHandlerClass('route_provider') ?: [];
    $providers['workflow'] = WorkflowRouteProvider::class;
    $type->setHandlerClass('route_provider', $providers);

    return $type;
  }

  /**
   * Filters the provided entity types to just the ones we are interested in.
   *
   * @param EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   * @return \CallbackFilterIterator
   *   An iterator producing only the config entities we want to modify.
   */
  protected function revisionableEntityTypes($entity_types) {
    $filtered_list = new \CallbackFilterIterator(
      new \CallbackFilterIterator(
        new \ArrayIterator($entity_types),
        [$this, 'typeFilter']),
      [$this, 'bundleFilter']);

    // This one needs to be inlined since it calls back to the $entity_types variable.
    $filtered_list = new \CallbackFilterIterator($filtered_list, function(EntityTypeInterface $current, $key) use ($entity_types) {
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
  public function typeFilter(EntityTypeInterface $type) {
    return $type instanceof ConfigEntityTypeInterface;
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
  public function bundleFilter(ConfigEntityTypeInterface $type) {
    return (bool)$type->get('bundle_of');
  }
}
