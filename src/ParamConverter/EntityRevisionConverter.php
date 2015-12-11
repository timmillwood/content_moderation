<?php
/**
 * @file
 * Contains \Drupal\moderation_state\ParamConverter\EntityRevisionConverter.
 */

namespace Drupal\moderation_state\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\TypedData\TranslatableInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a class for making sure the edit-route loads the current draft.
 */
class EntityRevisionConverter extends EntityConverter {

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if ($default = $route->getDefault('_entity_form') ) {
      list($entity_type_id, $operation) = explode('.', $default);
      if (!$this->entityManager->hasDefinition($entity_type_id)) {
        return FALSE;
      }
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      return $operation == 'edit' && $entity_type && $entity_type->isRevisionable();
    }
  }

  /**
   * Loads the latest revision of a specific entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The latest entity revision or NULL, if the entity type / entity doesn't
   *   exist.
   */
  protected function getLatestRevision($entity_type_id, $entity_id) {
    if ($storage = $this->entityManager->getStorage($entity_type_id)) {
      $revision_ids = $storage->getQuery()
        ->allRevisions()
        ->condition($this->entityManager->getDefinition($entity_type_id)->getKey('id'), $entity_id)
        ->sort($this->entityManager->getDefinition($entity_type_id)->getKey('revision'), 'DESC')
        ->pager(1)
        ->execute();
      if ($revision_ids) {
        $revision_id = array_keys($revision_ids)[0];
        return $storage->loadRevision($revision_id);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    if ($entity = $this->getLatestRevision($entity_type_id, $value)) {
      // If the entity type is translatable, ensure we return the proper
      // translation object for the current context.
      if ($entity instanceof EntityInterface && $entity instanceof TranslatableInterface) {
        $entity = $this->entityManager->getTranslationFromContext($entity, NULL, array('operation' => 'entity_upcast'));
      }
      return $entity;
    }
  }

}
