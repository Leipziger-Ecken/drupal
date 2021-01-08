<?php

namespace Drupal\http_client_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\vardumper\VarDumper\VarDumperDebug;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HttpConfigRequestController.
 *
 * @package Drupal\http_client_manager\Controller
 */
class HttpConfigRequestController extends ControllerBase {

  /**
   * Var Dumper service definition.
   *
   * @var \Drupal\vardumper\VarDumper\VarDumperDebug|null
   */
  protected $varDumper;

  /**
   * HttpConfigRequestController constructor.
   *
   * @param \Drupal\vardumper\VarDumper\VarDumperDebug|null $varDumper
   *   The VarDumper service.
   */
  public function __construct(VarDumperDebug $varDumper = NULL) {
    $this->varDumper = $varDumper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->has('vardumper_message') ? $container->get('vardumper_message') : NULL
    );
  }

  /**
   * Execute an Http Config Request.
   *
   * @param string $serviceApi
   *   The Http Service Api name.
   * @param string $commandName
   *   The Http Service Api Command name.
   * @param string $http_config_request
   *   The Http Config Request name.
   *
   * @return array
   *   A render array of the Request execution.
   */
  public function execute($serviceApi, $commandName, $http_config_request) {
    $storage = $this->entityTypeManager()->getStorage('http_config_request');
    /** @var \Drupal\http_client_manager\Entity\HttpConfigRequest $config_request */
    $config_request = $storage->load($http_config_request);
    $request = [
      'serviceApi' => $serviceApi,
      'commandName' => $commandName,
      'parameters' => array_filter($config_request->getParameters()),
    ];
    /** @var \GuzzleHttp\Command\Result $response */
    $response = $config_request->execute();

    if ($this->varDumper) {
      $this->varDumper->dump($request, 'REQUEST: ' . $http_config_request);
      $this->varDumper->dump($response, 'RESPONSE: ' . $http_config_request);
      return [
        '#type' => 'markup',
        '#markup' => '',
      ];
    }

    return [
      'request' => [
        '#type' => 'fieldset',
        '#title' => 'REQUEST: ' . $http_config_request,
        'output' => [
          '#markup' => '<pre>' . print_r($request, TRUE) . '</pre>',
        ],
      ],
      'response' => [
        '#type' => 'fieldset',
        '#title' => 'RESPONSE: ' . $http_config_request,
        'output' => [
          '#markup' => '<pre>' . print_r($response, TRUE) . '</pre>',
        ],
      ],
    ];
  }

}
