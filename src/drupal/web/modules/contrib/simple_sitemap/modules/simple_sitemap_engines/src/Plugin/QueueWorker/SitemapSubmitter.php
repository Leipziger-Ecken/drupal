<?php

namespace Drupal\simple_sitemap_engines\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\Logger;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process a queue of search engines to submit sitemaps.
 *
 * @QueueWorker(
 *   id = "simple_sitemap_engine_submit",
 *   title = @Translation("Sitemap search engine submission"),
 *   cron = {"time" = 30}
 * )
 *
 * @see simple_sitemap_engines_cron()
 */
class SitemapSubmitter extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $engineStorage;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * @var \Drupal\simple_sitemap\Logger
   */
  protected $logger;

  /**
   * SitemapSubmitter constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityStorageInterface $engine_storage
   * @param \GuzzleHttp\ClientInterface $http_client
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\Logger $logger
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $engine_storage, ClientInterface $http_client, Simplesitemap $generator, Logger $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->engineStorage = $engine_storage;
    $this->httpClient = $http_client;
    $this->generator = $generator;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('simple_sitemap_engine'),
      $container->get('http_client'),
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.logger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($engine_id) {
    /** @var \Drupal\simple_sitemap_engines\Entity\SearchEngine $engine */
    if ($engine = $this->engineStorage->load($engine_id)) {

      $sitemap_urls = [];
      $manager = $this->generator->getSitemapManager();

      foreach ($manager->getSitemapTypes() as $type_name => $type_definition) {
        $sitemap_generator = $manager->getSitemapGenerator($type_definition['sitemapGenerator']);

        // Submit all variants that are enabled for this search engine.
        foreach ($manager->getSitemapVariants($type_name, FALSE) as $variant_id => $variant_definition) {
          if (in_array($variant_id, $engine->sitemap_variants)
            && FALSE !== $this->generator->setVariants($variant_id)->getSitemap()) {
            $sitemap_urls[$variant_definition['label']] = $sitemap_generator->setSitemapVariant($variant_id)->getSitemapUrl();
          }
        }
      }

      // Submit all URLs.
      foreach ($sitemap_urls as $variant => $sitemap_url) {
        $submit_url = str_replace('[sitemap]', $sitemap_url, $engine->url);
        try {
          $this->httpClient->request('GET', $submit_url);
          // Log if submission was successful.
          $this->logger->m('Sitemap @variant submitted to @url', ['@variant' => $variant, '@url' => $submit_url])->log();
          // Record last submission time. This is purely informational; the
          // variable that determines when the next submission should be run is
          // stored in the global state.
          $engine->last_submitted = time();
        }
        catch (RequestException $e) {
          // Catch and log exceptions so this submission gets removed from the
          // queue whether or not it succeeded.
          // If the error was caused by network failure, it's fine to just wait
          // until next time the submission is queued to try again.
          // If the error was caused by a malformed URL, keeping the submission
          // in the queue to retry is pointless since it will always fail.
          watchdog_exception('simple_sitemap', $e);
        }
      }
      $engine->save();
    }
  }

}
