<?php

namespace Drupal\http_client_manager_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\http_client_manager\Entity\HttpConfigRequest;
use Drupal\http_client_manager\HttpClientInterface;
use Drupal\http_client_manager\HttpServiceApiWrapperFactoryInterface;
use Drupal\http_client_manager\Plugin\HttpServiceApiWrapper\HttpServiceApiWrapperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ExampleController.
 *
 * @package Drupal\http_client_manager_example\Controller
 */
class ExampleController extends ControllerBase {

  /**
   * JsonPlaceholder Http Client.
   *
   * @var \Drupal\http_client_manager\HttpClientInterface
   */
  protected $httpClient;

  /**
   * The Posts Api Wrapper service.
   *
   * @var \Drupal\http_client_manager_example\Plugin\HttpServiceApiWrapper\HttpServiceApiWrapperPosts
   */
  protected $api;

  /**
   * The HTTP Service Api Wrapper Factory service.
   *
   * @var \Drupal\http_client_manager\HttpServiceApiWrapperFactoryInterface
   */
  protected $apiFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(HttpClientInterface $http_client, HttpServiceApiWrapperInterface $api_wrapper, HttpServiceApiWrapperFactoryInterface $api_wrapper_factory) {
    $this->httpClient = $http_client;
    $this->api = $api_wrapper;
    $this->apiFactory = $api_wrapper_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('example_api.http_client'),
      $container->get('http_client_manager_example.api_wrapper.posts'),
      $container->get('http_client_manager.api_wrapper.factory')
    );
  }

  /**
   * Get Client.
   *
   * @return \Drupal\http_client_manager\HttpClientInterface
   *   The Http Client instance.
   */
  public function getClient() {
    return $this->httpClient;
  }

  /**
   * Find posts.
   *
   * @param int|null $postId
   *   The post Id.
   *
   * @return array
   *   The service response.
   */
  public function findPosts($postId = NULL) {
    $client = $this->getClient();
    $post_link = TRUE;
    $command = 'FindPosts';
    $params = [];

    if (!empty($postId)) {
      $post_link = FALSE;
      $command = 'FindPost';
      $params = ['postId' => (int) $postId];
    }
    $response = $client->call($command, $params);

    if (!empty($postId)) {
      $response = [$postId => $response->toArray()];
    }

    $build = [];
    foreach ($response as $id => $post) {
      $build[$id] = $this->buildPostResponse($post, $post_link);
    }

    return $build;
  }

  /**
   * Find posts - Advanced usage.
   *
   * @param int|null $postId
   *   The post Id.
   *
   * @return array
   *   The service response.
   */
  public function findPostsAdvanced($postId = NULL) {
    $post_link = empty($postId);
    $response = !empty($postId) ? $this->api->findPost($postId) : $this->api->findPosts();

    if (!empty($postId)) {
      $response = [$postId => $response];
    }
    $build = [];
    foreach ($response as $id => $post) {
      $build[$id] = $this->buildPostResponse($post, $post_link, TRUE);
    }

    return $build;
  }

  /**
   * Build Post response.
   *
   * @param array $post
   *   The Post response item.
   * @param bool $post_link
   *   TRUE for a "Read more" link, otherwise "Back to list" link.
   * @param bool $advanced
   *   Boolean indicating if we are using the basic or advanced usage.
   *
   * @return array
   *   A render array of the post.
   */
  protected function buildPostResponse(array $post, $post_link, $advanced = FALSE) {
    $route = $advanced ? 'http_client_manager_example.find_posts.advanced' : 'http_client_manager_example.find_posts';
    $link_text = $post_link ? $this->t('Read more') : $this->t('Back to list');
    $route_params = $post_link ? ['postId' => $post['id']] : [];

    $output = [
      '#type' => 'fieldset',
      '#title' => $post['id'] . ') ' . $post['title'],
      'body' => [
        '#markup' => '<p>' . $post['body'] . '</p>',
      ],
      'link' => [
        '#markup' => Link::createFromRoute($link_text, $route, $route_params)
          ->toString(),
      ],
    ];

    return $output;
  }

  /**
   * Create post.
   *
   * @return array
   *   The service response.
   */
  public function createPost() {
    $this->checkTokenModule();
    if ($request = HttpConfigRequest::load('create_post')) {
      $response = '<pre>' . print_r($request->execute(), TRUE) . '</pre>';
    }
    else {
      $response = $this->t('Unable to load "create_post" configured request.');
    }

    return [
      '#type' => 'markup',
      '#markup' => $response,
    ];
  }

  /**
   * Create post - Advanced usage.
   *
   * @return array
   *   The service response.
   */
  public function createPostAdvanced() {
    $this->checkTokenModule();

    // The whole HTTP Service Api Wrappers Factory is being used just to show
    // you an alternative way for accessing the api wrappers.
    $api = $this->apiFactory->get('posts');

    // Here we are using an HTTP Config Request just for example purposes.
    $response = $api->httpConfigRequest('create_post');
    return [
      '#type' => 'markup',
      '#markup' => '<pre>' . print_r($response, TRUE) . '</pre>',
    ];
  }

  /**
   * Check Token module.
   */
  protected function checkTokenModule() {
    if (!$this->moduleHandler()->moduleExists('token')) {
      $message = $this->t('Install the Token module in order to use tokens inside your HTTP Config Requests.');
      \Drupal::messenger()->addWarning($message);
    }
  }

}
