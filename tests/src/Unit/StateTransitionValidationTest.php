<?php

/**
 * @file
 * Contains \Drupal\Tests\workbench_moderation\Unit\StateTransitionValidationTest.
 */

namespace Drupal\Tests\workbench_moderation\Unit;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\workbench_moderation\ModerationStateTransitionInterface;
use Drupal\workbench_moderation\StateTransitionValidation;

/**
 * @coversDefaultClass \Drupal\workbench_moderation\StateTransitionValidation
 * @group workbench_moderation
 */
class StateTransitionValidationTest extends \PHPUnit_Framework_TestCase {

  protected function setupTranslationValidation(EntityStorageInterface $entity_storage) {
    return new StateTransitionValidation($entity_storage);
  }

  protected function setupExampleStorage() {
    $entity_storage = $this->prophesize(EntityStorageInterface::class);

    $state_transition0 = $this->prophesize(ModerationStateTransitionInterface::class);
    $state_transition0->getFromState()->willReturn('draft');
    $state_transition0->getToState()->willReturn('needs_review');

    $state_transition1 = $this->prophesize(ModerationStateTransitionInterface::class);
    $state_transition1->getFromState()->willReturn('needs_review');
    $state_transition1->getToState()->willReturn('staging');

    $state_transition2 = $this->prophesize(ModerationStateTransitionInterface::class);
    $state_transition2->getFromState()->willReturn('staging');
    $state_transition2->getToState()->willReturn('published');

    $state_transition3 = $this->prophesize(ModerationStateTransitionInterface::class);
    $state_transition3->getFromState()->willReturn('needs_review');
    $state_transition3->getToState()->willReturn('draft');

    $state_transition4 = $this->prophesize(ModerationStateTransitionInterface::class);
    $state_transition4->getFromState()->willReturn('draft');
    $state_transition4->getToState()->willReturn('draft');

    $state_transition5 = $this->prophesize(ModerationStateTransitionInterface::class);
    $state_transition5->getFromState()->willReturn('needs_review');
    $state_transition5->getToState()->willReturn('needs_review');

    $state_transition6 = $this->prophesize(ModerationStateTransitionInterface::class);
    $state_transition6->getFromState()->willReturn('published');
    $state_transition6->getToState()->willReturn('published');

    $entity_storage->loadMultiple()->willReturn([
      'draft__needs_review' => $state_transition0->reveal(),
      'needs_review__staging' => $state_transition1->reveal(),
      'staging__published' => $state_transition2->reveal(),
      'needs_review__draft' => $state_transition3->reveal(),
      'draft_draft' => $state_transition4->reveal(),
      'needs_review__needs_review' => $state_transition5->reveal(),
      'published__published' => $state_transition6->reveal(),
    ]);
    return $entity_storage->reveal();
  }

  /**
   * @covers ::isTransitionAllowed
   * @covers ::calculatePossibleTransitions
   */
  public function testIsTransitionAllowedWithValidTransition() {
    $state_transition_validation = $this->setupTranslationValidation($this->setupExampleStorage($this->setupExampleStorage()));
    $this->assertTrue($state_transition_validation->isTransitionAllowed('draft', 'draft'));
    $this->assertTrue($state_transition_validation->isTransitionAllowed('draft', 'needs_review'));
    $this->assertTrue($state_transition_validation->isTransitionAllowed('needs_review', 'needs_review'));
    $this->assertTrue($state_transition_validation->isTransitionAllowed('needs_review', 'staging'));
    $this->assertTrue($state_transition_validation->isTransitionAllowed('staging', 'published'));
    $this->assertTrue($state_transition_validation->isTransitionAllowed('needs_review', 'draft'));
  }

  /**
   * @covers ::isTransitionAllowed
   * @covers ::calculatePossibleTransitions
   */
  public function testIsTransitionAllowedWithInValidTransition() {
    $state_transition_validation = $this->setupTranslationValidation($this->setupExampleStorage($this->setupExampleStorage()));
    $this->assertFalse($state_transition_validation->isTransitionAllowed('published', 'needs_review'));
    $this->assertFalse($state_transition_validation->isTransitionAllowed('published', 'staging'));
    $this->assertFalse($state_transition_validation->isTransitionAllowed('staging', 'needs_review'));
    $this->assertFalse($state_transition_validation->isTransitionAllowed('staging', 'staging'));
    $this->assertFalse($state_transition_validation->isTransitionAllowed('needs_review', 'published'));
  }

}
