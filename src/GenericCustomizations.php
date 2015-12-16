<?php
/**
 * @file
 * Contains Drupal\moderation_state\GenericCustomizations.
 */

namespace Drupal\moderation_state;


use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Common customizations for most/all entities.
 *
 * This class is intended primarily as a base class.
 */
class GenericCustomizations implements EntityCustomizationInterface {

  use StringTranslationTrait;

  public function __construct(TranslationInterface $translation) {
    $this->stringTranslation = $translation;
  }

  /**
   * {@inheritdoc}
   */
  function getEntityTypeId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function onPresave(ContentEntityInterface $entity, $published_state) {
    // This is *probably* not necessary if configuration is setup correctly,
    // but it can't hurt.
    $entity->setNewRevision(TRUE);

    // A newly-created revision is always the default revision, or else
    // it gets lost.
    $entity->isDefaultRevision($entity->isNew() || $published_state);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityModerationFormSubmit(ConfigEntityInterface $bundle) {
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsEntityFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    return;
  }


  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsBundleFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    return;
  }

}
