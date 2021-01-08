## Leaflet

###General Information

**Leaflet** module provides integration with
[Leaflet map scripting library](http://leafletjs.com).

It is based and dependant from:
- the [Leaflet JS library](http://leafletjs.com);
- the [Geofield](https://www.drupal.org/project/geofield) Module;

###Installation and Use

- Require/Download the Leaflet module
[using Composer to manage Drupal site dependencies](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies)__,
which will also download the required
[Geofield Module](https://www.drupal.org/project/geofield)
dependency and GeoPHP library.
It is done simply running the following command from your project package root
(where the main composer.json file is sited):

    `composer require drupal/leaflet`

- Enable the **Leaflet** module to be able to use:
    - the configurable **Leaflet Map** as Geofield Widget, with [Leaflet Geoman js library](https://geoman.io/leaflet-geoman);
    - the configurable **Leaflet Map** as Geofield Formatter;

- Enable **Leaflet Views** (leaflet_views) submodule for **Leaflet Map Views
integration**
You need to add at least one geofield to the Fields list, and select the Leaflet
Map style in the Display Format.
In the settings of the style, select the geofield as the Data Source and select
a field for Title and Description (which will be rendered in the popup).

- Enable **Leaflet Markercluster** (leaflet_markercluster) submodule for
[__Leaflet Markercluster Js library__](https://github.com/Leaflet/Leaflet.markercluster) functionalities and configurations, both
in the Leaflet Formatter and in the Leaflet Map View display.

- Add, enable and configure ["Geoocoder" module](https://www.drupal.org/project/geocoder) to enable Geocoder Control
 (with Autocomplete) for quick Leaflet Map Address search & pan/zoom.

As a more powerful alternative, you can use node view modes to be rendered in
the popup. In the Description field, select "<entire node>" and then select a
View mode.

###API Usage

Rendering a map is as simple as instantiating the LeafletService and its
leafletRenderMap method

    \Drupal::service('leaflet.service')->leafletRenderMap($map, $features, $height)

which takes 3 parameters:

* $map:
An associative array defining a map. See hook_leaflet_map_info(). The module
defines a default map with a OpenStreet Maps base layer.

* $features:
This is an associative array of all the Leaflet features you
want to plot on the map. A feature can be a point, linestring, polygon,
multipolygon, multipolygon, or json object. Additionally, features can be
grouped into [leaflet layer groups](http://leafletjs.com/reference-1.3.0.html#layergroup)
so they can be controlled together,

* $height:
The map height, expressed in css units.

###Tips & Tricks

- ####Bind events on geojson (json) features
  @see: https://www.drupal.org/project/leaflet/issues/3186029

  $features[] = [
    'type' => 'json',
    'json' => $geojson,
    'events' => [
      'click' => 'Drupal.manageGeojsonClick', // or whatever callback
    ],
  ];
  $this->leaflet->leafletRenderMap($map_info, $features, $height),

####Authors/Credits

* [itamair](https://www.drupal.org/u/itamair)
* [levelos](http://drupal.org/user/54135)
* [pvhee](http://drupal.org/user/108811)
