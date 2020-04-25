# Drupal Test Traits

[![Build status](https://gitlab.com/weitzman/drupal-test-traits/badges/master/build.svg)](https://gitlab.com/weitzman/drupal-test-traits/commits/master)
[![project-stage-badge]][project-stage-page]
[![license-badge]][mit]

## Introduction

Traits for testing Drupal sites that have user content (versus unpopulated sites).

[Behat](http://behat.org) is great for facilitating conversations between business managers and developers. Those are useful conversations, but many organizations simply can't or won't converse via Gherkin. When you are on the hook for product quality and not conversations, this is a testing approach for you.

- Blog: [Introducing Drupal Test Traits](https://medium.com/massgovdigital/introducing-drupal-test-traits-9fe09e84384c)
- Blog: [Introducing Drupal Test Traits: Drupal extension for testing existing sites](https://www.previousnext.com.au/blog/introducing-drupal-testing-traits-drupal-extension-testing-existing-sites)

## Installation

- Install via Composer: `composer require weitzman/drupal-test-traits`.

## Authoring tests

Pick a test type:
- **ExistingSite**. See [ExampleTest.php](./tests/ExampleTest.php). Start here. These tests can be small unit tests up to larger functional tests (via [Goutte](http://goutte.readthedocs.io/en/latest/)). Tests of this type should be placed in `tests/src/ExistingSite`.
- **ExistingSiteSelenium2DriverTest**. See [ExampleSelenium2DriverTest.php](tests/ExampleSelenium2DriverTest.php). These tests make use of any browser which can run in web driver mode (Chrome, FireFox or Edge) via Selenium, so are suited to testing Ajax and similar client side interactions. This browser setup can also be used to run Drupal 8 core JS testing using [nightwatch](https://www.drupal.org/node/2968570). These tests run slower than ExistingSite. To use this test type you need to `composer require 'behat/mink-selenium2-driver'`. Tests of this type should be placed in `tests/src/ExistingSiteJavascript`.
- **ExistingSiteWebDriverTest**. See [ExampleWebDriverTest.php](tests/ExampleWebDriverTest.php). These tests make use of a headless Chrome browser, so are suited to testing Ajax and similar client side interactions. These tests run slower than ExistingSite. To use this test type you need to `composer require 'dmore/chrome-mink-driver'`. Tests of this type should be placed in `tests/src/ExistingSiteJavascript`.

Extend the base class that corresponds to your pick above: [ExistingSiteBase.php](src/ExistingSiteBase.php), [ExistingSiteSelenium2DriverTestBase.php](src/ExistingSiteSelenium2DriverTestBase.php), or  [ExistingSiteWebDriverTestBase.php](src/ExistingSiteWebDriverTestBase.php). You may extend it directly from your test class or create a base class for your project that extends one of these. To choose between Selenium2 or WebDriver for JS testing, read [testing Drupal with WebDriver browser mode vs Headless browser mode](https://www.previousnext.com.au/blog/testing-drupal-webdriver-browser-mode-vs-headless-browser-mode).
  
## Running tests

1. Create or edit `phpunit.xml` to include new testsuites for `existing-site` and `existing-site-javascript` ([example phpunit.xml](docs/phpunit.xml)).
2. Specify the URL to your existing site with `DTT_BASE_URL=http://example.com`. For ExistingSiteSelenium2DriverTest tests, also specify `DTT_MINK_DRIVER_ARGS=["firefox", null, "http://selenium:9222/wd/hub"]`. For ExistingSiteWebDriverTest tests, also specify `DTT_API_URL=http://localhost:9222`. You can also change timeouts like this: `DTT_API_OPTIONS={"socketTimeout": 360, "domWaitTimeout": 3600000}` (note the JSON string). You can specify these environment variables one of three ways:
    - Add them to your `phpunit.xml` ([example phpunit.xml](docs/phpunit.xml)).
    - Add them to your `.env` (supported by [drupal-project](https://github.com/drupal-composer/drupal-project/blob/8.x/.env.example) and [Docker](https://docs.docker.com/compose/env-file/)). 
    - Specify them at runtime: `DTT_BASE_URL=http://127.0.0.1:8888; DTT_API_URL=http://localhost:9222 vendor/bin/phpunit ...`
3. Run `phpunit` with the `--bootstrap` option: `vendor/bin/phpunit --bootstrap=vendor/weitzman/drupal-test-traits/src/bootstrap-fast.php ...`. This bootstrap can also be referenced in your `phpunit.xml` ([example phpunit.xml](docs/phpunit.xml)). Depending on your setup, you may wish to run `phpunit` as the web server user: `su -s /bin/bash www-data -c "vendor/bin/phpunit ..."`. If you get 'Class not found' errors, please see comments at top of [bootstrap-fast.php](src/bootstrap-fast.php)

You should limit these test runs to your custom functionality only; testing Core and Contrib modules is already the purview of DrupalCI. To do so, tell `phpunit` to only look for tests in a certain path, or use `@group` annotations to limit the scope. The following examples depend on a properly configured `phpunit.xml` ([example phpunit.xml](docs/phpunit.xml)):

```
vendor/bin/phpunit web/modules/custom
vendor/bin/phpunit --group CustomTestGroup
``` 

## Debugging tests

- All HTML requests can be logged. To do so, add `BROWSERTEST_OUTPUT_DIRECTORY=/tmp` and `--printer '\\Drupal\\Tests\\Listeners\\HtmlOutputPrinter'` to the `phpunit` call. To disable deprecation notices, include `SYMFONY_DEPRECATIONS_HELPER=disabled`. Alternatively, you can specify these in your `phpunit.xml` ([example phpunit.xml](docs/phpunit.xml)).  
- To check the current HTML of the page, use `file_put_contents('public://' . drupal_basename($this->getSession()->getCurrentUrl()) . '.html', $this->getCurrentPageContent());`.
- To take a screenshot of the current page under ExistingSiteSelenium2DriverTest or ExistingSiteWebDriverTest, use `file_put_contents('public://screenshot.jpg', $this->getSession()->getScreenshot());`.

## Available traits

- **DrupalTrait**  
  Bootstraps Drupal so that its APIs are available. Also offers an entity cleanup API so databases are kept relatively free of testing content.

- **GoutteTrait**  
  Makes Goutte available for browser control, and offers a few helper methods.

- **Selenium2DriverTrait**   
  Makes [Selenium2Driver](https://github.com/minkphp/MinkSelenium2Driver) available for browser control with Selenium. Suitable for functional JS testing.

- **WebDriverTrait**   
  Makes [ChromeDriver]([ChromeDriver](https://gitlab.com/DMore/chrome-mink-driver/)) available for browser control without the overhead of Selenium. Suitable for functional JS testing.

- **NodeCreationTrait**  
  Create nodes that are deleted at the end of the test method.
  
- **TaxonomyCreationTrait**
  Create terms and vocabularies that are deleted at the end of the test method.
  
- **UserCreationTrait**
  Create users and roles that are deleted at the end of the test method.
  
- **MailCollectionTrait**  
  Enables the collection of emails during tests. Assertions can be made against contents of these as core's `AssertMailTrait` is included.
  
- **ScreenShotTrait**  
  Allows for the capture of screenshots when using Webdriver or Selenium2 drivers. The destination directory can be set with `DTT_SCREENSHOT_REPORT_DIRECTORY`, which can also be set in `phpunit.xml` ([example phpunit.xml](docs/phpunit.xml)). Defaults to `/sites/simpletest/browser_output`.

## Contributed Packages

Submit a [merge request](https://docs.gitlab.com/ee/user/project/merge_requests/) to get your trait added to this list.

Use `type="drupal-dtt"` in your package's `composer.json` in order to appear in [this list](https://packagist.org/?type=drupal-dtt).

- [LoginTrait](https://gitlab.com/weitzman/logintrait.git). Provides login/logout via user reset URL instead of forms. Useful when TFA/SAML are enabled.
- [QueueRunnerTrait](https://github.com/drupaltest/queue-runner-trait/). Provides methods to clear and run queues during tests.
  
## Contributing

Contributions to this project are welcome! Please file issues and pull requests.
All pull requests are automatically tested via [GitLab CI](https://gitlab.com/weitzman/drupal-test-traits/pipelines).

See `docker-compose.yml` for a handy development environment.

See the [#testing channel on Drupal Slack](https://drupal.slack.com/messages/C223PR743) for discussion about this project. 

## Colophon

- **Author**: [Moshe Weitzman](http://weitzman.github.io).
- **Maintainers**: [Moshe Weitzman](http://weitzman.github.io), [Jibran Ijaz (jibran)](https://www.drupal.org/u/jibran), [Rob Bayliss](https://github.com/rbayliss), and the community.
- **License**: [MIT license][mit]

[mit]: ./LICENSE.md
[license-badge]: https://img.shields.io/badge/License-MIT-blue.svg
[project-stage-badge]: http://img.shields.io/badge/Project%20Stage-Development-yellowgreen.svg
[project-stage-page]: http://bl.ocks.org/potherca/raw/a2ae67caa3863a299ba0/
