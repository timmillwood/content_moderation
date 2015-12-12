<?php

/**
 * @file
 * Contains Drupal\moderation_state\ModerationInformation.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * General service for moderation-related questions about Entity API.
 */
class ModerationInformation implements ModerationInformationInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function isModeratableEntity(EntityInterface $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      return FALSE;
    }

    return $this->isModeratableBundle($entity->getEntityType(), $entity->bundle());
  }

  /**
   * Loads a specific bundle entity.
   *
   * @param string $bundle_entity_type_id
   *   The bundle entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface|null
   */
  protected function loadBundleEntity($bundle_entity_type_id, $bundle_id) {
    if ($bundle_entity_type_id) {
      return $this->entityTypeManager->getStorage($bundle_entity_type_id)->load($bundle_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isModeratableBundle(EntityTypeInterface $entity_type, $bundle) {
    if ($bundle_entity = $this->loadBundleEntity($entity_type->getBundleEntityType(), $bundle)) {
      return $bundle_entity->getThirdPartySetting('moderation_state', 'enabled', FALSE);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function selectRevisionableEntityTypes(array $entity_types) {
    return array_filter($entity_types, function (EntityTypeInterface $type) use ($entity_types) {
      return ($type instanceof ConfigEntityTypeInterface)
      && ($bundle_of = $type->get('bundle_of'))
      && $entity_types[$bundle_of]->isRevisionable();
    });
  }

  /**
   * {@inheritdoc}
   */
  public function isBundleForModeratableEntity(EntityInterface $entity) {
    $type = $entity->getEntityType();

    return
      $type instanceof ConfigEntityTypeInterface
      && ($bundle_of = $type->get('bundle_of'))
      && $this->entityTypeManager->getDefinition($bundle_of)->isRevisionable()
      && $this->currentUser->hasPermission('administer moderation state');
  }

  /**
   * {@inheritdoc}
   */
  public function isModeratedEntityForm(FormInterface $form_object) {
    return $form_object instanceof ContentEntityFormInterface
    && $this->isModeratableEntity($form_object->getEntity());
  }

  /**
   * {@inheritdoc}
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
