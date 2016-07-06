<?php

namespace Drupal\content_moderation\Plugin\Field;

use Drupal\content_moderation\ContentModerationStateInterface;
use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use \Drupal\content_moderation\Entity\ModerationState as ModerationStateEntity;

class ModerationState extends EntityReferenceFieldItemList {

  /**
   * @return \Drupal\Core\Entity\EntityInterface
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
    $default = \Drupal::service('content_moderation.moderation_information')
      ->loadBundleEntity($entity->getEntityType()->getBundleEntityType(), $entity->bundle())
      ->getThirdPartySetting('content_moderation', 'default_moderation_state');
    if ($default) {
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

  public function __set($property_name, $value) {
    if ($property_name === 'entity' || $property_name === 'target_id') {
      $entity = $this->getEntity();
      $content_moderation_state = ContentModerationState::create();
      $content_moderation_state->set('content_entity_type_id', $entity->getEntityTypeId());
      $content_moderation_state->set('content_entity_id', $entity->id());
      $content_moderation_state->set('content_entity_revision_id', $entity->getRevisionId());
      if ($property_name === 'entity') {
        /** @var \Drupal\content_moderation\ModerationStateInterface $value */
        $content_moderation_state->set('moderation_state', $value->id());
      }
      else {
        $content_moderation_state->set('moderation_state', $value);
      }
      $content_moderation_state->save();
    }
    return parent::__set($property_name, $value);
  }

}
