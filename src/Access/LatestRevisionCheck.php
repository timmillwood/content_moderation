<?php

/**
 * @file
 * Contains \Drupal\workbench_moderation\Access\LatestRevisionCheck
 */

namespace Drupal\workbench_moderation\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\workbench_moderation\ModerationInformationInterface;
use Symfony\Component\Routing\Route;

class LatestRevisionCheck implements AccessInterface {

  /**
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Constructs a new LatestRevisionCheck.
   *
   * @param \Drupal\workbench_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   */
  public function __construct(ModerationInformationInterface $moderation_information) {
    $this->moderationInfo = $moderation_information;
  }

  /**
   * Checks that there is a forward revision available.
   *
   * This checker assumes the presence of an '_entity_access' requirement key
   * in the same form as used by EntityAccessCheck.
   *
   * @see EntityAccessCheck.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match) {

    // This tab should not show up period unless there's a reason to show it.
    // @todo Do we need any extra cache tags here?
    $entity = $this->loadEntity($route, $route_match);
    return $this->moderationInfo->hasForwardRevision($entity)
      ? AccessResult::allowed()->addCacheableDependency($entity)
      : AccessResult::forbidden()->addCacheableDependency($entity);
  }

  /**
   * Loads the default revision of the entity this route is for.
   *
   * Drupal doesn't offer an entity-generic way to access the entity this
   * route is for, so we need to make a reasonable guess of the entity type
   * from the other information in the route. If that information doesn't lead
   * us to an entity, we cannot continue.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route
   *
   * @return ContentEntityInterface
   *   returns the Entity in question, or NULL if for some reason no entity
   *   is available.
   *
   * @throws \Exception
   */
  protected function loadEntity(Route $route, RouteMatchInterface $route_match) {
    // Split the entity type and the operation. There are several possible
    // properties we could check for this information.
    $requirement = $route->getRequirement('_entity_access')
      ?: $route_match->getParameter('_entity_view')
      ?: $route_match->getParameter('_entity_form');
    list($entity_type, $operation) = explode('.', $requirement);
    // If there is valid entity of the given entity type, check its access.
    $parameters = $route_match->getParameters();
    if ($parameters->has($entity_type)) {
      $entity = $parameters->get($entity_type);
      if ($entity instanceof EntityInterface) {
        return $entity;
      }
    }

    throw new \Exception('Fail');
  }
}
