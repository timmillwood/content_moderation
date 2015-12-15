<?php

/**
 * @file
 * Contains \Drupal\moderation_state\Plugin\MenuEditTab.
 */

namespace Drupal\moderation_state\Plugin\Menu;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\moderation_state\ModerationInformation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for making the edit tab use 'Edit draft' or 'New draft'
 */
class EditTab extends LocalTaskDefault implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The moderatio information service.
   *
   * @var \Drupal\moderation_state\ModerationInformation
   */
  protected $moderationInformation;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * Constructs a new EditTab object.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation service.
   * @param \Drupal\moderation_state\ModerationInformation $moderation_information
   *   The moderation information.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $string_translation, ModerationInformation $moderation_information) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->stringTranslation = $string_translation;
    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('moderation_state.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    // Override the node here with the latest revision.
    $this->entity = $route_match->getParameter($this->pluginDefinition['entity_type_id']);
    return parent::getRouteParameters($route_match);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    if (!$this->moderationInformation->isModeratableEntity($this->entity)) {
      // Moderation isn't enabled.
      return parent::getTitle();
    }

    // @todo write a test for this.
    /** @var ContentEntityInterface $latest */
    $latest = $this->moderationInformation->getLatestRevision($this->entity->getEntityTypeId(), $this->entity->id());
    if ($this->entity->getRevisionId() === $latest->getRevisionId() && $this->entity->isDefaultRevision() && $this->entity->moderation_state->entity && $this->entity->moderation_state->entity->isPublishedState()) {
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
    $tags = array_merge($tags, $this->entity->getCacheTags());
    $tags[] = $this->entity->getEntityType()->getBundleEntityType() . ':' . $this->entity->bundle();
    return $tags;
  }

}
