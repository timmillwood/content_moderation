<?php

/**
 * @file
 * Contains \Drupal\moderation_state\Tests\ModerationStateNodeTest.
 */

namespace Drupal\moderation_state\Tests;

/**
 * Tests general content moderation workflow for nodes..
 *
 * @group moderation_state
 */
class ModerationStateNodeTest extends ModerationStateTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->createContentTypeFromUI('Moderated content', 'moderated_content', TRUE, [
      'draft',
      'needs_review',
      'published'
    ], 'draft');
    $this->grantUserPermissionToCreateContentOfType($this->adminUser, 'moderated_content');
  }

  /**
   * Tests creating content.
   */
  public function testCreatingContent() {
    $this->drupalPostForm('node/add/moderated_content', [
      'title[0][value]' => 'moderated content',
    ], t('Save as Draft'));
  }

}
