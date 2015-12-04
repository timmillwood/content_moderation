<?php

/**
 * Contains Drupal\moderation_state\EntityTypeInfo.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\moderation_state\Form\EntityWorkflowForm;
use Drupal\moderation_state\Routing\WorkflowRouteProvider;

/**
 * Service class for manipulating entity type information.
 */
class EntityTypeInfo {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypes;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new EntityTypeInfo service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_types
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_types, AccountInterface $current_user) {
    $this->entityTypes = $entity_types;
    $this->currentUser = $current_user;
  }

  /**
   * Adds Workflow configuration to approprite entity types.
   *
   * This is an alter hook bridge.
   *
   * @see hook_entity_type_alter().
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
   * @return \Generator
   *   A generator producing only the config entities we want to modify.
   */
  protected function revisionableEntityTypes(array $entity_types) {
    foreach ($entity_types as $type_name => $type) {
      if ($type instanceof ConfigEntityTypeInterface) {
        if ($type->get('bundle_of')) {
          if ($entity_types[$type->get('bundle_of')]->isRevisionable()) {
            yield $type_name => $type;
          }
        }
      }
    }
  }

  /**
   * Adds an operation on bundles that should have a Workflow form.
   *
   * @see hook_entity_operation().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    $type = $entity->getEntityType();

    if ($this->entityOperationApplies($entity)) {
      $operations['manage-workflow'] = [
        'title' => t('Manage workflow'),
        'weight' => 27,
        'url' => Url::fromRoute("entity.{$type->id()}.workflow", [$entity->getEntityTypeId() => $entity->id()]),
      ];
    }

    return $operations;
  }

  protected function entityOperationApplies(EntityInterface $entity) {
    $type = $entity->getEntityType();
    if ($type instanceof ConfigEntityTypeInterface) {
      if ($bundle_of = $type->get('bundle_of')) {
        if ($this->entityTypes->getDefinition($bundle_of)->isRevisionable()) {
          if ($this->currentUser->hasPermission('administer moderation state')) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

}
