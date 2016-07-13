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
  return _content_moderation_views_data_object()->getViewsData();
}

/**
 * Implements hook_views_data_alter().
 */
function content_moderation_views_data_alter(array &$data) {
  _content_moderation_views_data_object()->alterViewsData($data);
}

function _content_moderation_views_data_object() {
  return new ViewsData(
    \Drupal::service('entity_type.manager'),
    \Drupal::service('content_moderation.moderation_information')
  );
}
