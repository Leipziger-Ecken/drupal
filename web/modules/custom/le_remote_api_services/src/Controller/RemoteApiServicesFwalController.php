<?php

namespace Drupal\le_remote_api_services\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\geocoder\Geocoder;
use Drupal\geofield\WktGenerator;
use Drupal\node\Entity\Node;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RemoteApiServicesFwalController.
 * 
 * @todo Author of added entities should be "cron", not admin
 * 
 * @package Drupal\le_remote_api_services\Controller
 */
class RemoteApiServicesFwalController extends ControllerBase
{
  const ANGEBOT_NODE_TYPE = 'le_remote_content_fwal';
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
   * @var integer
   */
  protected $agencyId;

  /**
   * @var string
   */
  protected $accessKey;
  

  /**
   * RemoteApiServicesFwalController constructor.
   */
  public function __construct(Client $http_client, Geocoder $geocoder, WktGenerator $wktGenerator, EntityTypeManagerInterface $entity_type_manager)
  {
    $this->httpClient = $http_client;
    $this->geocoder = $geocoder;
    $this->wktGenerator = $wktGenerator;

    $this->providers = $entity_type_manager->getStorage('geocoder_provider')->loadMultiple(['mapbox']);
    $this->bezirke = $entity_type_manager->getStorage('taxonomy_term')->loadTree('le_bezirk');

    $this->agencyId = Settings::get('le_remote_api_fwal_agency_id', null);
    $this->accessKey = Settings::get('le_remove_api_fwal_access_key', null);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('http_client'),
      $container->get('geocoder'),
      $container->get('geofield.wkt_generator'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Resolve internal Bezirk-ID via geocoder provider
   * @todo Move into own trait
   */
  private function resolveBezirkIDFromGeodata(float $lng, float $lat): ?int
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
          // Maaaaatch!
          return (int) $bezirk->tid;
        }
      }
    }

    return null;
  }

  /**
   * Identify and return any akteur linked to $einrichtungsname
   */
  private function resolveAkteurFromEinrichtungsname(string $einrichtungsname)
  {
    $einrichtungsname = trim($einrichtungsname);
    $database = \Drupal::database();
    $query = $database->query("
      SELECT * FROM node__field_le_akteur_einrichtungsname
      WHERE bundle = :bundle
      AND field_le_akteur_einrichtungsname_value = :pattern",
      array(
        ':bundle' => 'le_akteur',
        ':pattern' => $einrichtungsname
      )
    );

    return $query->fetchAssoc();
  }

  private function mapAngebotToFields(\SimpleXMLElement $angebot, int $angebotsId): Array
  {
    $agencyId = $this->agencyId;
    $lng = (float) $angebot->geo_laenge;
    $lat = (float) $angebot->geo_breite;

    $fields = [
      'title' => (string) $angebot->angebotsname,
      'type' => self::ANGEBOT_NODE_TYPE,
      'body' => [
        'value' => (string) $angebot->beschreibung,
        'format' => 'full_html',
      ],
      'field_adresse' => [
        'country_code' => 'DE',
        'address_line1' => (string) $angebot->strasse,
        'postal_code' => (string) $angebot->plz,
        'locality' => (string) $angebot->ort,
      ],
      'field_le_rcds_id_external' => $angebotsId,
      'field_le_rcds_einrichtung_name' => (string) $angebot->einrichtungsname,
      'field_le_rcds_offers_count' => (int) $angebot->anzahl_gesuche,
      'field_le_rcds_link' => "https://www.freinet-online.de/query/iframe/detail.php?agid=${agencyId}&styleid=1&frametyp=2&detail=${angebotsId}",
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
   * Add angebot as new node or overwrite given node.
   */
  private function processAngebot(\SimpleXMLElement $angebot): int
  {
    $node_id = null;

    $attr = $angebot->attributes();
    $angebotsId = (int) $attr['angebotsId'];

    // Entity already existing locally?
    $node = \Drupal::entityQuery('node')
      ->condition('field_le_rcds_id_external', $angebotsId)
      ->condition('type', self::ANGEBOT_NODE_TYPE)
      ->execute();

    $fields = $this->mapAngebotToFields($angebot, $angebotsId);

    if (!empty($node)) {
      // Update node
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
      // Add node
      $einrichtungsname = (string) $angebot->einrichtungsname;

      if (!empty($einrichtungsname)) {
        $akteur = $this->resolveAkteurFromEinrichtungsname($einrichtungsname);
        if (!empty($akteur)) {
          $fields['og_audience'] = (int) $akteur['entity_id'];
        }
      }

      $node = Node::create($fields);
      $node->save();

      /*if (!$node->validate()) {
          print_r($node->validate());
      }*/

      $node_id = $node->id();
    }

    return $node_id;
  }

  /**
   * Le probleme: We know which items are in Freinet-DB, but not which have
   * been deleted there since last call -> compare response IDs with local 
   * items, remove those outside
   */
  private function identifyDeletedAngebote(Array $processed_ids): void
  {
    // @todo implement
  }

  /**
   * GetAngebote route callback.
   * 
   * Note that we use GuzzeHttp\Client to fetch data; not httpClientManager / Guzzle service descriptor
   * as this came with troubles parsing the returned XML (and I was tired of injecting further
   * middleware/toolkits).
   *
   * @return array
   *   A render array used to show the Angebote list.
   *
   * @see https://freinet-online.de/api/api_angebot
   */
  public function getAngebote(): Array
  {
    if (!$this->agencyId || !$this->accessKey) {
      return [
        '#type' => 'markup',
        '#markup' => 'Error: No agencyID / API-key provided'
      ];
    }

    $host = 'https://www.lachnit-software.de/';
    $operation = 'query/api/MatchingServiceEndpoint.php';
    $params = '?agencyId='. $this->agencyId .'&accessKey=' . $this->accessKey . '&limit=9999';

    $request = $this->httpClient->request('GET', "${host}${operation}${params}");
    $response = new \SimpleXMLElement($request->getBody()->getContents());

    $processed_ids = [];

    if (isset($response->angebot)) {
      foreach ($response->angebot as $angebot) {
        if ((int) $angebot->anzahl_gesuche === 0) {
          // Skip inactive offer
          continue;
        }
    
        $processed_ids[] = $this->processAngebot($angebot);
      }
    }

    // Identify and remove Angebote that do not exist anymore/becamed inactive
    $this->identifyDeletedAngebote($processed_ids);

    return [
      '#type' => 'markup',
      '#markup' => 'Added/Updated ' . count($processed_ids) . ' nodes'
    ];
  }
}

