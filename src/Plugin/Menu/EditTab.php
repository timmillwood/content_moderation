<?php

/**
 * @file
 * Contains \Drupal\moderation_state\Plugin\MenuEditTab.
 */

namespace Drupal\moderation_state\Plugin\Menu;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeStorageInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for making the edit tab use 'Edit draft' or 'New draft'
 */
class EditTab extends LocalTaskDefault implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Node storage handler.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Node type storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeTypeStorage;

  /**
   * Node for route.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('entity_type.manager')->getStorage('node_type')
    );
  }

  /**
   * Constructs a new EditTab object.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\node\NodeStorageInterface $node_storage
   *   Node storage handler.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_type_storage
   *   Node type storage handler
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, NodeStorageInterface $node_storage, EntityStorageInterface $node_type_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nodeStorage = $node_storage;
    $this->nodeTypeStorage = $node_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    // Override the node here with the latest revision.
    $this->node = $route_match->getParameter('node');
    return parent::getRouteParameters($route_match);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    /* @var NodeTypeInterface $node_type */
    // @todo write a test for this.
    $node_type = $this->nodeTypeStorage->load($this->node->bundle());
    if (!$node_type->getThirdPartySetting('moderation_state', 'enabled', FALSE)) {
      // Moderation isn't enabled.
      return parent::getTitle();
    }
    $revision_ids = $this->nodeStorage->revisionIds($this->node);
    sort($revision_ids);
    $latest = end($revision_ids);
    if ($this->node->getRevisionId() === $latest && $this->node->isDefaultRevision() && $this->node->moderation_state->entity && $this->node->moderation_state->entity->isPublishedState()) {
      // @todo write a test for this.
      return $this->t('New draft');
    }
    else {
      // @todo write a test for this:
      return $this->t('Edit draft');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // @todo write a test for this.
    $tags = parent::getCacheTags();
    // Tab changes if node or node-type is modified.
    $tags[] = 'node:' . $this->node->id();
    $tags[] = 'node_type:' . $this->node->bundle();
    return $tags;
  }

}
