<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\content_moderation\Entity\ModerationState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;

/**
 * Tests links between a content entity and a content_moderation_state entity.
 *
 * @group content_moderation
 */
class ContentModerationStateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'content_moderation', 'user', 'system', 'language', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');
  }

  /**
   */
  public function testBasicModeration() {
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->setThirdPartySetting('content_moderation', 'enabled', TRUE);
    $node_type->setThirdPartySetting('content_moderation', 'allowed_moderation_states', ['draft', 'needs_review', 'published']);
    $node_type->setThirdPartySetting('content_moderation', 'default_moderation_state', 'draft');
    $node_type->save();
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->save();
    $node = $this->reloadNode($node);
    $this->assertEquals('draft', $node->moderation_state->entity->id());

    $node->moderation_state->target_id = 'needs_review';
    $node->save();

    $node = $this->reloadNode($node);
    $this->assertEquals('needs_review', $node->moderation_state->entity->id());

    $published = ModerationState::load('published');
    $node->moderation_state->entity = $published;
    $node->save();

    $node = $this->reloadNode($node);
    $this->assertEquals('published', $node->moderation_state->entity->id());

    // Change the state without saving the node.
    $content_moderation_state = ContentModerationState::load(1);
    $content_moderation_state->set('moderation_state', 'draft');
    $content_moderation_state->setNewRevision(TRUE);
    $content_moderation_state->save();

    $node = $this->reloadNode($node);
    $this->assertEquals('draft', $node->moderation_state->entity->id());

    $node->moderation_state->target_id = 'needs_review';
    $node->save();

    $node = $this->reloadNode($node);
    $this->assertEquals('needs_review', $node->moderation_state->entity->id());
  }


  /**
   */
  public function testMultilingualModeration() {
    // Enable French
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->setThirdPartySetting('content_moderation', 'enabled', TRUE);
    $node_type->setThirdPartySetting('content_moderation', 'allowed_moderation_states', ['draft', 'needs_review', 'published']);
    $node_type->setThirdPartySetting('content_moderation', 'default_moderation_state', 'draft');
    $node_type->save();
    $english_node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $english_node
      ->setPublished(FALSE)
      ->save();
    $this->assertEquals('draft', $english_node->moderation_state->entity->id());
    $this->assertFalse($english_node->isPublished());

    // Create a french translation.
    $french_node = $english_node->addTranslation('fr', ['title' => 'French title']);
    $french_node->setPublished(FALSE);
    $french_node->save();
    $this->assertEquals('draft', $french_node->moderation_state->entity->id());
    $this->assertFalse($french_node->isPublished());

    // Move English node to needs review.
    $english_node = $this->reloadNode($english_node);
    $english_node->moderation_state->target_id = 'needs_review';
    $english_node->save();
    $this->assertEquals('needs_review', $english_node->moderation_state->entity->id());

    // French node should still be in draft.
    $french_node = $this->reloadNode($english_node)->getTranslation('fr');
    $this->assertEquals('draft', $french_node->moderation_state->entity->id());

    // Publish the French node.
    $french_node->moderation_state->target_id = 'published';
    $french_node->save();
    $this->assertTrue($french_node->isPublished());
    $this->assertEquals('published', $french_node->moderation_state->entity->id());
    $this->assertTrue($french_node->isPublished());
    $english_node = $this->reloadNode($french_node)->getTranslation('en');
    $this->assertEquals('needs_review', $english_node->moderation_state->entity->id());

    // Publish the English node.
    $english_node->moderation_state->target_id = 'published';
    $english_node->save();
    $this->assertTrue($english_node->isPublished());

    // Move the French node back to draft.
    $french_node = $this->reloadNode($english_node)->getTranslation('fr');
    $this->assertTrue($french_node->isPublished());
    $french_node->moderation_state->target_id = 'draft';
    $french_node->save();
    $this->assertFalse($french_node->isPublished());
    $this->assertTrue($french_node->getTranslation('en')->isPublished());

    // Republish the French node.
    $french_node->moderation_state->target_id = 'published';
    $french_node->save();

    // Change the state without saving the node.
    $english_node = $this->reloadNode($french_node);
    ContentModerationState::updateOrCreateFromEntity($english_node, 'draft');

    // $node = Node::load($node->id());
    $this->assertEquals('draft', $english_node->moderation_state->entity->id());
    $french_node = $this->reloadNode($english_node)->getTranslation('fr');
    $this->assertEquals('published', $french_node->moderation_state->entity->id());

    // This should unpublish the French node.
    ContentModerationState::updateOrCreateFromEntity($french_node, 'needs_review');

    $english_node = $this->reloadNode($french_node);
    $this->assertEquals('draft', $english_node->moderation_state->entity->id());
    $french_node = $this->reloadNode($english_node)->getTranslation('fr');
    $this->assertEquals('needs_review', $french_node->moderation_state->entity->id());
    // @todo this doesn't work.
    $this->assertFalse($french_node->isPublished());
  }

  /**
   * Reloads the node after clearing the static cache.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return \Drupal\node\NodeInterface
   */
  protected function reloadNode(NodeInterface $node) {
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    return Node::load($node->id());
  }

}
