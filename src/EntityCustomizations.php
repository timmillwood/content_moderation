<?php

/**
 * @file
 * Contains Drupal\moderation_state\EntityCustomizations.
 */

namespace Drupal\moderation_state;


use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Service collector facade for entity customizations.
 */
class EntityCustomizations implements EntityCustomizationInterface {

  /**
   * @var EntityCustomizationInterface[]
   *
   * This is a keyed array, with the key being the machine name of an entity
   * such as "node" or "block_content".
   */
  protected $customizationsByEntityType;

  /**
   * @var EntityCustomizationInterface[]
   *
   * This is a keyed array with the key being the fully qualified class name
   * of the bundle definition config entity of the entity to be operated upon.
   * The complexity of that definition highlights the deficiency of the Entity
   * API's built-in introspection capabilities.
   *
   */
  protected $customizationsByBundleType;

  /**
   * @var EntityCustomizationInterface
   */
  protected $defaultCustomization;

  /**
   * Adds a customization service.
   *
   * @param \Drupal\moderation_state\EntityCustomizationInterface $customization
   *   The customization to add.  The entity type it is for is specified by
   *   the service itself.
   *
   * @return static
   */
  public function addEntityCustomization(EntityCustomizationInterface $customization) {
    $this->customizationsByEntityType[$customization->getEntityTypeId()] = $customization;
    $this->customizationsByBundleType[$customization->getEntityBundleClass()] = $customization;

    return $this;
  }

  /**
   * Sets the default customization service.
   *
   * The default service will be used for any entity that does not have its own
   * customizations. That is, at least in theory it's possible for a given
   * entity type to not need a customization service. In practice it's unclear
   * if that will happen.
   *
   * @param \Drupal\moderation_state\EntityCustomizationInterface $customization
   *   The default customization service.
   *
   * @return static
   */
  public function setDefaultCustomization(EntityCustomizationInterface $customization) {
    $this->defaultCustomization = $customization;
    return $this;
  }

  /**
   * Returns the customization object appropriate for the specified entity type.
   *
   * @param string $type
   *   The entity type for which we want the customization set.
   *
   * @return \Drupal\moderation_state\EntityCustomizationInterface
   */
  protected function getCustomizationByType($type) {
    return !empty($this->customizationsByEntityType[$type]) ? $this->customizationsByEntityType[$type] : $this->defaultCustomization;
  }

  protected function getCustomizationByBundleClass($class) {
    return !empty($this->customizationsByBundleType[$class]) ? $this->customizationsByBundleType[$class] : $this->defaultCustomization;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundleClass() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function onPresave(ContentEntityInterface $entity, $published_state) {
    return $this->getCustomizationByType($entity->getEntityTypeId())->onPresave($entity, $published_state);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityModerationFormSubmit(ConfigEntityInterface $bundle) {
    return $this->getCustomizationByBundleClass(get_class($bundle))->onEntityModerationFormSubmit($bundle);
  }

}
