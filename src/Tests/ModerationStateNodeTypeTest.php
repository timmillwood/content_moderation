<?php
/**
 * @file
 * Contains \Drupal\moderation_state\Tests\ModerationStateNodeTypeTest.
 */

namespace Drupal\moderation_state\Tests;
use Drupal\user\Entity\Role;

/**
 * Tests moderation state node type integration.
 * @group moderation_state
 */
class ModerationStateNodeTypeTest extends ModerationStateTestBase {

  /**
   * A node type without moderation state enabled.
   */
  public function testNotModerated() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/types');
    $this->clickLink('Add content type');
    $this->assertFieldByName('enable_moderation_state');
    $this->assertNoFieldChecked('edit-enable-moderation-state');
    $this->drupalPostForm(NULL, [
      'name' => 'Not moderated',
      'type' => 'not_moderated',

    ], t('Save content type'));
    $this->assertText('The content type Not moderated has been added.');
    $role_ids = $this->adminUser->getRoles(TRUE);
    /** @var \Drupal\user\RoleInterface $role */
    $role_id = reset($role_ids);
    $role = Role::load($role_id);
    $role->grantPermission('create not_moderated content');
    $this->drupalGet('node/add/not_moderated');
    $this->assertRaw('Save as unpublished');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Test',
    ], t('Save and publish'));
    $this->assertText('Not moderated Test has been created.');
  }
}
