<?php

namespace Drupal\content_moderation\Event;

/**
 * Defines events for content moderation.
 *
 * @see \Drupal\content_moderation\Event\ContentModerationTransitionEvent
 */
final class ContentModerationEvents {

  /**
   * This event is fired every time a state is changed.
   *
   * @Event
   */
  const STATE_TRANSITION = 'content_moderation.state_transition';

}
