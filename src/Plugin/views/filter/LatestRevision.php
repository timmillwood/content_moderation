<?php

namespace Drupal\workbench_moderation\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Annotation\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter to show only the latest revision of an entity.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("latest_revision")
 */
class LatestRevision extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }


  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  public function adminSummary() { }

  protected function operatorForm(&$form, FormStateInterface $form_state) { }

  public function canExpose() { return FALSE; }

  public function query() {
    $table = $this->ensureMyTable();

    /** @var Sql $query */
    $query = $this->query;

    // This line is probably wrong.
    $tracker_table = $query->ensureTable('workbench_revision_tracker');

    // We need to join against workbench_revision_tracker, ON:
    // - entity_type = The type of entity (get this from the view somewhere?)
    // - entity_id = The ID in the base table.
    // - langcode = The langcode in the base table.
    // - revision_id = The revision ID in the base table.
    // That means we need to, um, get the key IDs from the entity definition.

    $definition = $this->entityTypeManager->getDefinition($this->getEntityType());
    $keys = $definition->getKeys();

    $query->addWhere($query->options['group'], 'entity_type', $this->getEntityType());
    $query->addWhereExpression($query->options['group'], "{$table}.{$keys['id']}={$tracker_table}.entity_id", []);
    $query->addWhereExpression($query->options['group'], "{$table}.{$keys['langcode']}={$tracker_table}.langcode", []);
    $query->addWhereExpression($query->options['group'], "{$table}.{$keys['revision']}={$tracker_table}.revision_id", []);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    return $contexts;
  }

}
