<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the views integration of content_moderation.
 *
 * @group content_moderation
 */
class ViewsDataIntegrationTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_moderation_test_views',
    'node',
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', 'node_access');
    $this->installConfig('content_moderation_test_views');
  }

  public function testViewsData() {
    $node_type = NodeType::create([
      'type' => 'page',
    ]);
    $node_type->setThirdPartySetting('content_moderation', 'enabled', TRUE);
    $node_type->save();

    $node = Node::create([
      'type' => 'page',
      'title' => 'Test title first revision',
      'moderation_state' => 'published',
    ]);
    $node->save();

    $revision = clone $node;
    $revision->setNewRevision(TRUE);
    $revision->isDefaultRevision(FALSE);
    $revision->title->value = 'Test title second revision';
    $revision->moderation_state->target_id = 'draft';
    $revision->save();

    $view = Views::getView('test_content_moderation_latest_revision');
    $view->execute();

    // Ensure that the content_revision_tracker contains the right latest
    // revision ID.
    // Also ensure that the relationship back to the revision table contains the
    // right latest revision.
    $expected_result = [
      [
        'nid' => $node->id(),
        'revision_id' => $revision->getRevisionId(),
        'title' => $revision->label(),
        'moderation_state_revision' => 'draft',
        'moderation_state' => 'published',
      ],
    ];
    $this->assertIdenticalResultset($view, $expected_result, ['nid' => 'nid', 'content_revision_tracker_revision_id' => 'revision_id', 'moderation_state_revision' => 'moderation_state_revision', 'moderation_state' => 'moderation_state']);
  }

}
