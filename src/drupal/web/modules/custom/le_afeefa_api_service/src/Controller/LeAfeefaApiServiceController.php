<?php

namespace Drupal\le_afeefa_api_service\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\geocoder\Geocoder;
use Drupal\geofield\WktGenerator;
use Drupal\le_remote_api_services\Controller\RemoteApiServicesDepotController;
use Drupal\node\Entity\Node;
use GuzzleHttp\Client;
use Drupal\http_client_manager\HttpClientManagerFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LeAfeefaApiServiceModuleController
 *
 * API Call: https://afeefa.de/fapi/search?area=leipzig&lang=de
 * API Offer Detail: https://afeefa.de/fapi/offers/1849?lang=de
 * API Categories: https://afeefa.de/fapi/categoryTypes?area=leipzig&lang=de
 *
 * @package Drupal\le_afeefa_api_service\Controller
 */
class LeAfeefaApiServiceController extends ControllerBase
{
  const RESSOURCE_NODE_TYPE = 'le_remote_content_afeefa';

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
   * constructor.
   */
  public function __construct(Client $http_client, Geocoder $geocoder, WktGenerator $wktGenerator, EntityTypeManagerInterface $entity_type_manager)
    {
      $this->httpClient = $http_client;
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
      $container->get('http_client'),
      $container->get('geocoder'),
      $container->get('geofield.wkt_generator'),
      $container->get('entity_type.manager')
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
    //dump($result);

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


  /**
   * get resource and return object array
   * @return array $feedArray
   */
  protected function getFeed()
  {
    $feedArray = [];
    // local test
    //$feed = file_get_contents(__DIR__.'/../Data/afeefa-leipzig.json');
    //$feedArray = \GuzzleHttp\json_decode($feed);

    // api call
    $host = 'https://afeefa.de/fapi/';
    $operation = 'search';
    $params = '?area=leipzig&lang=de';
    $request = $this->httpClient->request('GET', "${host}${operation}${params}", []);
    $feedArray = \GuzzleHttp\json_decode($request->getBody()->getContents());

    return $feedArray;
  }


  /**
   * helper function
   * @see RemoteApiServicesDepotController
   * @param object $ressource
   * @return Array
   */
  private function mapRessourceToFields($ressource)
  {
    $fields = [
      'title' => (string) $ressource->title,
      'type' => self::RESSOURCE_NODE_TYPE,
      'body'  => [
        'value' => (string) $ressource->description,
        'format' => 'full_html',
      ],
      'field_afeefa_offer_id' => (int) $ressource->id,
      'field_le_rcds_link' => 'https://afeefa.de/'.(string) $ressource->slug.'-'.(int) $ressource->id.'o',
      'field_afeefa_requirements' => (string) $ressource->requirements,
    ];

    $lng = (float) $ressource->location[0];
    $lat = (float) $ressource->location[1];
    if ($lng && $lat) {
      $wktPoint = $this->wktGenerator->WktBuildPoint([
        'lon' => $lng,
        'lat' => $lat
      ]);

      $fields['field_geofield'] = $wktPoint;
      $fields['field_bezirk'] = $this->resolveBezirkIDFromGeodata($lng, $lat);
      //dump($wktPoint);
    }

    return $fields;
  }


  /**
   * handle single resource to import or update
   * @param array $resource
   */
  protected function processResource($resource)
  {
    // check
    $node = \Drupal::entityQuery('node')
      ->condition('field_afeefa_offer_id', (int) $resource->id)
      ->condition('type', self::RESSOURCE_NODE_TYPE)
      ->execute();

    $fields = $this->mapRessourceToFields($resource);

    #dump($resource);
    #dump($node);
    #dump($fields);
    #exit();

    if (!empty($node)) {
      // Update/Patch node
      $node_id = $node[array_key_first($node)];
      $node = Node::load($node_id);

      foreach($fields as $key => $field) {
        // Patch fields
        $node->set($key, $field);
      }

      $node->setPublished(1);
      $node->save();

    } else {
      // Add new node
      $node = Node::create($fields);
      #dump($node);
      #exit();

      $node->setPublished(1);
      $node->save();
      $node_id = $node->id();
    }
    //var_dump($node_id);

    return $node_id;
  }


  /**
   * set existing resources to unpublish
   */
  protected function unpublishNodes()
  {
    $nodes = \Drupal::entityQuery('node')
      ->condition('type', self::RESSOURCE_NODE_TYPE)
      ->execute();

    foreach ($nodes as $node) {
      $node = Node::load((int) $node);
      $node->setUnpublished();
      $node->save();
    }
  }


  /**
   * main method function, called by url path
   */
  public function makeUpdate()
  {
    // set all afeefa nodes to unpublished
    $this->unpublishNodes();

    // get data
    $resource = $this->getFeed();

    // process offers from api
    if(count($resource->offers)>0) {
     foreach ($resource->offers as $key => $offer) {
       $this->processResource($offer);
       //if ($key==5) break;
     }
    }

    return [
      '#type' => 'markup',
      '#markup' => 'Added/Updated ' . $key . ' nodes'
    ];
  }

}
