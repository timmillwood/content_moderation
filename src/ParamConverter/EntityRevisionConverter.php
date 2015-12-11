<?php
/**
 * @file
 * Contains \Drupal\moderation_state\ParamConverter\EntityRevisionConverter.
 */

namespace Drupal\moderation_state\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\moderation_state\LatestRevisionTrait;
use Symfony\Component\Routing\Route;

/**
 * Defines a class for making sure the edit-route loads the current draft.
 */
class EntityRevisionConverter extends EntityConverter {

  use LatestRevisionTrait;

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
