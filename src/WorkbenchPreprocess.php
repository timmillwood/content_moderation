<?php
/**
 * @file
 * Contains Drupal\workbench_moderation\IsLatestPage.
 */

namespace Drupal\workbench_moderation;

use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Service to determine whether a route is the "Latest version" tab of a node.
 */
class WorkbenchPreprocess {

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   */
  protected $routeMatch;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   Current route match service.
   */
  public function __construct(CurrentRouteMatch $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Wrapper for hook_preprocess_HOOK().
   *
   * @param array $variables
   *   Theme variables to preprocess.
   *
   * @todo I don't like passing $variables by reference here, but I did it
   *       because it matches the way hook_preprocess_HOOK() works. Is this OK?
   */
  public function preprocessNode(array &$variables) {
    $variables['page'] = $variables['page'] || $this->isLatestPage($variables['node']);
  }

  /**
   * Checks whether a route is the "Latest version" tab of a node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A node.
   *
   * @return bool
   *  True if the current route is the latest version tab of the given node.
   */
  public function isLatestPage(\Drupal\node\Entity\Node $node) {
    return $this->routeMatch->getRouteName() == 'entity.node.latest_version'
           && $pageNode = $this->routeMatch->getParameter('node')
           && $pageNode->id == $node->id;
  }
}
