<?php

namespace weitzman\DrupalTestTraits;

use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Mink;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Session;
use Drupal\Tests\HiddenFieldSelector;
use GuzzleHttp\Client;

trait GoutteTrait
{

    /**
     * @var \Behat\Mink\Mink
     */
    protected $mink;

    /**
     * @var \Behat\Mink\Driver\DriverInterface
     */
    protected $driver;

    protected $baseUrl;

    /**
     * @return \Behat\Mink\Driver\DriverInterface
     */
    protected function getDriverInstance()
    {
        if (!isset($this->driver)) {
            $this->driver = new GoutteDriver();
        }
        return $this->driver;
    }

    /**
     *
     * Setup a Mink session. Call this from your setUp() method.
     *
     */
    protected function setupMinkSession()
    {
        if (empty($this->baseUrl)) {
            $this->baseUrl = getenv('DTT_BASE_URL') ?: 'http://localhost:8000';
        }

        $driver = $this->getDriverInstance();
        $selectors_handler = new SelectorsHandler([
          'hidden_field_selector' => new HiddenFieldSelector(),
        ]);
        $session= new Session($driver, $selectors_handler);
        $this->mink = new Mink([
            'default' => $session,
        ]);
        $this->mink->setDefaultSessionName('default');
        $session->start();

        // Create the artifacts directory if necessary.
        $output_dir = getenv('BROWSERTEST_OUTPUT_DIRECTORY');
        if ($output_dir && !is_dir($output_dir)) {
            mkdir($output_dir, 0777, true);
        }

        if ($driver instanceof GoutteDriver) {
            // Inject a Guzzle middleware to generate debug output for every request
            // performed in the test.

            // Turn off curl timeout. Having a timeout is not a problem in a normal
            // test running, but it is a problem when debugging. Also, disable SSL
            // peer verification so that testing under HTTPS always works.
            $client = new Client(['timeout' => null, 'verify' => false]);
            $handler_stack = $client->getConfig('handler');
            $handler_stack->push($this->getResponseLogHandler());
            $driver->getClient()->setClient($client);
        }

        // According to the W3C WebDriver specification a cookie can only be set if
        // the cookie domain is equal to the domain of the active document. When the
        // browser starts up the active document is not our domain but 'about:blank'
        // or similar. To be able to set our User-Agent and Xdebug cookies at the
        // start of the test we now do a request to the front page so the active
        // document matches the domain.
        // @see https://w3c.github.io/webdriver/webdriver-spec.html#add-cookie
        // @see https://www.w3.org/Bugs/Public/show_bug.cgi?id=20975
        $this->visit($this->baseUrl . '/core/misc/druplicon.png');
    }

    /**
     * Stop session. Call this from your tearDown() method.
     */
    protected function tearDownMinkSession()
    {
        $this->getSession()->stop();
        // Avoid leaking memory in test cases (which are retained for a long time)
        // by removing references to all the things.
        $this->mink = null;
    }

    protected function getSession($name = null)
    {
        return $this->mink->getSession($name);
    }

    protected function getCurrentPage()
    {
        return $this->getSession()->getPage();
    }

    protected function getCurrentPageContent()
    {
        return $this->getCurrentPage()->getContent();
    }

    protected function visit($url)
    {
        if (!parse_url($url, PHP_URL_SCHEME)) {
            $url = $this->baseUrl . $url;
        }
        $this->getSession()->visit($url);
    }
}
