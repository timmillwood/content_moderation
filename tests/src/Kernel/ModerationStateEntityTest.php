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
   * @covers ::isPublishedState()
   * @covers ::isLiveRevisionState()
   *
   * @todo these might not quite be crud tests, since they're looking at the method logic
   */
  public function testModerationStateCrud() {
    $moderation_state_id = $this->randomMachineName();
    $moderation_state = ModerationState::create([
      'id' => $moderation_state_id,
      'label' => $this->randomString(),
      'published' => false,
      'live_revision' => false,
    ]);

    $this->assertFalse($moderation_state->isPublishedState());
    $this->assertFalse($moderation_state->isLiveRevisionState());

    // For archived states, a moderation state may prompt the revision to
    // become the default live revision, but not be published.
    $moderation_state->set('published', false);
    $moderation_state->set('live_revision', true);
    $moderation_state->save();

    $this->assertFalse($moderation_state->isPublishedState());
    $this->assertTrue($moderation_state->isLiveRevisionState());

    // When a moderation state is a published state, it should also become the
    // live revision.
    $moderation_state->set('published', true);
    $moderation_state->set('live_revision', true);
    $moderation_state->save();
    $this->assertTrue($moderation_state->isPublishedState());
    $this->assertTrue($moderation_state->isLiveRevisionState());

    $moderation_state->set('published', true);
    $moderation_state->set('live_revision', false);
    $moderation_state->save();
    $this->assertTrue($moderation_state->isPublishedState());
    $this->assertTrue($moderation_state->isLiveRevisionState());

    // When we delete a moderation state, it should go away.
    // @todo this probably isn't necessary -- this case should be covered by
    //       regular entity tests, right? however, this test class name does
    //       say "crud"...
    $moderation_state->delete();
    $this->assertNull(ModerationState::load($moderation_state_id));
  }

}
