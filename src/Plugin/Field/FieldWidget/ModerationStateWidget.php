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
    $user = \Drupal::currentUser();
    $options = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getOptionsProvider($this->column, $items->getEntity())
      ->getSettableOptions($user);

    $default = $items->get($delta)->target_id;
    // @todo write a test for this.
    // @todo inject this.
    $from = \Drupal::entityQuery('moderation_state_transition')
      ->condition('stateFrom', $items->get($delta)->getValue())
      ->execute();
    // Can always keep this one as is.
    $to[$default] = $default;
    if ($from) {
      // @todo inject this.
      foreach (\Drupal::entityManager()
                 ->getStorage('moderation_state_transition')
                 ->loadMultiple($from) as $id => $transition) {
        if ($user->hasPermission('use ' . $id . 'transition')) {
          $to[$transition->getToState()] = $transition->getToState();
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
      '#published' => ModerationState::load($default)->isPublished(),
    ];
    if ($user->hasPermission('administer nodes') && count($options)) {
      // Don't show in sidebar/body.
      $element['#access'] = FALSE;
      // Use the dropbutton.
      // We have to do this in a form alter because process callbacks cannot
      // add elements that influence input values.
      // @see moderation_state_form_node_form_alter
      $form_state->setTemporaryValue('moderation_state_dropbutton', $element);
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
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return parent::isApplicable($field_definition) && $field_definition->getName() === 'moderation_state' && $field_definition->getTargetEntityTypeId() === 'node';
  }


}
