<?php

/**
 * @file
 * Contains \Drupal\Tests\workbench_moderation\Kernel\ModerationStateEntityTest.
 */

namespace Drupal\Tests\workbench_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workbench_moderation\Entity\ModerationState;

/**
 * Class ModerationStateEntityTest
 *
 * @coversDefaultClass \Drupal\workbench_moderation\Entity\ModerationState
 * @group workbench_moderation
 */
class ModerationStateEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workbench_moderation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('moderation_state');
  }

  /**
   * @covers ::isPublishedState
   * @covers ::isDefaultRevisionState
   *
   * @todo these might not quite be crud tests, since they're looking at the method logic
   */
  public function testModerationStateCrud() {
    $moderation_state_id = $this->randomMachineName();
    $moderation_state = ModerationState::create([
      'id' => $moderation_state_id,
      'label' => $this->randomString(),
      'published' => FALSE,
      'default_revision' => FALSE,
    ]);
    // @todo is it necessary to do ModerationState::load() every time? if this test is focused on the methods, rather than the properties themselves, maybe ::save and ::load are unnecessary?
    $moderation_state->save();
    $moderation_state = ModerationState::load($moderation_state_id);

    $this->assertFalse($moderation_state->isPublishedState());
    $this->assertFalse($moderation_state->isDefaultRevisionState());

    // For archived states, a moderation state may prompt the revision to
    // become the default default revision, but not be published.
    $moderation_state->set('published', FALSE);
    $moderation_state->set('default_revision', TRUE);
    $moderation_state->save();
    $moderation_state = ModerationState::load($moderation_state_id);

    $this->assertFalse($moderation_state->isPublishedState());
    $this->assertTrue($moderation_state->isDefaultRevisionState());

    // When a moderation state is a published state, it should also become the
    // default revision.
    $moderation_state->set('published', TRUE);
    $moderation_state->set('default_revision', TRUE);
    $moderation_state->save();
    $moderation_state = ModerationState::load($moderation_state_id);
    $this->assertTrue($moderation_state->isPublishedState());
    $this->assertTrue($moderation_state->isDefaultRevisionState());

    $moderation_state->set('published', TRUE);
    $moderation_state->set('default_revision', FALSE);
    $moderation_state->save();
    $moderation_state = ModerationState::load($moderation_state_id);
    $this->assertTrue($moderation_state->isPublishedState());
    $this->assertTrue($moderation_state->isDefaultRevisionState());

    // When we delete a moderation state, it should go away.
    // @todo this probably isn't necessary -- this case should be covered by
    //       regular entity tests, right? however, this test class name does
    //       say "crud"...
    $moderation_state->delete();
    $this->assertNull(ModerationState::load($moderation_state_id));
  }

}
