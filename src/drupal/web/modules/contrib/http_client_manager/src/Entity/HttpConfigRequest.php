<?php

namespace Drupal\http_client_manager\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Http Config Request entity.
 *
 * @ConfigEntityType(
 *   id = "http_config_request",
 *   label = @Translation("Http Config Request"),
 *   handlers = {
 *     "list_builder" = "Drupal\http_client_manager\HttpConfigRequestListBuilder",
 *     "execution_handler" = "Drupal\http_client_manager\Controller\HttpConfigRequestController",
 *     "form" = {
 *       "add" = "Drupal\http_client_manager\Form\HttpConfigRequestForm",
 *       "edit" = "Drupal\http_client_manager\Form\HttpConfigRequestForm",
 *       "delete" = "Drupal\http_client_manager\Form\HttpConfigRequestDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\http_client_manager\HttpConfigRequestHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "http_config_request",
 *   admin_permission = "administer http_client_manager",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/http-client-manager/{serviceApi}/{commandName}/http-config-request/{http_config_request}",
 *     "add-form" = "/admin/config/services/http-client-manager/{serviceApi}/{commandName}/http-config-request/add",
 *     "edit-form" = "/admin/config/services/http-client-manager/{serviceApi}/{commandName}/http-config-request/{http_config_request}/edit",
 *     "delete-form" = "/admin/config/services/http-client-manager/{serviceApi}/{commandName}/http-config-request/{http_config_request}/delete",
 *     "execute" = "/admin/config/services/http-client-manager/{serviceApi}/{commandName}/http-config-request/{http_config_request}/execute",
 *     "collection" = "/admin/config/services/http-client-manager/{serviceApi}/{commandName}/http-config-request"
 *   }
 * )
 */
class HttpConfigRequest extends ConfigEntityBase implements HttpConfigRequestInterface {

  /**
   * The Http Config Request ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Http Config Request label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Http Config Request service api.
   *
   * @var string
   */
  protected $service_api;

  /**
   * The Http Config Request command name.
   *
   * @var string
   */
  protected $command_name;

  /**
   * The Http Config Request parameters.
   *
   * @var string
   */
  protected $parameters;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['serviceApi'] = $this->get('service_api');
    $uri_route_parameters['commandName'] = $this->get('command_name');
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $factory = \Drupal::service('http_client_manager.factory');
    $client = $factory->get($this->get('service_api'));
    $params = $this->getParameters();
    return $client->call($this->get('command_name'), $this->replaceTokens($params));
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters() {
    $parameters = $this->get('parameters');
    return !empty($parameters) ? array_filter($this->get('parameters')) : [];
  }

  /**
   * Replace Tokens.
   *
   * @param array $params
   *   The parameters to replace tokens.
   *
   * @return mixed
   *   The replaced token.
   */
  protected function replaceTokens($params) {
    $token = \Drupal::token();
    array_walk_recursive($params, function ($value, $key) use (&$params, $token) {
      if ($token->scan($value)) {
        $params[$key] = $token->replace($value);
      }
    });
    return $params;
  }

}
