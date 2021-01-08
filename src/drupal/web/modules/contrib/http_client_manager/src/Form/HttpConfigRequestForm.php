<?php

namespace Drupal\http_client_manager\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\http_client_manager\HttpClientManagerFactoryInterface;
use Drupal\http_client_manager\HttpServiceApiHandlerInterface;
use GuzzleHttp\Command\Guzzle\Parameter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class HttpConfigRequestForm.
 *
 * @package Drupal\http_client_manager\Form
 */
class HttpConfigRequestForm extends EntityForm {

  /**
   * Current Request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Drupal\http_client_manager\HttpServiceApiHandler definition.
   *
   * @var \Drupal\http_client_manager\HttpServiceApiHandler
   */
  protected $httpServicesApi;

  /**
   * Drupal\http_client_manager\HttpClientManagerFactory definition.
   *
   * @var \Drupal\http_client_manager\HttpClientManagerFactory
   */
  protected $httpClientFactory;

  /**
   * HttpConfigRequestForm constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The Request Stack Service.
   * @param \Drupal\http_client_manager\HttpServiceApiHandlerInterface $http_services_api
   *   The Http Service Api Handler service.
   * @param \Drupal\http_client_manager\HttpClientManagerFactoryInterface $http_client_manager_factory
   *   The Http Client Factory service.
   */
  public function __construct(
    RequestStack $requestStack,
    HttpServiceApiHandlerInterface $http_services_api,
    HttpClientManagerFactoryInterface $http_client_manager_factory
  ) {
    $this->request = $requestStack->getCurrentRequest();
    $this->httpServicesApi = $http_services_api;
    $this->httpClientFactory = $http_client_manager_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('http_client_manager.http_services_api'),
      $container->get('http_client_manager.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $serviceApi = $this->request->get('serviceApi');
    $commandName = $this->request->get('commandName');
    $http_config_request = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $http_config_request->label(),
      '#description' => $this->t("Label for the Http Config Request."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $http_config_request->id(),
      '#machine_name' => [
        'exists' => '\Drupal\http_client_manager\Entity\HttpConfigRequest::load',
      ],
      '#disabled' => !$http_config_request->isNew(),
    ];

    $form['service_api'] = [
      '#type' => 'value',
      '#value' => $serviceApi,
    ];

    $form['command_name'] = [
      '#type' => 'value',
      '#value' => $commandName,
    ];

    $form['parameters'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('parameters'),
      '#tree' => TRUE,
    ];

    $client = $this->httpClientFactory->get($serviceApi);
    $parameters = $http_config_request->get('parameters');

    /** @var \GuzzleHttp\Command\Guzzle\Parameter $param */
    foreach ($client->getCommand($commandName)->getParams() as $param) {
      $name = $param->getName();
      $form['parameters'][$name] = [
        '#command_param' => $param,
        '#title' => $this->t($name),
        '#type' => 'textarea',
        '#rows' => 1,
        '#required' => $param->isRequired(),
        '#default_value' => $parameters[$name] ? $parameters[$name] : $param->getDefault(),
        '#description' => $param->getDescription() ? $this->t($param->getDescription()) : '',
      ];

      // Create a select list for parameters with enums.
      if ($param->has('enum')) {
        $enum = $param->getEnum();
        $options = ['' => $this->t('- Select -')] + array_combine($enum, $enum);
        $form['parameters'][$name]['#type'] = 'select';
        $form['parameters'][$name]['#options'] = $options;
        continue;
      }

      $type = $param->getType();
      switch ($type) {
        case 'bool':
        case 'boolean':
          $form['parameters'][$name]['#type'] = 'checkbox';
          $form['parameters'][$name]['#value_callback'] = [
            $this,
            'booleanValue',
          ];
          break;

        case 'integer':
        case 'number':
          $form['parameters'][$name]['#type'] = 'number';
          $form['parameters'][$name]['#value_callback'] = [
            $this,
            'integerValue',
          ];
          break;

        case 'float':
        case 'decimal':
          $form['parameters'][$name]['#type'] = 'number';
          $form['parameters'][$name]['#step'] = 'any';
          $form['parameters'][$name]['#value_callback'] = [
            $this,
            'floatValue',
          ];
          break;

        case 'array':
        case 'object':
          $form['parameters'][$name]['#type'] = 'textarea';
          $form['parameters'][$name]['#rows'] = 12;
          $form['parameters'][$name]['#description'] .= '<div class="json-help">' . $this->t('Example') . ': <small><pre>' . $this->getJsonHelp($param) . '</pre></small></div>';
          $form['parameters'][$name]['#attributes']['placeholder'] = $this->t('Enter data in JSON format.');
          $form['parameters'][$name]['#value_callback'] = [$this, 'jsonString'];
          $form['parameters'][$name]['#element_validate'][] = [
            $this,
            'validateJson',
          ];
          break;
      }
    }

    // Show the token help.
    $form['token_help'] = [
      '#theme' => 'token_tree_link',
    ];

    return $form;
  }

  /**
   * Value callback: casts provided input to integer.
   *
   * @param array $element
   *   The form element.
   * @param string $input
   *   The input value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form State instance.
   *
   * @return int|null
   *   The integer value or NULL if no value has been provided.
   */
  public function integerValue(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      return (int) $input;
    }
    return NULL;
  }

