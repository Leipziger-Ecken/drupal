<?php

namespace Drupal\le_api\Plugin\rest\resource;

use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheableResponseInterface;


error_reporting(E_ALL);
ini_set("display_errors", 1);

/**
 * Annotation for get method
 *
 * @RestResource(
 *   id = "le_akteure_get",
 *   label = @Translation("Endpoint GET"),
 *   uri_paths = {
 *     "patch" = "/api/v1/akteure/{id}",
 *     "create" = "/api/v1/akteure"
 *   }
 * )
 */
class AkteureResource extends ResourceBase
{
    /**
     * A current user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    /**
     * Constructs a Drupal\rest\Plugin\ResourceBase object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param array $serializer_formats
     *   The available serialization formats.
     * @param \Psr\Log\LoggerInterface $logger
     *   A logger instance.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   A current user instance.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        AccountProxyInterface $current_user)
    {parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);$this->currentUser = $current_user;
    }    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('custom_rest'),
            $container->get('current_user')
        );
    }    /**
     * Responds to GET requests.
     *
     * Returns a list of bundles for specified entity.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    /*public function get(string $akteur_id)
    {

        if ($akteur_id) {
            $node = Node::load($akteur_id);

            if (is_object($node)){
                $response_result[$node->id()] = $node->getFields();
                $response = new ResourceResponse($response_result);

                // Configure caching for results
                if ($response instanceof CacheableResponseInterface) {
                    $response->addCacheableDependency($response_result);
                }

                return $response;
            }

            return new ResourceResponse('Akteur doesn\'t exist', 400);
        }

        return new ResourceResponse('Akteur ID is required', 400);
    }*/

    /**
     * Responds to POST requests.
     *
     * Returns a list of bundles for specified entity.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function post(array $data)
    {
        $data = (object) $data;

        $response_status['status'] = false;

        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        $akteur = array(
            'type' => 'le_akteur',
            'title' => $data->title,
            'body' => [
              'summary' => '',
              'value' => $data->title,
              'format' => 'full_html',
            ],
            'field_le_akteur_description' => 'sdf',
            'field_le_akteur_email' => [
              '0' => array(
                'value' => 'bla@dl'
              )
            ],
            'field_le_akteur_name' => 'dfD',
            'bla' => 'DF'
          );

        $node = Node::create($akteur);

        if ($validationError = $node->validate()) {
          $node->save();
          return new ResourceResponse($node);
        } else {
          return new ResourceResponse($validationError, 200);
        }
    }

    /**
     * Responds to PATCH requests.
     *
     * @param string $payload
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function patch(string $akteur_id, array $data) {

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        if ($akteur_id) {
            $akteur = Node::load($akteur_id);

            if (is_object($akteur)) {
                foreach ($data as $key => $value) {
                    $akteur->set($key, $value);
                }

                $validationError = $akteur->validate();

                if (!isset($validationError)) {
                  $akteur->save();
                  return new ModifiedResourceResponse($akteur);
                } else {
                  return new ResourceResponse((string) $validationError, 400);
                }
            }

            return new ResourceResponse('Akteur doesn\'t exist', 400);
        }

        return new ModifiedResourceResponse($payload, 204);
    }

}

