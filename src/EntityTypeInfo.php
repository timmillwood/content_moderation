<?php

/**
 * Contains Drupal\moderation_state\EntityTypeInfo.
 */

namespace Drupal\moderation_state;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\moderation_state\Form\EntityModerationForm;
use Drupal\moderation_state\Routing\ModerationRouteProvider;
use Drupal\moderation_state\Entity\Handler\NodeModerationHandler;
use Drupal\moderation_state\Entity\Handler\BlockContentModerationHandler;
use Drupal\moderation_state\Entity\Handler\ModerationHandler;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Service class for manipulating entity type information.
 */
class EntityTypeInfo {

  use StringTranslationTrait;

  /**
   * The moderation information service.
   *
   * @var \Drupal\moderation_state\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A keyed array of custom moderation handlers for given entity types.
   * Any entity not specified will use a common default.
   *
   * @var array
   */
  protected $moderationHandlers = [
    'node' => NodeModerationHandler::class,
    'block_content' => BlockContentModerationHandler::class,
  ];

  /**
   * EntityTypeInfo constructor.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service. for form alters.
   * @param \Drupal\moderation_state\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(TranslationInterface $translation, ModerationInformationInterface $moderation_information, EntityTypeManagerInterface $entity_type_manager) {
    $this->stringTranslation = $translation;
    $this->moderationInfo = $moderation_information;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Adds Moderation configuration to appropriate entity types.
   *
   * This is an alter hook bridge.
   *
   * @param EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter().
   */
  public function entityTypeAlter(array &$entity_types) {
    foreach ($this->moderationInfo->selectRevisionableEntityTypes($entity_types) as $type_name => $type) {
      $entity_types[$type_name] = $this->addModerationToEntityType($type);
      $entity_types[$type->get('bundle_of')] = $this->addModerationToEntity($entity_types[$type->get('bundle_of')]);
    }
  }

  /**
   * Modifies an entity definition to include moderation support.
   *
   * This primarily just means an extra handler. A Generic one is provided,
   * but individual entity types can provide their own as appropriate.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $type
   *   The content entity definition to modify.
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface
   *   The modified content entity definition.
   */
  protected function addModerationToEntity(ContentEntityTypeInterface $type) {
    if (!$type->getHandlerClass('moderation')) {
      $handler_class = !empty($this->moderationHandlers[$type->id()]) ? $this->moderationHandlers[$type->id()] : ModerationHandler::class;
      $type->setHandlerClass('moderation', $handler_class);
    }

    return $type;
  }

  /**
   * Modifies an entity type definition to include moderation configuration support.
   *
   * That "configuration support" includes a configuration form, a hypermedia
   * link, and a route provider to tie it all together. There's also a
   * moderation handler for per-entity-type variation.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $type
   *   The config entity definition to modify.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityTypeInterface
   *   The modified config entity definition.
   */
  protected function addModerationToEntityType(ConfigEntityTypeInterface $type) {
    if ($type->hasLinkTemplate('edit-form') && !$type->hasLinkTemplate('moderation-form')) {
      $type->setLinkTemplate('moderation-form', $type->getLinkTemplate('edit-form') . '/moderation');
    }

    if (!$type->getFormClass('moderation')) {
      $type->setFormClass('moderation', EntityModerationForm::class);
    }

    // @todo Core forgot to add a direct way to manipulate route_provider, so
    // we have to do it the sloppy way for now.
    $providers = $type->getHandlerClass('route_provider') ?: [];
    if (empty($providers['moderation'])) {
      $providers['moderation'] = ModerationRouteProvider::class;
      $type->setHandlerClass('route_provider', $providers);
    }

    return $type;
  }

  /**
   * Adds an operation on bundles that should have a Moderation form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation().
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    $type = $entity->getEntityType();

    if ($this->moderationInfo->isBundleForModeratableEntity($entity)) {
      $operations['manage-moderation'] = [
        'title' => t('Manage moderation'),
        'weight' => 27,
        'url' => Url::fromRoute("entity.{$type->id()}.moderation", [$entity->getEntityTypeId() => $entity->id()]),
      ];
    }

    return $operations;
  }

  /**
   * Force moderatable bundles to have a moderation_state field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $fields
   *   The array of bundle field definitions.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $bundle
   *   The bundle.
   *
   * @see hook_entity_bundle_field_info_alter();
   */
  public function entityBundleFieldInfoAlter(&$fields, EntityTypeInterface $entity_type, $bundle) {
    if ($this->moderationInfo->isModeratableBundle($entity_type, $bundle) && !empty($fields['moderation_state'])) {
      $fields['moderation_state']->addConstraint('ModerationState', []);
    }
  }

  /**
   * Alters bundle forms to enforce revision handling.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form id.
   *
   * @see hook_form_alter()
   */
  public function bundleFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    if ($this->moderationInfo->isRevisionableBundleForm($form_state->getFormObject())) {
      /* @var ConfigEntityTypeInterface $bundle */
      $bundle = $form_state->getFormObject()->getEntity();

      $this->entityTypeManager->getHandler($bundle->getEntityType()->getBundleOf(), 'moderation')->enforceRevisionsBundleFormAlter($form, $form_state, $form_id);
    }
    else if ($this->moderationInfo->isModeratedEntityForm($form_state->getFormObject())) {
      /* @var ContentEntityInterface $entity */
      $entity = $form_state->getFormObject()->getEntity();

      $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'moderation')->enforceRevisionsEntityFormAlter($form, $form_state, $form_id);
    }
  }
}
