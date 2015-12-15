<?php

/**
 * @file
 * Contains \Drupal\moderation_state\Plugin\Validation\Constraint\ModerationStateValidator.
 */

namespace Drupal\moderation_state\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * Creates a new ModerationStateValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\moderation_state\StateTransitionValidation $validation
   *   The state transition validation.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateTransitionValidation $validation) {
    $this->validation = $validation;
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('moderation_state.state_transition_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $value->getEntity();
    if (!$entity->isNew()) {
      $original_entity = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());
      $next_moderation_state_id = $entity->moderation_state->target_id;
      $original_moderation_state_id = $original_entity->moderation_state->target_id;

      if (!$this->validation->isTransitionAllowed($original_moderation_state_id, $next_moderation_state_id)) {
        $this->context->addViolation($constraint->message, ['%from' => $original_entity->moderation_state->entity->label(), '%to' => $entity->moderation_state->entity->label()]);
      }
    }
  }

}
