<?php

namespace Drupal\rules\Form;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rules\Engine\ExpressionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base form for rules add and edit forms.
 */
abstract class RulesComponentFormBase extends EntityForm {

  /**
   * The Rules expression manager to get expression plugins.
   *
   * @var \Drupal\rules\Engine\ExpressionManagerInterface
   */
  protected $expressionManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.rules_expression'));
  }

  /**
   * Creates a new object of this class.
   *
   * @param \Drupal\rules\Engine\ExpressionManagerInterface $expression_manager
   *   The expression manager.
   */
  public function __construct(ExpressionManagerInterface $expression_manager) {
    $this->expressionManager = $expression_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#entity_builders'][] = '::entityTagsBuilder';
    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#open' => $this->entity->isNew(),
    ];

    $form['settings']['label'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Enter a name to be used to identify your component in the administrative interface.'),
      '#title' => $this->t('Label'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];

    $form['settings']['id'] = [
      '#type' => 'machine_name',
      '#description' => $this->t('A unique machine-readable name for your component. Can only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'source' => ['settings', 'label'],
        'error' => $this->t('The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".'),
      ],
    ];

    // @todo Enter a real tag field here.
    $form['settings']['keywords'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tags'),
      '#default_value' => implode(', ', $this->entity->getTags()),
      '#description' => $this->t('Enter a list of comma-separated keywords here; e.g., "notification, publishing". Tags are keywords used for filtering available components in the administration interface.'),
      '#required' => FALSE,
    ];

    $form['settings']['description'] = [
      '#type' => 'textarea',
      '#default_value' => $this->entity->getDescription(),
      '#description' => $this->t('Enter a description for this component, to help document what this component is intended to do.'),
      '#title' => $this->t('Description'),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * Callback method for the #entity_builder form property.
   *
   * Used to change format of tags from comma-separated values (as input)
   * into an array (as stored in the the configuration entity).
   *
   * @param string $entity_type
   *   The type of the entity.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity whose form is being built.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function entityTagsBuilder($entity_type, ConfigEntityInterface $entity, array $form, FormStateInterface $form_state) {
    $tags = [];
    $input_tags = $form_state->getValue('keywords');
    if (trim($input_tags) != '') {
      $tags = array_map('trim', explode(',', $input_tags));
    }
    $entity->set('tags', $tags);
  }

  /**
   * Machine name exists callback.
   *
   * @param string $id
   *   The machine name ID.
   *
   * @return bool
   *   TRUE if an entity with the same name already exists, FALSE otherwise.
   */
  public function exists($id) {
    $type = $this->entity->getEntityTypeId();
    return (bool) $this->entityTypeManager->getStorage($type)->load($id);
  }

}
