<?php

/**
 * @file
 * Contains \Drupal\workbench_moderation\Tests\ModerationStateNodeTest.
 */

namespace Drupal\workbench_moderation\Tests;

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
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'title' => 'moderated content',
      ]);
    $node = reset($nodes);

    $path = 'node/' . $node->id() . '/edit';
    // Set up needs review revision.
    $this->drupalPostForm($path, [], t('Save and transition to Needs Review'));
    // Set up published revision.
    $this->drupalPostForm($path, [], t('Save and transition to Published'));
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    /* @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());
    $this->assertTrue($node->isPublished());
  }

}
