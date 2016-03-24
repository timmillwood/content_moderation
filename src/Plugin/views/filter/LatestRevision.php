<?php

namespace Drupal\workbench_moderation\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Annotation\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter to show only the latest revision of an entity.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("latest_revision")
 */
class LatestRevision extends FilterPluginBase {

  public function adminSummary() { }

  protected function operatorForm(&$form, FormStateInterface $form_state) { }

  public function canExpose() { return FALSE; }

  public function query() {
    $table = $this->ensureMyTable();

    $query = $this->query;



  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    return $contexts;
  }

}
