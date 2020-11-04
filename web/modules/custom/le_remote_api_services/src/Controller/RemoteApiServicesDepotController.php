<?php

namespace Drupal\le_remote_api_services\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\geocoder\Geocoder;
use Drupal\geofield\WktGenerator;
use Drupal\http_client_manager\HttpClientManagerFactoryInterface;
use Drupal\node\Entity\Node;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RemoteApiServicesDepotController.
 * 
 * @todo Author of added entities should be "cron", not admin
 *
 * @package Drupal\le_remote_api_services\Controller
 */
class RemoteApiServicesDepotController extends ControllerBase
{
  const RESSOURCE_NODE_TYPE = 'le_remote_content_depot_social';
  const UPDATE_MODE = 'skip'; // (int) 'patch' || 'skip' 

  /**
   * @var \Drupal\http_client_manager\HttpClientInterface
   */
  protected $httpClient;

  /**
   * @var \Drupal\geocoder\Geocoder
   */
  protected $geocoder;

  /**
   * @var \Drupal\geofield\WktGenerator
   */
  protected $wktGenerator;

  /**
   * @var Array
   */
  protected $providers;

  /**
   * @var Array
   */
  protected $bezirke;

  /**
   * RemoteApiServicesController constructor.
   */
  public function __construct(HttpClientManagerFactoryInterface $http_client_factory, Geocoder $geocoder, WktGenerator $wktGenerator, EntityTypeManagerInterface $entity_type_manager) {
    $this->httpClient = $http_client_factory->get('le_remote_api_services_depot_social.contents');
    $this->geocoder = $geocoder;
    $this->wktGenerator = $wktGenerator;

    $this->providers = $entity_type_manager->getStorage('geocoder_provider')->loadMultiple(['mapbox']);
    $this->bezirke = $entity_type_manager->getStorage('taxonomy_term')->loadTree('le_bezirk');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client_manager.factory'),
      $container->get('geocoder'),
      $container->get('geofield.wkt_generator'),
      $container->get('entity_type.manager'),
    );
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
  private function resolveBezirkIDFromGeodata(int $lng, int $lat): ?int
  {

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
          return (int) $bezirk->tid;
        }
      }
    }

    return null;
  }

  private function mapRessourceToFields(Array $ressource): Array
  {

    $lng = (float) $ressource['address_lng'];
    $lat = (float) $ressource['address_lat'];

    $fields = [
      'title' => (string) $ressource['name'],
      'type' => self::RESSOURCE_NODE_TYPE,
      'body'  => [
        'value' => (string) $ressource['desc'],
        'format' => 'full_html',
      ],
      'field_le_rcds_id_external' => (int) $ressource['id'],
      'field_le_rcds_link' => (string) $ressource['uri_slug'],
      'field_le_rcds_resources_count' => (int) $ressource['resources_count'],
      'field_le_rcds_image' => 'https://leipzig.depot.social/sites/leipzig.depot.social/files/' . $ressource['image']['filename'],
      'field_le_rcds_zip_code' => (string) $ressource['address_zip_code'],
    ];

    if ($lng && $lat) {
      $wktPoint = $this->wktGenerator->WktBuildPoint([
        'lon' => $lng,
        'lat' => $lat
      ]);

      $fields['field_geofield'] = $wktPoint;
      $fields['field_bezirk'] = $this->resolveBezirkIDFromGeodata($lng, $lat);
    }

    return $fields;
  }

  /**
   * Add resource as new node or overwrite given node.
   */
  private function processRessource(Array $ressource): int
  {
    $node_id = null;

    // Entity already existing locally?
    $node = \Drupal::entityQuery('node')
      ->condition('field_le_rcds_id_external', (int) $ressource['id'])
      ->condition('type', self::RESSOURCE_NODE_TYPE)
      ->execute();

    $fields = $this->mapRessourceToFields($ressource);

    if (!empty($node)) {
      // Update/Patch node
      $node_id = $node[array_key_first($node)];

      if (self::UPDATE_MODE === 'skip') {
        // For testing or in poor performing environments: Skip update
        return $node_id;
      }

      $node = Node::load($node_id);

      foreach($fields as $key => $field) {
        // Patch fields
        $node->set($key, $field);
      }

      $node->save();

    } else {
      // Add new node
      // @todo Link with Akteur, if wished
      $node = Node::create($fields);
      $node->save();
      $node_id = $node->id();
    }

    return $node_id;
  }

  private function identifyDeletedAngebote(Array $processed_ids): void
  {
    // @todo implement
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
  public function getRessourcen($limit = 100, $sort = '')
  {

    $response = $this->httpClient->call('GetRessourcen', [
      'limit' => $limit,
      'sort' => $sort
    ]);

    // @todo Check for valid response
    $response = $response->toArray();

    $processed_ids = [];

    foreach ($response as $id => $ressource) {
      $processed_ids[] = $this->processRessource($ressource);
    }

    // Identify and remove Angebote that do not exist anymore/becamed inactive
    $this->identifyDeletedAngebote($processed_ids);

    return [
      '#type' => 'markup',
      '#markup' => 'Added/Updated ' . count($processed_ids) . ' nodes'
    ];
  }
}

