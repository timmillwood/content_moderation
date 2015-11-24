<?php
/**
 * @file
 * Contains \Drupal\moderation_state\ModerationStatePermissions.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\moderation_state\Entity\ModerationState;
use Drupal\moderation_state\Entity\ModerationStateTransition;

/**
 * Defines a class for dynamic permisisons based on transitions.
 */
class ModerationStatePermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of transition permissions.
   *
   * @return array
   *   The transition permissions.
   */
  public function transitionPermissions() {
    // @todo write a test for this.
    $perms = [];
    $states = ModerationState::loadMultiple();
    /* @var \Drupal\moderation_state\ModerationStateTransitionInterface $transition */
    foreach (ModerationStateTransition::loadMultiple() as $id => $transition) {
      $perms['use ' . $id . ' transition'] = [
        'title' => $this->t('Use the %transition_name transition', [
          '%transition_name' => $transition->label(),
        ]),
        'description' => $this->t('Move content from %from state to %to state.', [
          '%from' => $states[$transition->getFromState()]->label(),
          '%to' => $states[$transition->getToState()]->label(),
        ]),
      ];
    }

    return $perms;
  }

}
