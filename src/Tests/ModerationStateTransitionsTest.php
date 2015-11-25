<?php
/**
 * @file
 * Contains \Drupal\moderation_state\Tests\ModerationStateTransitionsTest.
 */

namespace Drupal\moderation_state\Tests;

/**
 * Tests moderation state transition config entity.
 * @group moderation_state
 */
class ModerationStateTransitionsTest extends ModerationStateTestBase {

  /**
   * Tests route access/permissions.
   */
  public function testAccess() {
    $paths = [
      'admin/structure/moderation-state/transitions',
      'admin/structure/moderation-state/transitions/add',
      'admin/structure/moderation-state/transitions/draft_needs_review',
      'admin/structure/moderation-state/transitions/draft_needs_review/delete',
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
   * Tests administration of moderation state transition entity.
   */
  public function testTransitionAdministration() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/moderation-state');
    $this->clickLink('Moderation state transitions');
    $this->assertLink('Add Moderation state transition');
    $this->assertText('Draft » Needs review');
    // Edit the Draft » Needs review.
    $this->clickLink('Edit');
    $this->assertFieldByName('label', 'Draft » Needs review');
    $this->assertFieldByName('stateFrom', 'draft');
    $this->assertFieldByName('stateTo', 'needs_review');
    $this->drupalPostForm(NULL, [
      'label' => 'Draft to Needs review',
    ], t('Save'));
    $this->assertText('Saved the Draft to Needs review Moderation state transition.');
    $this->drupalGet('admin/structure/moderation-state/transitions/draft_needs_review');
    $this->assertFieldByName('label', 'Draft to Needs review');
    $this->drupalPostForm(NULL, [
      'label' => 'Draft » Needs review',
    ], t('Save'));
    $this->assertText('Saved the Draft » Needs review Moderation state transition.');
    // Add a new state.
    $this->drupalGet('admin/structure/moderation-state/states/add');
    $this->drupalPostForm(NULL, [
      'label' => 'Expired',
      'id' => 'expired',
    ], t('Save'));
    $this->assertText('Created the Expired Moderation state.');
    $this->drupalGet('admin/structure/moderation-state/transitions');
    $this->clickLink(t('Add Moderation state transition'));
    $this->drupalPostForm(NULL, [
      'label' => 'Published » Expired',
      'id' => 'published_expired',
      'stateFrom' => 'published',
      'stateTo' => 'published',
    ], t('Save'));
    $this->assertText('You cannot use the same state for both from and to');
    $this->drupalPostForm(NULL, [
      'label' => 'Published » Expired',
      'id' => 'published_expired',
      'stateFrom' => 'published',
      'stateTo' => 'expired',
    ], t('Save'));
    $this->assertText('Created the Published » Expired Moderation state transition.');
    $this->drupalGet('admin/structure/moderation-state/transitions/published_expired');
    $this->clickLink('Delete');
    $this->assertText('Are you sure you want to delete Published » Expired?');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertText('Moderation transition Published » Expired deleted');
  }

}
