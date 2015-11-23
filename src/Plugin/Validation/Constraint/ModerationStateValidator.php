<?php
/**
 * @file
 * Contains \Drupal\moderation_state\Plugin\Validation\Constraint\ModerationStateValidator.
 */

namespace Drupal\moderation_state\Plugin\Validation\Constraint;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ModerationStateValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // @todo Validate the transition is allowed.
    // $this->context->addViolation($constraint->message, array('%type' => $type, '%id' => $id));
  }

}