  /**
   * Value callback: casts provided input to float.
   *
   * @param array $element
   *   The form element.
   * @param string $input
   *   The input value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form State instance.
   *
   * @return float|null
   *   The float value or NULL if no value has been provided.
   */
  public function floatValue(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      return (float) $input;
    }
    return NULL;
  }

  /**
   * Value callback: casts provided input to boolean.
   *
   * @param array $element
   *   The form element.
   * @param string $input
   *   The input value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form State instance.
   *
   * @return bool|null
   *   The boolean value or NULL if no value has been provided.
   */
  public function booleanValue(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE || $input === NULL) {
      return (bool) $input;
    }
    return $element['#default_value'];
  }

  /**
   * Validate JSON.
   *
   * @param array $element
   *   The Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form State instance.
   */
  public function validateJson(array &$element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (empty($value)) {
      return;
    }

    if (is_string($value)) {
      $form_state->setError($element);
      return;
    }

    /** @var \GuzzleHttp\Command\Guzzle\Parameter $param */
    $param = $element['#command_param'];
    $type = $param->getType();

    switch ($type) {
      case 'array':
        $is_valid = is_array($value);
        break;

      case 'object':
        $is_valid = is_object($value) || (array_values($value) !== $value);
        break;

      default:
        $is_valid = FALSE;
    }

    if (!$is_valid) {
      $message = $this->t('Field @title has to be an @type. @var_type provided.', [
        '@title' => $element['#title'],
        '@type' => $type,
        '@var_type' => ucfirst(gettype($value)),
      ]);
      $form_state->setError($element, $message);
      if (!is_string($value)) {
        $element['#value'] = json_encode($value, JSON_PRETTY_PRINT);
      }
    }
  }

  /**
   * Value callback: converts strings to JSON array.
   *
   * @param array $element
   *   The form element.
   * @param string $input
   *   The input value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form State instance.
   *
   * @return array|null|string
   *   The computed value.
   */
  public function jsonString(array &$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      $input = trim($input);
      if (empty($input)) {
        return [];
      }

      /** @var \GuzzleHttp\Command\Guzzle\Parameter $param */
      $param = $element['#command_param'];
      $assoc = $param->getType() == 'array' ? JSON_OBJECT_AS_ARRAY : JSON_FORCE_OBJECT;

      $item = json_decode($input, $assoc);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $message = $this->t('There was an error parsing your JSON: @error', [
          '@error' => json_last_error_msg(),
        ]);
        $this->messenger()->addError($message);
        return $input;
      }
      return $item;
    }

    if (empty($element['#default_value'])) {
      return NULL;
    }

    $item = json_encode($element['#default_value'], JSON_PRETTY_PRINT);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->messenger()->addError(json_last_error_msg());
      return $input;
    }
    return $item;
  }

  /**
   * Get JSON Help.
   *
   * @param \GuzzleHttp\Command\Guzzle\Parameter $param
   *   The Guzzle parameter.
   *
   * @return string
   *   A JSON String.
   */
  protected function getJsonHelp(Parameter $param) {
    if ($param->getType() == 'array') {
      $properties = $param->getItems()->getProperties();
    }
    else {
      $properties = $param->getProperties();
    }

    $array = [];
    foreach ($properties as $name => $parameter) {
      switch ($parameter->getType()) {
        case 'string':
          $sample = 'Lorem ipsum...';
          break;

        case 'integer':
        case 'number':
          $sample = 123;
          break;

        case 'float':
        case 'decimal':
          $sample = 123.01;
          break;

        case 'bool':
        case 'boolean':
          $sample = TRUE;
          break;

        case 'array':
          $sample = [];
          break;

        case 'object':
          $sample = new \stdClass();
          break;

        default:
          $sample = '...';
      }
      $array[$name] = $sample;
    }

    if ($param->getType() == 'array') {
      $array = [$array];
    }
    return json_encode($array, JSON_PRETTY_PRINT);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $http_config_request = $this->entity;
    $status = $http_config_request->save();
    $messenger = $this->messenger();

    switch ($status) {
      case SAVED_NEW:
        $messenger->addStatus($this->t('Created the %label Http Config Request.', [
          '%label' => $http_config_request->label(),
        ]));
        break;

      default:
        $messenger->addStatus($this->t('Saved the %label Http Config Request.', [
          '%label' => $http_config_request->label(),
        ]));
    }
    $form_state->setRedirectUrl($http_config_request->toUrl('collection'));
  }

}
