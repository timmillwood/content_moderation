<?php

namespace Drupal\content_moderation\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\RevisionTrackerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a class for making sure the edit-route loads the current draft.
 */
class EntityRevisionConverter extends EntityConverter {

  /**
   * Moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Revisions tracker service.
   *
   * @var \Drupal\content_moderation\RevisionTracker
   */
  protected $tracker;

  /**
   * EntityRevisionConverter constructor.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager, needed by the parent class.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation info utility service.
   *
   * @todo: If the parent class is ever cleaned up to use EntityTypeManager
   *   instead of Entity manager, this method will also need to be adjusted.
   */
  public function __construct(EntityManagerInterface $entity_manager, ModerationInformationInterface $moderation_info, RevisionTrackerInterface $tracker) {
    parent::__construct($entity_manager);
    $this->moderationInformation = $moderation_info;
    $this->tracker = $tracker;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return $this->hasForwardRevisionFlag($definition) || $this->isEditFormPage($route);
  }

  /**
   * Determines if the route definition includes a forward-revision flag.
   *
   * This is a custom flag defined by WBM to load forward revisions rather than
   * the default revision on a given route.
   *
   * @param array $definition
   *   The parameter definition provided in the route options.
   *
   * @return bool
   *   TRUE if the forward revision flag is set, FALSE otherwise.
   */
  protected function hasForwardRevisionFlag(array $definition) {
    return (isset($definition['load_forward_revision']) && $definition['load_forward_revision']);
  }

  /**
   * Determines if a given route is the edit-form for an entity.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route definition.
   *
   * @return bool
   *   Returns TRUE if the route is the edit form of an entity, FALSE otherwise.
   */
  protected function isEditFormPage(Route $route) {
    if ($default = $route->getDefault('_entity_form')) {
      // If no operation is provided, use 'default'.
      $default .= '.default';
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
    $entity = parent::convert($value, $definition, $name, $defaults);

    if ($entity && $this->moderationInformation->isModeratableEntity($entity)) {
      $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
      $latest_revision_id = $this->tracker->getLatestRevision($entity_type_id, $entity->id(), $entity->language()->getId());
      if ($latest_revision_id) {
        $latest_revision = node_revision_load($latest_revision_id);
        // If the entity type is translatable, ensure we return the proper
        // translation object for the current context.
        if ($latest_revision instanceof EntityInterface && $entity instanceof TranslatableInterface) {
          $latest_revision = $this->entityManager->getTranslationFromContext($latest_revision, NULL, array('operation' => 'entity_upcast'));
        }
        $entity = $latest_revision;
      }
    }

    return $entity;
  }

}
