<?php

/**
 * Contains Drupal\moderation_state\EntityTypeInfo.
 */

namespace Drupal\moderation_state;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\moderation_state\Form\EntityModerationForm;
use Drupal\moderation_state\Routing\ModerationRouteProvider;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

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
   * EntityTypeInfo constructor.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service. for form alters.
   * @param \Drupal\moderation_state\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   */
  public function __construct(TranslationInterface $translation, ModerationInformationInterface $moderation_information) {
    $this->stringTranslation = $translation;
    $this->moderationInfo = $moderation_information;
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
      $entity_types[$type_name] = $this->addModeration($type);
    }
  }

  /**
   * Modifies an entity type to include moderation configuration support.
   *
   * That "configuration support" includes a configuration form, a hypermedia
   * link, and a route provider to tie it all together.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $type
   *   The config entity definition to modify.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityTypeInterface
   *   The modified config entity definition.
   */
  protected function addModeration(ConfigEntityTypeInterface $type) {
    if ($type->hasLinkTemplate('edit-form')) {
      $type->setLinkTemplate('moderation-form', $type->getLinkTemplate('edit-form') . '/moderation');
    }

    $type->setFormClass('moderation', EntityModerationForm::class);

    // @todo Core forgot to add a direct way to manipulate route_provider, so
    // we have to do it the sloppy way for now.
    $providers = $type->getHandlerClass('route_provider') ?: [];
    $providers['moderation'] = ModerationRouteProvider::class;
    $type->setHandlerClass('route_provider', $providers);

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
      $this->enforceRevisionsBundleFormAlter($form, $form_state, $form_id);
    }
    else if ($this->moderationInfo->isModeratedEntityForm($form_state->getFormObject())) {
      $this->enforceRevisionsEntityFormAlter($form, $form_state, $form_id);
    }
  }

  /**
   * Alters entity forms to enforce revision handling.
   *
   * Different entity types structure their forms completely differently, so
   * there's seemingly no way to do this globally. Instead, we'll just hard
   * code form changes for core's entity types. Suggestions for a better
   * approach are welcome.
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
  protected function enforceRevisionsEntityFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $entity = $form_state->getFormObject()->getEntity();

    if ($entity instanceof Node) {
      $form['revision']['#disabled'] = TRUE;
      $form['revision']['#default_value'] = TRUE;
      $form['revision']['#description'] = $this->t('Revisions are required.');
    }
    else if ($entity instanceof BlockContent) {

      $form['revision_information']['revision']['#default_value'] = TRUE;
      $form['revision_information']['revision']['#disabled'] = TRUE;
      $form['revision_information']['revision']['#description'] = $this->t('Revisions must be required when moderation is enabled.');
    }
  }

  /**
   * Alters bundle forms to enforce revision handling.
   *
   * Different entity types structure their forms completely differently, so
   * there's seemingly no way to do this globally. Instead, we'll just hard
   * code form changes for core's entity types. Suggestions for a better
   * approach are welcome.
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
  protected function enforceRevisionsBundleFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $entity = $form_state->getFormObject()->getEntity();

    if ($entity instanceof NodeType) {
      $form['workflow']['options']['#default_value']['revision'] = 'revision';
    }
    else if ($entity instanceof BlockContentType) {
      $form['revision']['#default_value'] = 1;
      $form['revision']['#disabled'] = TRUE;
      $form['revision']['#description'] = $this->t('Revisions must be required when moderation is enabled.');
    }
  }

}
