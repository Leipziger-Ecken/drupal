<?php

namespace Drupal\Tests\http_client_manager\Unit;

use Drupal\http_client_manager\HttpClient;
use Drupal\http_client_manager\HttpServiceApiHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class HttpClientTest.
 *
 * @package Drupal\Tests\http_client_manager\Unit
 * @coversDefaultClass \Drupal\http_client_manager\HttpClient
 * @group HttpClientManager
 */
class HttpClientTest extends UnitTestCase {

  /**
   * The client.
   *
   * @var \Drupal\http_client_manager\HttpClient
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->initClient('test', $this->getServiceApiInfo());
  }

  /**
   * Tests HttpClient::getApi().
   *
   * @covers ::getApi
   */
  public function testGetApi() {
    $this->assertSame($this->getServiceApiInfo(), $this->client->getApi());
  }

  /**
   * Tests HttpClient::getCommands().
   *
   * @covers ::getCommands
   */
  public function testGetCommands() {
    $commands = $this->client->getCommands();
    $this->assertCount(3, $commands);
    $this->assertArrayHasKey('FindPosts', $commands);
    $this->assertArrayHasKey('FindComments', $commands);
  }

  /**
   * Tests HttpClient::getCommand().
   *
   * @param string $commandName
   *   A Guzzle Command name.
   *
   * @covers ::getCommand
   *
   * @dataProvider providerTestGetCommand
   */
  public function testGetCommand($commandName) {
    $command = $this->client->getCommand($commandName);
    $this->assertNotEmpty($command);
    $this->assertInstanceOf('GuzzleHttp\Command\Guzzle\Operation', $command);
  }

  /**
   * Tests HttpClient::getCommand() with wrong argument.
   *
   * @covers ::getCommand
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage No operation found named Missing
   */
  public function testGetCommandWhichDoesNotExists() {
    $command = $this->client->getCommand('Missing');
    $this->assertEmpty($command);
  }

  /**
   * Tests HttpClient::call().
   *
   * @covers ::call
   */
  public function testCall() {
    $result = $this->client->call('FindPosts');
    $this->assertNotEmpty($result);
    $this->assertInstanceOf('GuzzleHttp\Command\Result', $result);
    $this->assertGreaterThan(1, $result->count());

    $result = $this->client->call('FindPost', ['postId' => 1]);
    $this->assertNotEmpty($result);
    $this->assertInstanceOf('GuzzleHttp\Command\Result', $result);
    $result = $result->toArray();
    $this->assertArrayHasKey('id', $result);
    $this->assertEquals(1, $result['id']);
    $this->assertCount(4, $result);
    $keys = [
      'userId',
      'id',
      'title',
      'body',
    ];
    $this->assertSame($keys, array_keys($result));
  }

  /**
   * Tests HttpClient::call() with wrong command name.
   *
   * @covers ::call
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage No operation found named Missing
   */
  public function testCallWithWrongCommandName() {
    $this->client->call('Missing');
  }

  /**
   * Tests HttpClient::__call().
   *
   * @covers ::__call
   */
  public function testMagicMethodCall() {
    $result = $this->client->findPosts();
    $this->assertNotEmpty($result);
    $this->assertInstanceOf('GuzzleHttp\Command\Result', $result);
    $this->assertGreaterThan(1, count($result));

    $result = $this->client->findPost(['postId' => 1]);
    $this->assertNotEmpty($result);
    $this->assertInstanceOf('GuzzleHttp\Command\Result', $result);
    $result = $result->toArray();
    $this->assertArrayHasKey('id', $result);
    $this->assertEquals(1, $result['id']);
    $this->assertCount(4, $result);
    $keys = [
      'userId',
      'id',
      'title',
      'body',
    ];
    $this->assertSame($keys, array_keys($result));
  }

  /**
   * Tests HttpClient::__call() with wrong command name.
   *
   * @covers ::__call
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage No operation found named Missing
   */
  public function testMagicMethodCallWithWrongCommandName() {
    $this->client->missing();
  }

  /**
   * Get Service api info.
   *
   * @return array
   *   An array of services api info.
   */
  protected function getServiceApiInfo() {
    return [
      "title" => "TestApi",
      "api_path" => "api/test_api.json",
      "config" => [
        "base_uri" => "http://jsonplaceholder.typicode.com",
      ],
      'id' => "mock",
      'provider' => "mock",
      'source' => __DIR__ . "/api/test_api.json",
    ];
  }

  /**
   * Data provider for testGetCommand().
   *
   * @return array
   *   An array of command names.
   */
  public function providerTestGetCommand() {
    return [
      ['FindPost'],
      ['FindPosts'],
      ['FindComments'],
    ];
  }

  /**
   * Initialize a new HttpClient instance.
   *
   * @param string $serviceApi
   *   The service api name.
   * @param array $serviceInfo
   *   An array of service info as described into an http_services_api.yml file.
   */
  private function initClient($serviceApi, array $serviceInfo) {
    $apiHandler = $this->prophesize(HttpServiceApiHandlerInterface::class);
    $apiHandler->load(Argument::any())->willReturn($serviceInfo);
    $apiHandler = $apiHandler->reveal();

    $event_dispatcher = $this->prophesize(EventDispatcherInterface::class)
      ->reveal();

    $this->client = new HttpClient($serviceApi, $apiHandler, $event_dispatcher);
  }

}
