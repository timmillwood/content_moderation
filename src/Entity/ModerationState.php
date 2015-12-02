<?php

/**
 * @file
 * Contains \Drupal\moderation_state\Entity\ModerationState.
 */

namespace Drupal\moderation_state\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\moderation_state\ModerationStateInterface;

/**
 * Defines the Moderation state entity.
 *
 * @ConfigEntityType(
 *   id = "moderation_state",
 *   label = @Translation("Moderation state"),
 *   handlers = {
 *     "list_builder" = "Drupal\moderation_state\ModerationStateListBuilder",
 *     "form" = {
 *       "add" = "Drupal\moderation_state\Form\ModerationStateForm",
 *       "edit" = "Drupal\moderation_state\Form\ModerationStateForm",
 *       "delete" = "Drupal\moderation_state\Form\ModerationStateDeleteForm"
 *     },
 *   },
 *   config_prefix = "moderation_state",
 *   admin_permission = "administer moderation state",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/moderation-state/states/{moderation_state}",
 *     "edit-form" = "/admin/structure/moderation-state/states/{moderation_state}/edit",
 *     "delete-form" = "/admin/structure/moderation-state/states/{moderation_state}/delete",
 *     "collection" = "/admin/structure/moderation-state/states"
 *   }
 * )
 */
class ModerationState extends ConfigEntityBase implements ModerationStateInterface {
  /**
   * The Moderation state ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Moderation state label.
   *
   * @var string
   */
  protected $label;

  /**
   * Whether this state represents a published node.
   *
   * @var bool
   */
  protected $published;

  /**
   * {@inheritdoc}
   */
  public function isPublishedState() {
    return $this->published;
  }

}
