<?php
/**
 * @file
 * Contains Drupal\moderation_state\EntityCustomizationInterface.
 */

namespace Drupal\moderation_state;


use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines operations that need to vary by entity type.
 *
 * The existence of this interface and implementations of it are a symptom of
 * design flaws in core's Entity API. Ideally, over time it will get removed
 * as core becomes more standardized.
 */
interface EntityCustomizationInterface {

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to modify.
   * @param bool $published_state
   *   Whether the state being transitioned to is a published state or not.
   */
  public function onPresave(ContentEntityInterface $entity, $published_state);

  /**
   * Operates on the bundle definition that has been marked as moderatable.
   *
   * Note: The values on the EntityModerationForm itself are already saved
   * so do not need to be saved here. If any changes are made to the bundle
   * object here it is this method's responsibility to call save() on it.
   *
   * The most common use case is to force revisions on for this bundle if
   * moderation is enabled. That, sadly, does not have a common API in core.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $bundle
   *   The bundle definition that is being saved.
   * @return mixed
   */
  public function onEntityModerationFormSubmit(ConfigEntityInterface $bundle);

  /**
   * Alters entity forms to enforce revision handling.
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
  public function enforceRevisionsEntityFormAlter(array &$form, FormStateInterface $form_state, $form_id);

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
  public function enforceRevisionsBundleFormAlter(array &$form, FormStateInterface $form_state, $form_id);

}
