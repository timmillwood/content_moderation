<?php
/**
 * @file
 * Contains \Drupal\workbench_moderation\ParamConverter\EntityRevisionConverter.
 */

namespace Drupal\workbench_moderation\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\workbench_moderation\ModerationInformationInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a class for making sure the edit-route loads the current draft.
 */
class EntityRevisionConverter extends EntityConverter {

  /**
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * EntityRevisionConverter constructor.
   *
   * @todo: If the parent class is ever cleaned up to use EntityTypeManager
   * instead of Entity manager, this method will also need to be adjusted.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager, needed by the parent class.
   * @param \Drupal\workbench_moderation\ModerationInformationInterface $moderation_info
   *   The moderation info utility service.
   */
  public function __construct(\Drupal\Core\Entity\EntityManagerInterface $entity_manager, ModerationInformationInterface $moderation_info) {
    parent::__construct($entity_manager);
    $this->moderationInformation = $moderation_info;
  }

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
    if ($entity = $this->moderationInformation->getLatestRevision($entity_type_id, $value)) {
      // If the entity type is translatable, ensure we return the proper
      // translation object for the current context.
      if ($entity instanceof EntityInterface && $entity instanceof TranslatableInterface) {
        $entity = $this->entityManager->getTranslationFromContext($entity, NULL, array('operation' => 'entity_upcast'));
      }
      return $entity;
    }
  }

}
