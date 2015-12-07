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
use Drupal\moderation_state\Form\EntityModerationForm;
use Drupal\moderation_state\Routing\ModerationRouteProvider;
use Drupal\node\NodeTypeInterface;
use Drupal\node\Entity\NodeType;

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
   * Adds Moderation configuration to appropriate entity types.
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
      $entity_types[$type_name] = $this->addModeration($type);
    }
  }

  /**
   * Modifies an entity type to include moderation configuration support.
   *
   * That "configuration support" includes a configuration form, a hypermedia
   * link, and a route provider to tie it all together.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $type
   *   The config entity definition to modify.
   * @return \Drupal\Core\Config\Entity\ConfigEntityTypeInterface
   *   The modified config entity definition.
   */
  protected function addModeration(ConfigEntityTypeInterface $type) {
    if ($type->hasLinkTemplate('edit-form')) {
      $type->setLinkTemplate('moderation-form', $type->getLinkTemplate('edit-form') . '/moderation');
    }

    $type->setFormClass('moderation', EntityModerationForm::class);

    // @todo Core forgot to add a direct way to manipulate route_provider, so
    // we have to do it the sloppy way for now.
    $providers = $type->getHandlerClass('route_provider') ?: [];
    $providers['moderation'] = ModerationRouteProvider::class;
    $type->setHandlerClass('route_provider', $providers);

    return $type;
  }

  /**
   * Filters the provided entity types to just the ones we are interested in.
   *
   * @param EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   * @return array
   *   An array of only the config entities we want to modify.
   */
  protected function revisionableEntityTypes(array $entity_types) {
    return array_filter($entity_types, function (EntityTypeInterface $type) use ($entity_types) {
      return ($type instanceof ConfigEntityTypeInterface) && $type->get('bundle_of') && $entity_types[$type->get('bundle_of')]->isRevisionable();
    });
  }

  /**
   * Adds an operation on bundles that should have a Moderation form.
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
      $operations['manage-moderation'] = [
        'title' => t('Manage moderation'),
        'weight' => 27,
        'url' => Url::fromRoute("entity.{$type->id()}.moderation", [$entity->getEntityTypeId() => $entity->id()]),
      ];
    }

    return $operations;
  }

  /**
   * Force moderatable bundles to have a moderation_state field.
   *
   * @see hook_entity_bundle_field_info_alter();
   *
   * @param array $fields
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param string $bundle
   */
  public function entityBundleFieldInfoAlter(&$fields, EntityTypeInterface $entity_type, $bundle) {
    if ($this->isModeratableBundle($entity_type, $bundle)) {
      $fields['moderation_state']->addConstraint('ModerationState', []);
    }

    return;
  }

  /**
   * Determines if an entity type/bundle is one that will be moderated.
   *
   * @todo Generalize this to not be Node-specific.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition to check.
   * @param string $bundle
   *   The bundle to check.
   * @return bool
   *   TRUE if this is a bundle we want to moderate, FALSE otherwise.
   */
  protected function isModeratableBundle(EntityTypeInterface $entity_type, $bundle) {
    if ($entity_type->id() === 'node' && !empty($fields['moderation_state'])) {
      /* @var NodeTypeInterface $node_type */
      $node_type = NodeType::load($bundle);
      if ($node_type->getThirdPartySetting('moderation_state', 'enabled', FALSE)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Determines if we should be adding Moderation operations to this entity type.
   *
   * This is the same check as exists in revisionableEntityTypes(), but that
   * one cannot use the entity manager due to recursion and this one doesn't
   * have the entity list otherwise so must use the entity manager.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @return bool
   *   TRUE if we want to add a Moderation operation to this entity, FALSE
   *   otherwise.
   */
  protected function entityOperationApplies(EntityInterface $entity) {
    $type = $entity->getEntityType();

    return
      $type instanceof ConfigEntityTypeInterface
      && $type->get('bundle_of')
      && $this->entityTypes->getDefinition($type->get('bundle_of'))->isRevisionable()
      && $this->currentUser->hasPermission('administer moderation state');
  }

}
