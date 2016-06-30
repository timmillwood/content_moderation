<?php

namespace Drupal\content_moderation\Entity;

use Drupal\content_moderation\ContentModerationStateInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the content_moderation_state entity class.
 *
 * @ContentEntityType(
 *   id = "content_moderation_state",
 *   label = @Translation("Content Moderation State"),
 *   base_table = "content_moderation_state",
 *   data_table = "content_moderation_state_field_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *   }
 * )
 */
class ContentModerationState extends ContentEntityBase implements ContentModerationStateInterface{
  use EntityChangedTrait;

  /**
   * @inheritDoc
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['moderation_state'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Moderation state'))
      ->setDescription(t('The moderation state of the referenced content.'))
      ->setSetting('target_type', 'moderation_state')
      ->addConstraint('ModerationState', []);

    $fields['content_entity_type_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Content entity type ID'))
      ->setDescription(t('The ID of the content entity type this moderation state is for.'));

    $fields['content_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Content entity ID'))
      ->setDescription(t('The ID of the content entity this moderation state is for.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }


}