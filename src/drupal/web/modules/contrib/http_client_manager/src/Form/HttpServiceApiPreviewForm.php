<?php

namespace Drupal\http_client_manager\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\http_client_manager\HttpClientManagerFactoryInterface;
use Drupal\http_client_manager\HttpServiceApiHandlerInterface;
use GuzzleHttp\Command\Guzzle\Parameter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class HttpServiceApiPreviewForm.
 *
 * @package Drupal\http_client_manager\Form
 */
class HttpServiceApiPreviewForm extends FormBase {

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
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * HttpConfigRequestForm constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The Request Stack Service.
   * @param \Drupal\http_client_manager\HttpServiceApiHandlerInterface $http_services_api
   *   The Http Service Api Handler service.
   * @param \Drupal\http_client_manager\HttpClientManagerFactoryInterface $http_client_manager_factory
   *   The Http Client Factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   */
  public function __construct(
    RequestStack $requestStack,
    HttpServiceApiHandlerInterface $http_services_api,
    HttpClientManagerFactoryInterface $http_client_manager_factory,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->request = $requestStack->getCurrentRequest();
    $this->httpServicesApi = $http_services_api;
    $this->httpClientFactory = $http_client_manager_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('http_client_manager.http_services_api'),
      $container->get('http_client_manager.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'http_service_api_preview_form';
  }

  /**
   * Route title callback.
   *
   * @param string $serviceApi
   *   The service api name.
   *
   * @return string
   *   The service api title.
   */
  public function title($serviceApi) {
    try {
      $api = $this->httpServicesApi->load($serviceApi);
    }
    catch (\InvalidArgumentException $e) {
      $api['title'] = $this->t('Http Service Api not found');
    }
    return $api['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $serviceApi = $this->request->get('serviceApi');
    $client = $this->httpClientFactory->get($serviceApi);
    $commands = $client->getCommands();
    ksort($commands);

    $form['search'] = [
      '#type' => 'select',
      '#options' => array_combine(array_keys($commands), array_keys($commands)),
      '#empty_option' => $this->t('- All Commands -'),
      '#title' => $this->t('Filter commands'),
      '#title_display' => 'invisible',
      '#ajax' => [
        'callback' => '::filterCommandsAjaxCallback',
        'wrapper' => 'service-commands-wrapper',
      ],
      '#required' => TRUE,
    ];

    $form['service_commands'] = [
      '#type' => 'vertical_tabs',
      '#prefix' => '<div id="service-commands-wrapper">',
      '#suffix' => '</div>',
    ];

    $header = [
      'name' => $this->t('Name'),
      'type' => $this->t('Type'),
      'default' => $this->t('Default'),
      'description' => $this->t('Description'),
      'required' => $this->t('Required'),
      'location' => $this->t('Location'),
    ];

    /** @var \GuzzleHttp\Command\Guzzle\Operation $command */
    foreach ($commands as $commandName => $command) {
      $rows = [];
      /** @var \GuzzleHttp\Command\Guzzle\Parameter $param */
      foreach ($command->getParams() as $param) {
        $row = [
          'name' => $param->getName(),
          'type' => $this->getParameterType($param),
          'default' => $param->getDefault(),
          'description' => $param->getDescription(),
          'required' => $param->isRequired() ? $this->t('Yes') : $this->t('No'),
          'location' => $param->getLocation(),
        ];
        $rows[] = $row;
      }

      $form[$commandName] = [
        '#type' => 'details',
        '#title' => $this->t($commandName),
        '#description' => $this->t($command->getSummary()),
        '#group' => 'service_commands',
        '#access' => !$this->isHiddenCommand($commandName, $form_state),
      ];

      $form[$commandName]['info'] = [
        '#type' => 'table',
        '#header' => [
          'method' => $this->t('HTTP Method'),
          'uri' => $this->t('URI'),
          'operations' => $this->t('Operations'),
        ],
        '#rows' => [
          [
            $command->getHttpMethod(),
            $command->getUri(),
            ['data' => $this->buildOperations($serviceApi, $commandName)],
          ],
        ],
      ];

      $form[$commandName]['parameters'] = [
        '#type' => 'table',
        '#caption' => $this->t('Parameters'),
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('There are no parameters for this command.'),
      ];
    }

    $form['#attached']['library'][] = 'http_client_manager/service.preview';

    return $form;
  }

  /**
   * Filter commands Ajax Callback.
   *
   * @param array $form
   *   The form array.
   *
   * @return array
   *   The form element to be processed.
   */
  public function filterCommandsAjaxCallback(array $form) {
    return $form['service_commands'];
  }

  /**
   * Is hidden command.
   *
   * Check if the given command has to be filtered out.
   *
   * @param string $commandName
   *   The command name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the command has to be hidden.
   */
  protected function isHiddenCommand($commandName, FormStateInterface $form_state) {
    $search = trim($form_state->getValue('search', FALSE));
    if (empty($search)) {
      return FALSE;
    }

    return ($search != $commandName);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No action has to be performed.
  }

  /**
   * Get parameter type.
   *
   * @param \GuzzleHttp\Command\Guzzle\Parameter $param
   *   A Parameter object.
   *
   * @return string
   *   A formatted Parameter type.
   */
  protected function getParameterType(Parameter $param) {
    $type = $param->getType();
    if ($type != 'array') {
      return ucfirst($type);
    }

    $childType = $param->getItems()->getType();
    return '<List>' . ucfirst($childType);
  }

  /**
   * Build operations.
   *
   * @param string $serviceApi
   *   The service api name.
   * @param string $commandName
   *   The command name.
   *
   * @return array
   *   An array of operations.
   */
  protected function buildOperations($serviceApi, $commandName) {
    return [
      '#type' => 'operations',
      '#links' => [
        'config_requests' => [
          'title' => $this->t('Configured Requests (@count)', [
            '@count' => $this->getConfigEntitiesCount('http_config_request', $serviceApi, $commandName),
          ]),
          'url' => Url::fromRoute('entity.http_config_request.collection', [
            'serviceApi' => $serviceApi,
            'commandName' => $commandName,
          ]),
          'attributes' => [
            'class' => 'http-client-manager-service-summary',
          ],
        ],
      ],
    ];
  }

  /**
   * Get total number of available Http Config Requests for a specific command.
   *
   * @param mixed $entity
   *   The entity.
   * @param string $serviceApi
   *   The service api name.
   * @param string $commandName
   *   The command name.
   *
   * @return int
   *   Total number of available config requests.
   */
  protected function getConfigEntitiesCount($entity, $serviceApi, $commandName) {
    $storage = $this->entityTypeManager->getStorage($entity);
    return $storage->getQuery()
      ->condition('service_api', $serviceApi)
      ->condition('command_name', $commandName)
      ->count()
      ->execute();
  }

}
