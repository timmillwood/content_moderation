<?php

/**
 * @file
 * Contains \Drupal\moderation_state\Plugin\Validation\Constraint\ModerationStateValidator.
 */

namespace Drupal\moderation_state\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\moderation_state\ModerationInformationInterface;
use Drupal\moderation_state\StateTransitionValidation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ModerationStateValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The state transition validation.
   *
   * @var \Drupal\moderation_state\StateTransitionValidation
   */
  protected $validation;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The moderation info.
   *
   * @var \Drupal\moderation_state\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Creates a new ModerationStateValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\moderation_state\StateTransitionValidation $validation
   *   The state transition validation.
   * @param \Drupal\moderation_state\ModerationInformationInterface $moderation_information
   *   The moderation information.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateTransitionValidation $validation, ModerationInformationInterface $moderation_information) {
    $this->validation = $validation;
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInformation = $moderation_information;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('moderation_state.state_transition_validation'),
      $container->get('moderation_state.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $value->getEntity();
    if (!$this->moderationInformation->isModeratableEntity($entity)) {
      return;
    }
    if (!$entity->isNew()) {
      $original_entity = $this->moderationInformation->getLatestRevision($entity->getEntityTypeId(), $entity->id());
      $next_moderation_state_id = $entity->moderation_state->target_id;
      $original_moderation_state_id = $original_entity->moderation_state->target_id;

      if (!$this->validation->isTransitionAllowed($original_moderation_state_id, $next_moderation_state_id)) {
        $this->context->addViolation($constraint->message, ['%from' => $original_entity->moderation_state->entity->label(), '%to' => $entity->moderation_state->entity->label()]);
      }
    }
  }

}
