<?php

namespace Drupal\content_moderation\Plugin\Field;

use Drupal\content_moderation\ContentModerationStateInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use \Drupal\content_moderation\Entity\ModerationState as ModerationStateEntity;

class ModerationState extends EntityReferenceFieldItemList {

  /**
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function getModerationState() {
    $entity = $this->getEntity();
    $revision = $entity->getEntityType()->getKey('revision');

    if ($entity->id() && $entity->get($revision)->value) {
      /** @var \Drupal\content_moderation\ContentModerationStateInterface[] $entities */
      $entities = \Drupal::entityTypeManager()
        ->getStorage('content_moderation_state')
        ->loadByProperties([
          'content_entity_type_id' => $entity->getEntityTypeId(),
          'content_entity_id' => $entity->id(),
          'content_entity_revision_id' => $entity->get($revision)->value,
        ]);

      /** @var \Drupal\content_moderation\ContentModerationStateInterface $content_moderation_state */
      $content_moderation_state = reset($entities);
      if ($content_moderation_state instanceof ContentModerationStateInterface) {
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
    if ($property_name == 'entity' || $property_name = 'target_id') {
      $moderation_state = $this->getModerationState();
      if ($moderation_state) {
        if ($property_name = 'target_id') {
          return $this->getModerationState()->id();
        }
        return $moderation_state;
      }
    }
    return parent::__get($property_name);
  }

}
