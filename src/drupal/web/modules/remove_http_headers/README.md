INTRODUCTION
------------

The Remove HTTP headers module removes configured HTTP headers 
from the response. Also removes 
`<meta name="Generator" content="Drupal 8 (https://www.drupal.org)">` 
from the `<head>` tag if the **X-Generator** HTTP header is configured 
to be removed. By default the **X-Generator** and 
**X-Drupal-Dynamic-Cache** HTTP headers are configured to be removed.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/remove_http_headers

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/remove_http_headers

REQUIREMENTS
------------

* This module requires Drupal 8.6 or above.
* No additional modules are needed.

INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

CONFIGURATION
-------------
 
 * Configure the HTTP headers that should be removed on the settings page 
 (/admin/config/system/remove-http-headers) or directly 
 in the *remove_http_headers.settings.yml* configuration file.
  
    - *remove_http_headers.settings.yml* format:
      ```yaml
      headers_to_remove:
      - 'X-Generator' 
      - 'X-Drupal-Dynamic-Cache'
      - 'X-Drupal-Cache'
      ```


MAINTAINERS
-----------

**Current maintainers:**
 * Orlando Thöny - https://www.drupal.org/u/orlandothoeny

**This project has been sponsored by:**

 * **Namics** - Initial development
 
   Namics is one of the leading providers of e-business and digital brand 
   communication services in the German-speaking region. The full-service agency 
   helps companies transform business models with top-quality interdisciplinary 
   solutions, promising increased, measurable success for their clients. 
   Visit https://www.namics.com/en for more information.
