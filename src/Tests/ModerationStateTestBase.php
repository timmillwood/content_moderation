<?php
/**
 * @file
 * Contains \Drupal\moderation_state\Tests\ModerationStateTestBase.
 */

namespace Drupal\moderation_state\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Defines a base class for moderation state tests.
 */
abstract class ModerationStateTestBase extends WebTestBase {

  /**
   * Profile to use.
   */
  protected $profile = 'testing';

  /**
   * Admin user
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer moderation state',
    'administer moderation state transitions',
    'use draft_needs_review transition',
    'use published_draft transition',
    'use needs_review_published transition',
    'access administration pages',
    'administer content types',
    'administer nodes',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'moderation_state',
    'block',
    'node',
    'views',
    'options',
    'user',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'tabs_block']);
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_actions_block', ['id' => 'actions_block']);
  }

}
