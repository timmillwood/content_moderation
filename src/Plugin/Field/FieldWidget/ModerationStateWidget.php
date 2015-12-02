<?php

/**
 * @file
 * Contains \Drupal\moderation_state\Plugin\Field\FieldWidget\ModerationStateWidget.
 */

namespace Drupal\moderation_state\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\moderation_state\Entity\ModerationState;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class ModerationStateWidget extends OptionsSelectWidget implements ContainerFactoryPluginInterface {

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Node type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeTypeStorage;

  /**
   * Moderation state transition entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $moderationStateTransitionEntityQuery;

  /**
   * Moderation state storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $moderationStateStorage;
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('node_type'),
      $container->get('entity_type.manager')->getStorage('moderation_state'),
      $container->get('entity_type.manager')->getStorage('moderation_state_transition'),
      $container->get('entity.query')->get('moderation_state_transition', 'AND')
    );
  }

  /**
   * Constructs a new ModerationStateWidget object.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param array $settings
   *   Field settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_type_storage
   *   Node type storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $moderation_state_storage
   *   Moderation state storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $moderation_state_transition_storage
   *   Moderation state transition storage.
   * @param \Drupal\Core\Entity\Query\QueryInterface $entity_query
   *   Moderation transation entity query service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $node_type_storage, EntityStorageInterface $moderation_state_storage, EntityStorageInterface $moderation_state_transition_storage, QueryInterface $entity_query) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->nodeTypeStorage = $node_type_storage;
    $this->moderationStateTransitionEntityQuery = $entity_query;
    $this->moderationStateTransitionStorage = $moderation_state_transition_storage;
    $this->moderationStateStorage = $moderation_state_storage;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $node = $items->getEntity();
    /* @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $this->nodeTypeStorage->load($node->bundle());
    if (!$node_type->getThirdPartySetting('moderation_state', 'enabled', FALSE)) {
      // @todo write a test for this.
      return $element + ['#access' => FALSE];
    }
    $options = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getOptionsProvider($this->column, $node)
      ->getSettableOptions($this->currentUser);

    $default = $items->get($delta)->target_id ?: $node_type->getThirdPartySetting('moderation_state', 'default_moderation_state', FALSE);
    /** @var \Drupal\moderation_state\ModerationStateInterface $default_state */
    $default_state = ModerationState::load($default);
    if (!$default || !$default_state) {
      throw new \UnexpectedValueException(sprintf('The %s node type has an invalid moderation state configuration, moderation states are enabled but no default is set.', $node_type->label()));
    }
    // @todo write a test for this.
    $from = $this->moderationStateTransitionEntityQuery
      ->condition('stateFrom', $default)
      ->execute();
    // Can always keep this one as is.
    $to[$default] = $default;
    // @todo write a test for this.
    $allowed = $node_type->getThirdPartySetting('moderation_state', 'allowed_moderation_states', []);
    if ($from) {
      /* @var \Drupal\moderation_state\ModerationStateTransitionInterface $transition */
      foreach ($this->moderationStateTransitionStorage->loadMultiple($from) as $id => $transition) {
        $to_state = $transition->getToState();
        if ($this->currentUser->hasPermission('use ' . $id . 'transition') && in_array($to_state, $allowed, TRUE)) {
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
      '#published' => $default ? $default_state->isPublishedState() : FALSE,
    ];
    if ($this->currentUser->hasPermission('administer nodes') && count($options)) {
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
