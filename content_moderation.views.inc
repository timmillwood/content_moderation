<?php

/**
 * @file
 * Provide views data for content_moderation.module.
 *
 * @ingroup views_module_handlers
 */

use Drupal\content_moderation\ViewsData;

/**
 * Implements hook_views_data().
 */
function content_moderation_views_data() {
  $views_data = new ViewsData(
    \Drupal::service('entity_type.manager'),
    \Drupal::service('content_moderation.moderation_information')
  );
  return $views_data->getViewsData();
}
