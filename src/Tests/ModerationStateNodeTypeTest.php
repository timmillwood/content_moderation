<?php
/**
 * @file
 * Contains \Drupal\moderation_state\Tests\ModerationStateNodeTypeTest.
 */

namespace Drupal\moderation_state\Tests;

/**
 * Tests moderation state node type integration.
 * @group moderation_state
 */
class ModerationStateNodeTypeTest extends ModerationStateTestBase {

  /**
   * A node type without moderation state disabled.
   */
  public function testNotModerated() {
    $this->drupalLogin($this->adminUser);
    $this->createContentTypeFromUI('Not moderated', 'not_moderated');
    $this->assertText('The content type Not moderated has been added.');
    $this->grantUserPermissionToCreateContentOfType($this->adminUser, 'not_moderated');
    $this->drupalGet('node/add/not_moderated');
    $this->assertRaw('Save as unpublished');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Test',
    ], t('Save and publish'));
    $this->assertText('Not moderated Test has been created.');
  }

  /**
   * Tests enabling moderation on an existing node-type, with content.
   */
  /**
   * A node type without moderation state enabled.
   */
  public function testEnablingOnExistingContent() {
    $this->drupalLogin($this->adminUser);
    $this->createContentTypeFromUI('Not moderated', 'not_moderated');
    $this->grantUserPermissionToCreateContentOfType($this->adminUser, 'not_moderated');
    $this->drupalGet('node/add/not_moderated');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Test',
    ], t('Save and publish'));
    $this->assertText('Not moderated Test has been created.');
    // Now enable moderation state.
    $this->drupalGet('admin/structure/types/manage/not_moderated');
    $this->drupalPostForm(NULL, [
      'enable_moderation_state' => 1,
      'allowed_moderation_states[draft]' => 1,
      'allowed_moderation_states[needs_review]' => 1,
      'allowed_moderation_states[published]' => 1,
      'default_moderation_state' => 'draft',
    ], t('Save content type'));
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'title' => 'Test'
    ]);
    if (empty($nodes)) {
      $this->fail('Could not load node with title Test');
      return;
    }
    $node = reset($nodes);
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $this->assertLinkByHref('node/' . $node->id() . '/edit');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertResponse(200);
    $this->assertRaw('Save as Draft');
    $this->assertNoRaw('Save and publish');
  }

}
