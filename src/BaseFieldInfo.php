<?php

/**
 * @file
 * Contains \Drupal\moderation_state\BaseFieldInfo.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a class for handling moderation state base fields.
 */
class BaseFieldInfo {

  /**
   * Entity type ID to add fields for.
   *
   * @var string
   */
  protected $targetEntityTypeId = 'node';

  /**
   * Adds base field info to an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity type for adding base fields to.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   New fields added by moderation state.
   */
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    if ($entity_type->id() === $this->targetEntityTypeId) {
      $fields = [];
      // @todo write a test for this.
      $fields['moderation_state'] = BaseFieldDefinition::create('entity_reference')
        ->setLabel(t('Moderation state'))
        ->setDescription(t('The moderation state of this piece of content.'))
        ->setSetting('target_type', 'moderation_state')
        ->setRevisionable(TRUE)
        // @todo write a test for this.
        ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'string',
          'weight' => -5,
        ])
        // @todo write a custom widget/selection handler plugin instead of
        // manual filtering?
        ->setDisplayOptions('form', [
          'type' => 'moderation_state_default',
          'weight' => 5,
          'settings' => [],
        ])
        ->setDisplayConfigurable('form', FALSE)
        ->setDisplayConfigurable('view', TRUE);
      return $fields;
    }
    return [];
  }

}
