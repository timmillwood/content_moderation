<?php

namespace Drupal\moderation_state\Routing;


use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class WorkflowRouteProvider implements EntityRouteProviderInterface, EntityHandlerInterface  {

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static();
  }

  /**
   * @inheritDoc
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();

    if ($workflow_route = $this->getWorkflowFormRoute($entity_type)) {
      $entity_type_id = $entity_type->id();
      $collection->add("entity.{$entity_type_id}.workflow", $workflow_route);
    }

    return $collection;
  }

  /**
   * Gets the workflow-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getWorkflowFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('workflow-form') && $entity_type->getFormClass('workflow')) {
      $entity_type_id = $entity_type->id();

      $route = new Route($entity_type->getLinkTemplate('workflow-form'));

      $route
        ->setDefaults([
          '_entity_form' => "{$entity_type_id}.workflow",
          '_title' => 'Workflow',
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
