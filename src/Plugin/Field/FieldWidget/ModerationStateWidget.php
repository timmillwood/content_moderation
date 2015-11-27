<?php

/**
 * @file
 * Contains \Drupal\moderation_state\Plugin\Field\FieldWidget\ModerationStateWidget.
 */

namespace Drupal\moderation_state\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\moderation_state\Entity\ModerationState;
use Drupal\node\NodeInterface;

/**
 * Plugin implementation of the 'moderation_state_default' widget.
 *
 * @FieldWidget(
 *   id = "moderation_state_default",
 *   label = @Translation("Moderation state"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ModerationStateWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // @todo inject this.
    $node_type = \Drupal::entityTypeManager()->getStorage('node_type')->load($items->getEntity()->bundle());
    if (!$node_type->getThirdPartySetting('moderation_state', 'enabled', FALSE)) {
      // @todo write a test for this.
      return $element + ['#access' => FALSE];
    }
      // @todo inject this.
    $user = \Drupal::currentUser();
    $options = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getOptionsProvider($this->column, $items->getEntity())
      ->getSettableOptions($user);

    // @todo make the starting state configurable.
    $default = $items->get($delta)->target_id ?: 'draft';
    // @todo write a test for this.
    // @todo inject this.
    $from = \Drupal::entityQuery('moderation_state_transition')
      ->condition('stateFrom', $default)
      ->execute();
    // Can always keep this one as is.
    $to[$default] = $default;
    // @todo write a test for this.
    $allowed = $node_type->getThirdPartySetting('moderation_state', 'allowed_moderation_states', []);
    if ($from) {
      // @todo inject this.
      foreach (\Drupal::entityManager()
                 ->getStorage('moderation_state_transition')
                 ->loadMultiple($from) as $id => $transition) {
        $to_state = $transition->getToState();
        if ($user->hasPermission('use ' . $id . 'transition') && in_array($to_state, $allowed, TRUE)) {
          $to[$to_state] = $to_state;
        }
      }
    }
    $options = array_intersect_key($options, $to);

    // @todo write a test for this.
    $element += [
      '#access' => count($options),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default,
      '#published' => $default ? ModerationState::load($default)->isPublished() : FALSE,
    ];
    if ($user->hasPermission('administer nodes') && count($options)) {
      // Use the dropbutton.
      $element['#process'][] = [$this, 'processActions'];
      // Don't show in sidebar/body.
      $element['#access'] = FALSE;
    }
    else {
      // Place the field as a details element in the advanced tab-set in e.g.
      // the sidebar.
      $element = [
        '#type' => 'details',
        '#group' => 'advanced',
        '#open' => TRUE,
        '#weight' => -10,
        '#title' => t('Moderation state'),
        'target_id' => $element,
      ];
    }

    return $element;
  }

  /**
   * Entity builder updating the node moderation state with the submitted value.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\node\NodeInterface $node
   *   The node updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function updateStatus($entity_type_id, NodeInterface $node, array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (isset($element['#moderation_state'])) {
      $node->moderation_state->target_id = $element['#moderation_state'];
    }
  }

  /**
   * Process callback to alter action buttons.
   */
  public function processActions($element, FormStateInterface $form_state, array &$form) {
    $default_button = $form['actions']['submit'];
    $default_button['#access'] = TRUE;
    $options = $element['#options'];
    foreach ($options as $id => $label) {
      if ($id === $element['#default_value']) {
        if ($element['#published']) {
          $button = [
            '#value' => $this->t('Save and keep @state', ['@state' => $label]),
            '#dropbutton' => 'save',
            '#moderation_state' => $id,
            '#weight' => -10,
          ];
        }
        else {
          $button = [
            '#value' => $this->t('Save as @state', ['@state' => $label]),
            '#dropbutton' => 'save',
            '#moderation_state' => $id,
            '#weight' => -10,
          ];
        }
      }
      else {
        // @todo write a test for this.
        $button = [
          '#value' => $this->t('Save and @type @state', [
            '@state' => $label,
            '@type' => $element['#published'] ? $this->t('create new revision in') : $this->t('transition to'),
          ]),
          '#dropbutton' => 'save',
          '#moderation_state' => $id,
        ];
      }
      $form['actions']['moderation_state_' . $id] = $button + $default_button;
    }
    foreach (['publish', 'unpublish'] as $key) {
      $form['actions'][$key]['#access'] = FALSE;
      unset($form['actions'][$key]['#dropbutton']);
    }
    $form['#entity_builders']['update_moderation_state'] = [$this, 'updateStatus'];
    return $element;
  }
  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return parent::isApplicable($field_definition) && $field_definition->getName() === 'moderation_state' && $field_definition->getTargetEntityTypeId() === 'node';
  }


}
