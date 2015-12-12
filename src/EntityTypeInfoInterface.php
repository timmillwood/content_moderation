<?php

/**
 * Contains Drupal\moderation_state\EntityTypeInfoInterface.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for entity_type service.
 */
interface EntityTypeInfoInterface {

  /**
   * Adds Moderation configuration to appropriate entity types.
   *
   * This is an alter hook bridge.
   *
   * @param EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter().
   */
  public function entityTypeAlter(array &$entity_types);

  /**
   * Adds an operation on bundles that should have a Moderation form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation().
   */
  public function entityOperation(EntityInterface $entity);

  /**
   * Force moderatable bundles to have a moderation_state field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $fields
   *   The array of bundle field definitions.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $bundle
   *   The bundle.
   *
   * @see hook_entity_bundle_field_info_alter();
   */
  public function entityBundleFieldInfoAlter(&$fields, EntityTypeInterface $entity_type, $bundle);

  /**
   * Alters bundle forms to enforce revision handling.
   *
   * @see hook_form_alter()
   */
  public function bundleFormAlter(array &$form, FormStateInterface $form_state, $form_id);

}
