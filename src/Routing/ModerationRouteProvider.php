<?php

namespace Drupal\moderation_state\Routing;


use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ModerationRouteProvider implements EntityRouteProviderInterface, EntityHandlerInterface  {

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();

    if ($moderation_route = $this->getModerationFormRoute($entity_type)) {
      $entity_type_id = $entity_type->id();
      $collection->add("entity.{$entity_type_id}.moderation", $moderation_route);
    }

    return $collection;
  }

  /**
   * Gets the moderation-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getModerationFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('moderation-form') && $entity_type->getFormClass('moderation')) {
      $entity_type_id = $entity_type->id();

      $route = new Route($entity_type->getLinkTemplate('moderation-form'));

      $route
        ->setDefaults([
          '_entity_form' => "{$entity_type_id}.moderation",
          '_title' => 'Moderation',
          //'_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::editTitle'
        ])
        ->setRequirement('_permission', 'administer moderation state') // @todo Come up with a new permission.
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }
}
