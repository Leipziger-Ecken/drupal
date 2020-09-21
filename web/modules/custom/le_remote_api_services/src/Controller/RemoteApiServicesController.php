<?php

namespace Drupal\le_remote_api_services\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\http_client_manager\HttpClientManagerFactoryInterface;
use Drupal\node\Entity\Node;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DependencyInjection\ContainerInterface;
// use Drupal\geofield\WktGenerator;

// @todo https://www.drupal.org/docs/8/modules/http-client-manager/the-handler-stack
// @todo Better inject services instead of using global import
// @see https://drupal.stackexchange.com/questions/263598/how-to-inject-dependencies-into-an-access-controller

/**
 * Class RemoteApiServicesController.
 *
 * @package Drupal\le_remote_api_services\Controller
 */
class RemoteApiServicesController extends ControllerBase {

  const RESSOURCE_NODE_TYPE = 'le_remote_content_depot_social';

  /**
   * An ACME Services - Contents HTTP Client.
   *
   * @var \Drupal\http_client_manager\HttpClientInterface
   */
  protected $httpClient;

  /**
   * RemoteApiServicesController constructor.
   *
   * @todo Fully functionalize params
   *
   * @param \Drupal\http_client_manager\HttpClientManagerFactoryInterface $http_client_factory
   *   The HTTP Client Manager Factory service.
   * @param \Drupal\geocoder\Geocoder $geocoder
   * @param \Drupal\geocoder\ProviderPluginManager $providerPluginManager
   */
  public function __construct(HttpClientManagerFactoryInterface $http_client_factory) {
    $this->httpClient = $http_client_factory->get('le_remote_api_services_depot_social.contents');
    $this->geocoder = \Drupal::service('geocoder');
    $this->wktGenerator = \Drupal::service('geofield.wkt_generator');

    $this->providers = \Drupal::entityTypeManager()->getStorage('geocoder_provider')->loadMultiple(['mapbox']);
    $this->bezirke =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('le_bezirk');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client_manager.factory')
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
   * Resolve adress from geodata, match that address against local bezirke-taxonomy and return
   * its ID, if any.
   *
   * @return int|null
   *    The term-ID of le_bezirk
   *
   * This methods only works due to a patch to geocoder-mapbox package (see patches-folder).
   *
   * For further information:
   * The geocoder module has an extremely helpful README file ;)
   */
  private function getBezirkIDFromGeodata(int $lng, int $lat) {

    if (empty($lng) || empty($lat)) {
      return null;
    }

    $result = $this->geocoder->reverse($lat, $lng, $this->providers);

    if (NULL === $result) {
      return null;
    }

    $result = $result->first();

    if ($result && isset($result->subLocality)) {
      $needle = $result->subLocality;

      foreach ($this->bezirke as $bezirk) {
        if (strpos($bezirk->name, $needle) !== false) {
          // We found a match!
          echo 'found' . $bezirk->name;
          return (int) $bezirk->tid;
        }
      }

      // echo 'DEBUG - NO MATCH FOR: ' . $needle . '<br />';
    }

    return null;
  }

  /**
   * @todo Rename mapRessourceToNode, return array only
   * * create & save when new item
   * * iterate / map onto given item & save
   */
  private function addNodeFromRessource($ressource) {

    $lng = $ressource['address_lng'];
    $lat = $ressource['address_lat'];

    $node = [
      'title' => $ressource['name'],
      'type' => self::RESSOURCE_NODE_TYPE,
      'body'  => [
        'value' => $ressource['desc'],
        'format' => 'full_html',
      ],
      'field_le_rcds_id_external' => (int) $ressource['id'],
      'field_le_rcds_link' => $ressource['uri_slug'],
      'field_le_rcds_resources_count' => (int) $ressource['resources_count'],
      'field_le_rcds_image' => 'https://leipzig.depot.social/sites/leipzig.depot.social/files/' . $ressource['image']['filename'],
      'field_le_rcds_zip_code' => $ressource['address_zip_code'],
    ];

    $node = Node::create($node);

    if ($lng && $lat) {

      $node->set('field_bezirk', $this->getBezirkIDFromGeodata($lng, $lat));

      $point = $this->wktGenerator->WktBuildPoint([
        'lon' => $lng,
        'lat' => $lat
      ]);

      $node->set('field_le_rcds_geofield', $point);
    }

    $node->save();
    return $node->id;
  }

  private function readRessource($ressource) {

    $node_id = null;

    $node = \Drupal::entityQuery('node')
      ->condition('field_le_rcds_id_external', (int) $ressource['id'])
      ->condition('type', self::RESSOURCE_NODE_TYPE)
      ->execute();

    if (!empty($node)) {
      // Update/Patch node
      $node_id = $node[array_key_first($node)];
      $node = Node::load($node_id);

      // @todo
    } else {
      // Add node
      $node_id = $this->addNodeFromRessource($ressource);
    }

    return [
      '#type' => 'markup',
      '#markup' => '<p>Added/Updated node '. $node_id .'</p><br />'
    ];
  }

  /**
    * GetRessourcen route callback.
    *
    * @param int $limit
    *   The total number of posts we want to fetch.
    * @param string $sort
    *   The sorting order.
    *
    * @return array
    *   A render array used to show the Posts list.
    *
    * @todo Add timestamp & page-params, sort by created/updated
    */
  public function getRessourcen($limit = 100, $sort = '') {

    $response = $this->httpClient->call('GetRessourcen', [
      'limit' => $limit,
      'sort' => $sort
    ]);

    // @todo Check for valid response
    $response = $response->toArray();

    $build = [];

    foreach ($response as $id => $ressource) {
      $build[$id] = $this->readRessource($ressource);
    }

    // @todo Save timestamp

    return $build;
  }

}

