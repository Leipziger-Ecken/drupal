<?php namespace Drupal\le_admin\Controller;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Site\Settings;
use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;

class Unsplash extends ControllerBase
{
  /**
   * Constructor.
   *
   */
  public function __construct()
  {
    $default_config = [
      'verify' => true,
      'timeout' => 30,
      'headers' => [
        'User-Agent' => 'Drupal/' . \Drupal::VERSION . ' (+https://www.drupal.org/) ' . \GuzzleHttp\default_user_agent(),
      ],
      'proxy' => [
        'http' => NULL,
        'https' => NULL,
        'no' => [],
      ],
    ];
    $config = NestedArray::mergeDeep($default_config, Settings::get('http_client_config', []));
    $this->client = new Client($config);
  }

  /**
   * Proxies unsplash API
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *    Return json.
   */
  public function proxy($a, $b, $c)
  {
    $path = '';
    if ($a) $path .= $a;
    if ($b) $path .= '/' . $b;
    if ($c) $path .= '/' . $c;
    $baseUrl = 'https://api.unsplash.com/';
    $accessKey = Settings::get('le_admin_unsplash_access_key');
    $query = array_merge(['client_id' => $accessKey], \Drupal::request()->query->all());
    return $this->client->get($baseUrl . $path, [
      'query' => $query,
    ]);
  }
}
