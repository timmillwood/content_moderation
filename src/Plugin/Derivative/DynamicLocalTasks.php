<?php

/**
 * @file Contains Drupal\moderation_state\Plugin\Derivative\DynamicLocalTasks.
 */

namespace Drupal\moderation_state\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DynamicLocalTasks extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypes;

  /**
   * Creates an FieldUiLocalTask object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_types, TranslationInterface $string_translation) {
    $this->entityTypes = $entity_types;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo I don't know why this doesn't work. I suspect it's because
   * I've no idea what base_route is supposed to be or how it works. Help wanted.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = array();

    foreach ($this->workflowEntities() as $entity_type_id => $entity_type) {
      $this->derivatives["$entity_type_id.devel_tab"] = array(
        'route_name' => "entity.$entity_type_id.workflow",
        'title' => $this->t('Manage workflow'),
        'base_route' => "moderation_state.entities:$entity_type_id.workflow",
        'weight' => 30,
      );
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

  /**
   * Returns an iterable of the entities to which to attach local tasks.
   *
   * @return \Generator
   *   A generator that produces just the entities we care about.
   */
  protected function workflowEntities() {
    $entity_types = $this->entityTypes->getDefinitions();
    foreach ($entity_types as $type_name => $type) {
      if ($type instanceof ConfigEntityTypeInterface) {
        if ($type->get('bundle_of')) {
          if ($entity_types[$type->get('bundle_of')]->isRevisionable()) {
            yield $type_name => $type;
          }
        }
      }
    }
  }
}
