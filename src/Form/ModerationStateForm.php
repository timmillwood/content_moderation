<?php

/**
 * @file
 * Contains \Drupal\moderation_state\Form\ModerationStateForm.
 */

namespace Drupal\moderation_state\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ModerationStateForm.
 *
 * @package Drupal\moderation_state\Form
 */
class ModerationStateForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\moderation_state\ModerationStateInterface $moderation_state */
    $moderation_state = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $moderation_state->label(),
      '#description' => $this->t("Label for the Moderation state."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $moderation_state->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\moderation_state\Entity\ModerationState::load',
      ),
      '#disabled' => !$moderation_state->isNew(),
    );

    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#description' => $this->t('When content reaches this state it should be published'),
      '#default_value' => $moderation_state->isPublishedState(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $moderation_state = $this->entity;
    $status = $moderation_state->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Moderation state.', [
          '%label' => $moderation_state->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Moderation state.', [
          '%label' => $moderation_state->label(),
        ]));
    }
    $form_state->setRedirectUrl($moderation_state->urlInfo('collection'));
  }

}
