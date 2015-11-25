<?php
/**
 * @file
 * Contains \Drupal\moderation_state\Tests\ModerationStateStatesTest.
 */

namespace Drupal\moderation_state\Tests;

/**
 * Tests moderation state config entity.
 * @group moderation_state
 */
class ModerationStateStatesTest extends ModerationStateTestBase {

  /**
   * Tests route access/permissions.
   */
  public function testAccess() {
    $paths = [
      'admin/structure/moderation-state',
      'admin/structure/moderation-state/states',
      'admin/structure/moderation-state/states/add',
      'admin/structure/moderation-state/states/draft',
      'admin/structure/moderation-state/states/draft/delete',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      // No access.
      $this->assertResponse(403);
    }
    $this->drupalLogin($this->adminUser);
    foreach ($paths as $path) {
      $this->drupalGet($path);
      // User has access.
      $this->assertResponse(200);
    }
  }

  /**
   * Tests administration of moderation state entity.
   */
  public function testStateAdministration() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/moderation-state');
    $this->assertLink('Moderation states');
    $this->assertLink('Moderation state transitions');
    $this->clickLink('Moderation states');
    $this->assertLink('Add Moderation state');
    $this->assertText('Draft');
    // Edit the draft.
    $this->clickLink('Edit');
    $this->assertFieldByName('label', 'Draft');
    $this->assertNoFieldChecked('edit-published');
    $this->drupalPostForm(NULL, [
      'label' => 'Drafty',
    ], t('Save'));
    $this->assertText('Saved the Drafty Moderation state.');
    $this->drupalGet('admin/structure/moderation-state/states/draft');
    $this->assertFieldByName('label', 'Drafty');
    $this->drupalPostForm(NULL, [
      'label' => 'Draft',
    ], t('Save'));
    $this->assertText('Saved the Draft Moderation state.');
    $this->clickLink(t('Add Moderation state'));
    $this->drupalPostForm(NULL, [
      'label' => 'Expired',
      'id' => 'expired',
    ], t('Save'));
    $this->assertText('Created the Expired Moderation state.');
    $this->drupalGet('admin/structure/moderation-state/states/expired');
    $this->clickLink('Delete');
    $this->assertText('Are you sure you want to delete Expired?');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertText('Moderation state Expired deleted');
  }

}
