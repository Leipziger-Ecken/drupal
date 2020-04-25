<?php

namespace weitzman\DrupalTestTraits;

use DMore\ChromeDriver\ChromeDriver;

trait WebDriverTrait
{

    /**
     * @return \Behat\Mink\Driver\DriverInterface
     */
    protected function getDriverInstance()
    {
        if (!isset($this->driver)) {
            $driverApiUrl = getenv('DTT_API_URL') ?: 'http://127.0.0.1:9222';
            $driverApiOptions = json_decode(getenv('DTT_API_OPTIONS'), true) ?: ['socketTimeout' => 10, 'domWaitTimeout' => 3000];
            $this->driver = new ChromeDriver($driverApiUrl, null, $this->baseUrl, $driverApiOptions);
        }
        return $this->driver;
    }
}
