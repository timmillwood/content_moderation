<?php
/**
 * @file
 * Contains \Drupal\moderation_state\ParamConverter\NodeRevisionConverter.
 */

namespace Drupal\moderation_state\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\TypedData\TranslatableInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a class for making sure the edit-route loads the current draft.
 */
class NodeRevisionConverter extends EntityConverter {

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return $route->getDefault('_entity_form') === 'node.edit';
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    if ($storage = $this->entityManager->getStorage($entity_type_id)) {
      $entity = $storage->load($value);
      $revision_ids = $storage->revisionIds($entity);
      sort($revision_ids);
      $latest = end($revision_ids);
      $entity = $storage->loadRevision($latest);
      // If the entity type is translatable, ensure we return the proper
      // translation object for the current context.
      if ($entity instanceof EntityInterface && $entity instanceof TranslatableInterface) {
        $entity = $this->entityManager->getTranslationFromContext($entity, NULL, array('operation' => 'entity_upcast'));
      }
      return $entity;
    }
  }

}
