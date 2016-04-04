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

    $definition = $this->entityTypeManager->getDefinition($this->getEntityType());
    $keys = $definition->getKeys();

    $definition = [
      'table' => 'workbench_revision_tracker',
      'type' => 'INNER',
      'field' => 'entity_id',
      'left_table' => $table,
      'left_field' => $keys['id'],
      'extra' => [
        ['left_field' => $keys['langcode'], 'field' => 'langcode'],
        ['left_field' => $keys['revision'], 'field' => 'revision_id'],
        ['field' => 'entity_type', 'value' => $this->getEntityType()],
      ],
    ];

    $join = \Drupal::service('plugin.manager.views.join')->createInstance('standard', $definition);

    $query->ensureTable('workbench_revision_tracker', $this->relationship, $join);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    return $contexts;
  }

}
