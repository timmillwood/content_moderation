<?php

/**
 * @file
 * Contains Drupal\Tests\workbench_moderation\Kernel\EntityOperationsTest.
 */

namespace Drupal\Tests\workbench_moderation\Kernel;


use Drupal\KernelTests\KernelTestBase;
use Drupal\workbench_moderation\EntityOperations;
use Drupal\workbench_moderation\Entity\ModerationState;
use Drupal\workbench_moderation\ModerationInformation;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class EntityOperationsTest
 *
 * @coversDefaultClass \Drupal\workbench_moderation\EntityOperations
 * @group workbench_moderation
 */
class EntityOperationsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workbench_moderation', 'node', 'views', 'options', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installConfig('workbench_moderation');

    $this->createNodeType();
  }

  /**
   * Creates a page node type to test with, ensuring that it's moderatable.
   */
  protected function createNodeType() {
    $node_type = NodeType::create([
      'type' => 'page',
      'label' => 'Page',
    ]);
    $node_type->setThirdPartySetting('workbench_moderation', 'enabled', TRUE);
    $node_type->save();
  }

  /**
   * Verifies that the process of saving forward-revisions works as expected.
   */
  public function testForwardRevisions() {
    // Create a new node in draft.
    $page = Node::create([
      'type' => 'page',
      'title' => 'A',
    ]);
    $page->moderation_state->target_id = 'draft';
    $page->save();

    $id = $page->id();

    // Verify the entity saved correctly, and that the presence of forward
    // revisions doesn't affect the default node load.
    /** @var Node $page */
    $page = Node::load($id);
    $this->assertEquals('A', $page->getTitle());
    $this->assertTrue($page->isDefaultRevision());
    $this->assertFalse($page->isPublished());

    // Moderate the entity to published.
    $page->setTitle('B');
    $page->moderation_state->target_id = 'published';
    $page->save();

    // Verify the entity is now published and public.
    $page = Node::load($id);
    $this->assertEquals('B', $page->getTitle());
    $this->assertTrue($page->isDefaultRevision());
    $this->assertTrue($page->isPublished());

    // Make a new forward-revision in Draft.
    $page->setTitle('C');
    $page->moderation_state->target_id = 'draft';
    $page->save();

    // Verify normal loads return the still-default previous version.
    $page = Node::load($id);
    $this->assertEquals('B', $page->getTitle());

    // Verify we can load the forward revision, even if the mechanism is kind
    // of gross. Note: revisionIds() is only available on NodeStorageInterface,
    // so this won't work for non-nodes. We'd need to use entity queries. This
    // is a core bug that should get fixed.
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $revision_ids = $storage->revisionIds($page);
    sort($revision_ids);
    $latest = end($revision_ids);
    $page = $storage->loadRevision($latest);
    $this->assertEquals('C', $page->getTitle());

    $page->setTitle('D');
    $page->moderation_state->target_id = 'published';
    $page->save();

    // Verify normal loads return the still-default previous version.
    $page = Node::load($id);
    $this->assertEquals('D', $page->getTitle());
    $this->assertTrue($page->isDefaultRevision());
    $this->assertTrue($page->isPublished());

    // Now check that we can immediately add a new published revision over it.
    $page->setTitle('E');
    $page->moderation_state->target_id = 'published';
    $page->save();

    $page = Node::load($id);
    $this->assertEquals('E', $page->getTitle());
    $this->assertTrue($page->isDefaultRevision());
    $this->assertTrue($page->isPublished());
  }

  /**
   * Verifies that a newly-created node can go straight to published.
   */
  public function testPublishedCreation() {
    // Create a new node in draft.
    $page = Node::create([
      'type' => 'page',
      'title' => 'A',
    ]);
    $page->moderation_state->target_id = 'published';
    $page->save();

    $id = $page->id();

    // Verify the entity saved correctly.
    /** @var Node $page */
    $page = Node::load($id);
    $this->assertEquals('A', $page->getTitle());
    $this->assertTrue($page->isDefaultRevision());
    $this->assertTrue($page->isPublished());
  }

  /**
   * Verifies that an unpublished state may be made the default revision.
   */
  public function testArchive() {
    $published_id = $this->randomMachineName();
    $published_state = ModerationState::create([
      'id' => $published_id,
      'label' => $this->randomString(),
      'published' => TRUE,
      'default_revision' => TRUE,
    ]);
    $published_state->save();

    $archived_id = $this->randomMachineName();
    $archived_state = ModerationState::create([
      'id' => $archived_id,
      'label' => $this->randomString(),
      'published' => FALSE,
      'default_revision' => TRUE,
    ]);
    $archived_state->save();

    $page = Node::create([
      'type' => 'page',
      'title' => $this->randomString(),
    ]);
    $page->moderation_state->target_id = $published_id;
    $page->save();

    $id = $page->id();

    // The newly-created page should already be published.
    $page = Node::load($id);
    $this->assertTrue($page->isPublished());

    // When the page is moderated to the archived state, then the latest
    // revision should be the default revision, and it should be unpublished.
    $page->moderation_state->target_id = $archived_id;
    $page->save();
    $new_revision_id = $page->getRevisionId();

    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $new_revision = $storage->loadRevision($new_revision_id);
    $this->assertFalse($new_revision->isPublished());
    $this->assertTrue($new_revision->isDefaultRevision());
  }

  /**
   * Test behavior of forward drafts of nodes when moderation is disabled.
   */
  public function testModerationDisabledContent() {
    // Create a content type.
    $type = $this->randomMachineName();
    $node_type = NodeType::create([
      'type' => $type,
    ]);
    $node_type->save();

    // Enable moderation for the content type.
    /** @var NodeType $node_type */
    $node_type = NodeType::load($type);
    $node_type->setThirdPartySetting('workbench_moderation', 'enabled', TRUE);
    $node_type->setThirdPartySetting('workbench_moderation', 'allowed_moderation_states', [
      'draft',
      'needs_review',
      'published'
    ]);
    $node_type->setThirdPartySetting('workbench_moderation', 'default_moderation_state', 'draft');
    $node_type->save();

    // Create an initial version of the content.
    $node = Node::create([
      'type' => $type,
      'title' => 'First version',
    ]);
    $node->moderation_state->target_id = 'published';
    $node->save();
    $id = $node->id();

    // The default revision and latest revision should be the same revision.
    $node_default = Node::load($id);
    $node_latest = $this->getNodeLatestRevision($node_default);

    // default version title should be "First version"
    $this->assertEquals('First version', $node_default->label());
    // default revision should be published
    $this->assertTrue($node_default->isPublished());
    // latest revision == default revision
    $this->assertEquals($node_default->getRevisionId(), $node_latest->getRevisionId());

    // Update the node; this should become a forward draft.
    $node->title = 'Version 2';
    $node->moderation_state->target_id = 'draft';
    $node->save();
    $node_default = Node::load($id);
    $node_latest = $this->getNodeLatestRevision($node_default);

    // default version title should be "First version"
    $this->assertEquals('First version', $node_default->label());
    // default revision should be published
    $this->assertTrue($node_default->isPublished());
    // latest revision title should be "Version 2"
    $this->assertEquals('Version 2', $node_latest->label());
    // latest revision should be unpublished
    $this->assertFalse($node_latest->isPublished());
    // latest revision != default revision
    $this->assertNotEquals($node_default->getRevisionId(), $node_latest->getRevisionId());

    // Disable moderation for the content type.
    $node_type = NodeType::load($type);
    $node_type->setThirdPartySetting('workbench_moderation', 'enabled', FALSE);
    $node_type->save();

    // The default and latest revisions should be the same as when moderation
    // was enabled.
    $node_default = Node::load($id);
    $node_latest = $this->getNodeLatestRevision($node_default);

    // default version title should be "First version"
    $this->assertEquals('First version', $node_default->label());
    // latest revision title should be "Version 2"
    $this->assertEquals('Version 2', $node_latest->label());
    // latest revision != default revision
    $this->assertNotEquals($node_default->getRevisionId(), $node_latest->getRevisionId());

    // Update the node; this update should be made the default revision upon
    // save, now that moderation is disabled for this node type.
    $node->title = "Version 3";
    // (whether it is published or not)
    // $node->isPublished(TRUE);
    $node->save();
    $node_default = Node::load($id);
    $node_latest = $this->getNodeLatestRevision($node_default);

    // default version title should be "Version 3"
    $this->assertEquals('Version 3', $node_default->label());
    // default revision should be unpublished
    $this->assertFalse($node_default->isPublished());
    // latest revision == default revision
    $this->assertEquals($node_default->getRevisionId(), $node_latest->getRevisionId());
  }

  public function getNodeLatestRevision($node) {
    $storage = \Drupal::entityTypeManager()->getStorage('node');

    $revision_ids = $storage->revisionIds($node);
    sort($revision_ids);
    $latest = end($revision_ids);

    return $storage->loadRevision($latest);
  }

}
