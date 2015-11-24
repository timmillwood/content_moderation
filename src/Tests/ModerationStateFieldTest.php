<?php
/**
 * @file
 * Contains \Drupal\moderation_state\Tests\ModerationStateFieldTest.
 */

namespace Drupal\moderation_state\Tests;


use Drupal\simpletest\KernelTestBase;

/**
 * Tests moderation state field.
 * @group moderation_state
 */
class ModerationStateFieldTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['moderation_state', 'node', 'views', 'options', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
  }

  /**
   * {@inheritdoc}
   *
   * Covers moderation_state_install().
   */
  public function testModerationStateFieldIsCreated() {
    // There should be no updates as moderation_state_install() should have
    // applied the new field.
    $this->assertTrue(empty($this->container->get('entity.definition_update_manager')->needsUpdates()['node']));
    $this->assertTrue(!empty($this->container->get('entity_field.manager')->getFieldStorageDefinitions('node')['moderation_state']));
  }

}
