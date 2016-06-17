<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Disables the inline editing for entities with forward revisions.
 */
class InlineEditingDisabler {

  /**
   * The moderation info.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Creates a new InlineEditingDisabler instance.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation info.
   */
  public function __construct(ModerationInformationInterface $moderation_info) {
    $this->moderationInfo = $moderation_info;
  }

  /**
   * Implements hook_entity_view_alter().
   */
  public function entityViewAlter(&$build, EntityInterface $entity, EntityViewDisplayInterface $display) {
    if ($this->moderationInfo->isModeratableEntity($entity) && !$this->moderationInfo->isLatestRevision($entity)) {
      // Hide quickedit, because its super confusing for the user to not edit the
      // live revision.
      unset($build['#attributes']['data-quickedit-entity-id']);
    }
  }

  /**
   * Implements hook_module_implements_alter().
   */
  public function moduleImplementsAlter(&$implementations, $hook) {
    if ($hook == 'entity_view_alter') {
      // Find the quickedit implementation and move content after it.
      unset($implementations['content_moderation']);
      $implementations['content_moderation'] = FALSE;
    }
  }

}
