<?php

namespace Drupal\content_moderation\Plugin\Field;

use Drupal\Core\Field\FieldItemList;

class ModerationState extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $entity = $this->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $entity_id = $entity->id();

    /** @var \Drupal\content_moderation\ContentModerationStateInterface[] $entities */
    $entities = \Drupal::entityTypeManager()
      ->getStorage('content_moderation_state')
      ->loadByProperties([
        'content_entity_type_id' => $entity_type_id,
        'content_entity_id' => $entity_id
      ]);

    /** @var \Drupal\content_moderation\ContentModerationStateInterface $content_moderation_state */
    $content_moderation_state = reset($entities);
    return $content_moderation_state->get('moderation_state');
  }

}