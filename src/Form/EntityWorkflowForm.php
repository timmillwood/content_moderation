<?php

namespace Drupal\moderation_state\Form;


use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\moderation_state\Entity\ModerationState;
use Drupal\node\NodeTypeInterface;

class EntityWorkflowForm extends EntityForm {

  /**
   * {@inheritdoc}
   *
   * We need to blank out the base form ID so that poorly written form alters
   * that use the base form ID to target both add and edit forms don't pick
   * up our form. This should be fixed in core.
   */
  public function getBaseFormId() {
    return NULL;
  }


  public function form(array $form, FormStateInterface $form_state) {
    /** @var ConfigEntityTypeInterface $entity */
    $entity = $this->entity;


    /* @var NodeTypeInterface $node_type */
    $node_type = $form_state->getFormObject()->getEntity();
    $form['workflow']['enable_moderation_state'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable moderation states.'),
      '#description' => t('Content of this type must transition through moderation states in order to be published.'),
      '#default_value' => $node_type->getThirdPartySetting('moderation_state', 'enabled', FALSE),
    ];
    $states = \Drupal::entityTypeManager()->getStorage('moderation_state')->loadMultiple();
    $options = [];
    /** @var ModerationState $state */
    foreach ($states as $key => $state) {
      $options[$key] = $state->label() . ' ' . ($state->isPublishedState() ? t('(published)') : t('(non-published)'));
    }
    $form['workflow']['allowed_moderation_states'] = [
      '#type' => 'checkboxes',
      '#title' => t('Allowed moderation states.'),
      '#description' => t('The allowed moderation states this content-type can be assigned. You must select at least one published and one non-published state.'),
      '#default_value' => $node_type->getThirdPartySetting('moderation_state', 'allowed_moderation_states', []),
      '#options' => $options,
      '#states' => [
        'visible' => [
          ':input[name=enable_moderation_state]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['workflow']['default_moderation_state'] = [
      '#type' => 'select',
      '#title' => t('Default moderation state'),
      '#empty_option' => t('-- Select --'),
      '#options' => $options,
      '#description' => t('Select the moderation state for new content'),
      '#default_value' => $node_type->getThirdPartySetting('moderation_state', 'default_moderation_state', ''),
      '#states' => [
        'visible' => [
          ':input[name=enable_moderation_state]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['#entity_builders'][] = 'moderation_state_node_type_form_builder';

    return parent::form($form, $form_state); // TODO: Change the autogenerated stub
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @todo write a test for this.
    if ($form_state->getValue('enable_moderation_state')) {
      $states = \Drupal::entityTypeManager()->getStorage('moderation_state')->loadMultiple();
      $published = FALSE;
      $non_published = TRUE;
      $allowed = array_keys(array_filter($form_state->getValue('allowed_moderation_states')));
      foreach ($allowed as $state_id) {
        /** @var ModerationState $state */
        $state = $states[$state_id];
        if ($state->isPublishedState()) {
          $published = TRUE;
        }
        else {
          $non_published = TRUE;
        }
      }
      if (!$published || !$non_published) {
        $form_state->setErrorByName('allowed_moderation_states', t('You must select at least one published moderation and one non-published state.'));
      }
      if (($default = $form_state->getValue('default_moderation_state')) && !empty($default)) {
        if (!in_array($default, $allowed, TRUE)) {
          $form_state->setErrorByName('default_moderation_state', t('The default moderation state must be one of the allowed states.'));
        }
      }
      else {
        $form_state->setErrorByName('default_moderation_state', t('You must select a default moderation state.'));
      }
    }
  }
}
