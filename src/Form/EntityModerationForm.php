<?php
/**
 * @file
 * Contains EntityModerationForm.php
 */


namespace Drupal\workbench_moderation\Form;


use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_moderation\Entity\ModerationState;
use Drupal\workbench_moderation\ModerationInformationInterface;
use Drupal\workbench_moderation\StateTransitionValidation;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityModerationForm extends FormBase {

  /**
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * @var \Drupal\workbench_moderation\StateTransitionValidation
   */
  protected $validation;

  public function __construct(ModerationInformationInterface $moderation_info, StateTransitionValidation $validation) {
    $this->moderationInfo = $moderation_info;
    $this->validation = $validation;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workbench_moderation.moderation_information'),
      $container->get('workbench_moderation.state_transition_validation')
    );
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'workbench_moderation_entity_moderation_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContentEntityInterface $entity = NULL) {
    $target_states = $this->validation->getValidTransitionTargets($entity, $this->currentUser());

    $target_states = array_map(function(ModerationState $state) {
      return $state->label();
    }, $target_states);

    /** @var ModerationState $current_state */
    $current_state = $entity->moderation_state->entity;

    if ($current_state) {
      $form['current'] = [
        '#type' => 'markup',
        '#options' => $target_states,
        '#value' => $this->t('Current status: %state', ['%state' => $current_state->label()]),
      ];
    }

    $target_states = [];

    $form_state->set('entity', $entity);

    $form['new_state'] = [
      '#type' => 'select',
      '#title' => $this->t('New state'),
      '#options' => $target_states,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Set'),
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $form_state->get('entity');

  }


}
