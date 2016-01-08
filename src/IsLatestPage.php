<?php
/**
 * @file
 * Contains Drupal\workbench_moderation\IsLatestPage.
 */

namespace Drupal\workbench_moderation;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\node\Entity\Node;

/**
 * Service to determine whether a route is the "Latest version" tab of a node.
 */
class IsLatestPage {

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
   * Checks whether a route is the "Latest version" tab of a node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A node.
   *
   * @return bool
   *  True if the current route is the latest version tab of the given node.
   */
  public function isLatestPage(Node $node) {
    if ($this->routeMatch->getRouteName() == 'entity.node.latest_version') {
      $pageNode = $this->routeMatch->getParameter('node');
    }
    return (!empty($pageNode) ? $pageNode->id() == $node->id() : FALSE);
  }
}
