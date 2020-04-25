<?php

namespace weitzman\DrupalTestTraits;

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Exception\DriverException;

trait Selenium2DriverTrait
{

  /**
   * @return \Behat\Mink\Driver\DriverInterface
   * @throws \Behat\Mink\Exception\DriverException
   *   Thrown when invalid arguments are passed.
   */
    protected function getDriverInstance()
    {
        if (!isset($this->driver) && ($driverArgs = getenv('DTT_MINK_DRIVER_ARGS') ?: '["firefox", null, "http://localhost:4444/wd/hub"]')) {
            $driverArgs = json_decode($driverArgs, true);
            if (!$driverArgs) {
                throw new DriverException('Invalid driver arguments given for "DTT_MINK_DRIVER_ARGS", make sure to use double quotes inside the brackets.');
            }
            $this->driver = new Selenium2Driver(...$driverArgs);
        }
        return $this->driver;
    }

    /**
     * Returns headers in HTML output format.
     *
     * Response headers are unavailable with Selenium2Driver - return nothing and
     * avoid an Exception.
     *
     * @return string
     *   HTML output headers.
     */
    protected function getHtmlOutputHeaders()
    {
        return '';
    }
}
