<?php

/**
 * @file
 * Contains Drupal\moderation_state\ModerationInformation.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;

/**
 * General service for moderation-related questions about Entity API.
 */
class ModerationInformation {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Determines if an entity is one we should be moderating.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity we may be moderating.
   *
   * @return bool
   *   TRUE if this is an entity that we should act upon, FALSE otherwise.
   */
  public function isModeratableEntity(EntityInterface $entity) {
    if (! $entity->getEntityType() instanceof ContentEntityTypeInterface) {
      return FALSE;
    }

    $type_string = $entity->getEntityType()->getBundleEntityType();

    /** @var EntityTypeInterface $entity_type */
    $entity_type = $this->entityTypeManager->getStorage($type_string)->load($entity->bundle());
    return $entity_type->getThirdPartySetting('moderation_state', 'enabled', FALSE);
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
  public function isModeratableBundle(EntityTypeInterface $entity_type, $bundle) {
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
   * Filters an entity list to just bundle definitions for revisionable entities.
   *
   * @param EntityTypeInterface[] $entity_types
   *   The master entity type list filter.
   * @return array
   *   An array of only the config entities we want to modify.
   */
  public function selectRevisionableEntityTypes(array $entity_types) {
    return array_filter($entity_types, function (EntityTypeInterface $type) use ($entity_types) {
      return ($type instanceof ConfigEntityTypeInterface)
        && $type->get('bundle_of')
        && $entity_types[$type->get('bundle_of')]->isRevisionable();
    });
  }

  /**
   * Determines if a config entity is a bundle for entities that may be moderated.
   *
   * This is the same check as exists in selectRevisionableEntityTypes(), but
   * that one cannot use the entity manager due to recursion and this one
   * doesn't have the entity list otherwise so must use the entity manager. The
   * alternative would be to call getDefinitions() on entityTypeManager and use
   * that in a sub-call, but that would be unnecessarily memory intensive.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @return bool
   *   TRUE if we want to add a Moderation operation to this entity, FALSE
   *   otherwise.
   */
  public function isBundleForModeratableEntity(EntityInterface $entity) {
    $type = $entity->getEntityType();

    return
      $type instanceof ConfigEntityTypeInterface
      && $type->get('bundle_of')
      && $this->entityTypeManager->getDefinition($type->get('bundle_of'))->isRevisionable()
      && $this->currentUser->hasPermission('administer moderation state');
  }

  /**
   * Determines if this form is for a moderated entity.
   *
   * @param \Drupal\Core\Form\FormInterface $form_object
   *   The form definition object for this form.
   * @return bool
   *   TRUE if the form is for an entity that is subject to moderation, FALSe
   *   otherwise.
   */
  public function isModeratedEntityForm(FormInterface $form_object) {
    return $form_object instanceof ContentEntityFormInterface
    && $this->isModeratableEntity($form_object->getEntity());
  }

  /**
   * Determines if the form is the bundle edit of a revisionable entity.
   *
   * The logic here is not entirely clear, but seems to work. The form- and
   * entity-dereference chaining seems excessive but is what works.
   *
   * @param \Drupal\Core\Form\FormInterface $form_object
   *   The form definition object for this form.
   * @return bool
   *   True if the form is the bundle edit form for an entity type that supports
   *   revisions, false otherwise.
   */
  public function isRevisionableBundleForm(FormInterface $form_object) {
    // We really shouldn't be checking for a base class, but core lacks an
    // interface here. When core adds a better way to determine if we're on
    // a Bundle configuration form we should switch to that.
    if ($form_object instanceof BundleEntityFormBase) {
      $bundle_of = $form_object->getEntity()->getEntityType()->getBundleOf();
      $type = $this->entityTypeManager->getDefinition($bundle_of);
      return $type->isRevisionable();
    }

    return FALSE;
  }
}
