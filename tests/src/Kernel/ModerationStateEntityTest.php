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

  public function testModerationStateCrud() {
    $moderation_state = ModerationState::create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'published' => false,
      'live_revision' => false,
    ]);

    $this->assertFalse($moderation_state->isPublishedState());
    $this->assertFalse($moderation_state->isLiveRevisionState());

    // @todo update moderation state to have live_revision = 1, published = 0
    // @todo verify isPublishedState == false, isLiveRevisionState == true

    // @todo update moderation state to have published = 1, live_revision = 1
    // @todo verify isPublishedState == true, isLiveRevisionState == true

    // @todo update moderation state to have published = 1, live_revision = 0
    // @todo verify isPublishedState == true, isLiveRevisionState == true
    $this->assertTrue(FALSE, 'Pending');
  }

}
