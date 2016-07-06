<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\content_moderation\Plugin\Field\ModerationState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

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
    $this->assertEquals('draft', $node->moderation_state->entity->id());

    $node->moderation_state_target_id = 'needs_review';
    $node->save();

    $this->assertEquals('needs_review', $node->moderation_state->entity->id());

    // Change the state without saving the node.
    $content_moderation_state = ContentModerationState::load(1);
    $content_moderation_state->set('moderation_state', 'draft');
    $content_moderation_state->setNewRevision(TRUE);
    $content_moderation_state->save();

    $this->assertEquals('draft', $node->moderation_state->entity->id());

    $node->moderation_state_target_id = 'needs_review';
    $node->save();

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
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->save();
    $this->assertEquals('draft', $node->moderation_state->entity->id());

    // Create a french translation.
    $french_node = $node->addTranslation('fr', ['title' => 'French title']);
    $french_node->save();

    // Reload the node.
    $node = Node::load($node->id());
    $node->moderation_state_target_id = 'needs_review';
    $node->save();

    $this->assertEquals('needs_review', $node->moderation_state->entity->id());

    $french_node = $node->getTranslation('fr');
    $this->assertEquals('draft', $french_node->moderation_state->entity->id());

    // Publish the french node.
    $french_node->moderation_state_target_id = 'published';
    $french_node->save();

    // Reload the node.
    $node = Node::load($node->id());
    $this->assertEquals('needs_review', $node->moderation_state->entity->id());
    $french_node = $node->getTranslation('fr');
    $this->assertEquals('published', $french_node->moderation_state->entity->id());
  }

}
