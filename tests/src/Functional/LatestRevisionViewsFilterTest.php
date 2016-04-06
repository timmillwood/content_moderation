<?php

namespace Drupal\Tests\workbench_moderation\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\BrowserTestBase;

/**
 * Tests the "Latest Revision" views filter.
 *
 * @group workbench_moderation
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 */
class LatestRevisionViewsFilterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workbench_moderation_test_views', 'workbench_moderation', 'node', 'views', 'options', 'user', 'system'];

  /**
   *
   */
  public function testViewShowsCorrectNids() {
    $this->createNodeType('Test', 'test');

    $permissions = [
      'access content',
      'view all revisions',
    ];
    $editor1 = $this->drupalCreateUser($permissions);

    $this->drupalLogin($editor1);

    // Make a node that is only ever in Draft.

    /** @var Node $node_1 */
    $node_1 = Node::create([
      'type' => 'test',
      'title' => 'Node 1 - Rev 1',
      'uid' => $editor1->id(),
    ]);
    $node_1->moderation_state->target_id = 'draft';
    $node_1->save();

    // Make a node that is in Draft, then Published.

    /** @var Node $node_2 */
    $node_2 = Node::create([
      'type' => 'test',
      'title' => 'Node 2 - Rev 1',
      'uid' => $editor1->id(),
    ]);
    $node_2->moderation_state->target_id = 'draft';
    $node_2->save();

    $node_2->setTitle('Node 2 - Rev 2');
    $node_2->moderation_state->target_id = 'published';
    $node_2->save();

    // Make a node that is in Draft, then Published, then Draft.

    /** @var Node $node_3 */
    $node_3 = Node::create([
      'type' => 'test',
      'title' => 'Node 3 - Rev 1',
      'uid' => $editor1->id(),
    ]);
    $node_3->moderation_state->target_id = 'draft';
    $node_3->save();

    $node_3->setTitle('Node 3 - Rev 2');
    $node_3->moderation_state->target_id = 'published';
    $node_3->save();

    $node_3->setTitle('Node 3 - Rev 3');
    $node_3->moderation_state->target_id = 'draft';
    $node_3->save();


    // Now show the View, and confirm that only the correct titles are showing.

    $this->drupalGet('/latest');
    $page = $this->getSession()->getPage();
    $this->assertEquals(200, $this->getSession()->getStatusCode());
    $this->assertTrue($page->hasContent('Node 1 - Rev 1'));
    $this->assertTrue($page->hasContent('Node 2 - Rev 2'));
    $this->assertTrue($page->hasContent('Node 3 - Rev 3'));
    $this->assertFalse($page->hasContent('Node 2 - Rev 1'));
    $this->assertFalse($page->hasContent('Node 3 - Rev 1'));
    $this->assertFalse($page->hasContent('Node 3 - Rev 2'));
  }

  /**
   * Creates a new node type.
   *
   * @param string $label
   *   The human-readable label of the type to create.
   * @param string $machine_name
   *   The machine name of the type to create.
   */
  protected function createNodeType($label, $machine_name) {
    /** @var NodeType $node_type */
    $node_type = NodeType::create([
      'type' => $machine_name,
      'label' => $label,
    ]);
    $node_type->setThirdPartySetting('workbench_moderation', 'enabled', TRUE);
    $node_type->save();
  }
}
