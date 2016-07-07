<?php

namespace Drupal\content_moderation\Plugin\Field;

use Drupal\content_moderation\Entity\ModerationState as ModerationStateEntity;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * A computed field that provides a content entity's moderation state.
 *
 * It links content entities to a moderation state configuration entity via a
 * moderation state content entity.
 */
class ModerationState extends EntityReferenceItem {

  /**
   * Gets the moderation state entity linked to a content entity revision.
   *
   * @return \Drupal\content_moderation\ModerationStateInterface|null
   *   The moderation state configuration entity linked to a content entity
   *   revision.
   */
  protected function getModerationState() {
    $entity = $this->getEntity();

    if ($entity->id() && $entity->getRevisionId()) {
      $revisions = \Drupal::service('entity.query')->get('content_moderation_state')
        ->condition('content_entity_type_id', $entity->getEntityTypeId())
        ->condition('content_entity_id', $entity->id())
        ->condition('content_entity_revision_id', $entity->getRevisionId())
        ->allRevisions()
        ->sort('revision_id', 'DESC')
        ->execute();

      if ($revision_to_load = key($revisions)) {
        /** @var \Drupal\content_moderation\ContentModerationStateInterface $content_moderation_state */
        $content_moderation_state = \Drupal::entityTypeManager()
          ->getStorage('content_moderation_state')
          ->loadRevision($revision_to_load);

        // Return the correct translation.
        $langcode = $entity->language()->getId();
        if (!$content_moderation_state->hasTranslation($langcode)) {
          $content_moderation_state->addTranslation($langcode);
        }
        if ($content_moderation_state->language()->getId() !== $langcode) {
          $content_moderation_state = $content_moderation_state->getTranslation($langcode);
        }

        return $content_moderation_state->get('moderation_state')->entity;
      }
    }
    // It is possible that the bundle does not exist at this point. For example,
    // the node type form creates a fake Node entity to get default values.
    // @see \Drupal\node\NodeTypeForm::form()
    $bundle_entity = \Drupal::service('content_moderation.moderation_information')
      ->loadBundleEntity($entity->getEntityType()->getBundleEntityType(), $entity->bundle());
    if ($bundle_entity && ($default = $bundle_entity->getThirdPartySetting('content_moderation', 'default_moderation_state'))) {
      return ModerationStateEntity::load($default);
    }
  }

  /**
   * @inheritDoc
   */
  public function __get($property_name) {
    if ($property_name === 'entity' || $property_name === 'target_id') {
      $moderation_state = $this->getModerationState();
      if ($moderation_state) {
        if ($property_name === 'target_id') {
          return $moderation_state->id();
        }
        return $moderation_state;
      }
    }
    return parent::__get($property_name);
  }

  /**
   * @inheritDoc
   */
  public function __set($property_name, $value) {
    if ($property_name === 'entity' || $property_name === 'target_id') {
      $entity = $this->getEntity();
      if ($property_name === 'entity') {
        /** @var \Drupal\content_moderation\ModerationStateInterface $value */
        $entity->moderation_state_target_id = $value->id();
      }
      else {
        $entity->moderation_state_target_id = $value;
      }
    }
    return parent::__set($property_name, $value);
  }

}
