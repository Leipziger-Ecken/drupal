INTRODUCTION
------------

### **Geofield Map**

is an advanced, complete and easy-to-use Geo Mapping solution for Drupal 8, 
based on and fully compatible with the 
[Geofield](https://www.drupal.org/project/geofield "Geofield") module, 
that **lets you manage the Geofield with an interactive Map both in back-end 
and in the front-end.** It represents the perfect solution to:

*   geolocate (with one or more Locations / Geofields) any fieldable Drupal 
entity throughout an Interactive Geofield Map widget;
*   render each Content's Locations throughout a fully customizable Interactive 
Geofield Map Formatter;
*   expose and query Contents throughout fully customizable Map Views 
Integration;
*   implement advanced front-end Google Maps with Marker Icon & Infowindow 
advanced customizations, custom Google Map Styles and Marker Clustering 
capabilities;
*   customize Map Geometries properties (Lines & Polylines, Polygons, 
Multipolygons, etc.), based on Google Maps Polygons APIs

REQUIREMENTS
------------
This module requires the following modules:

 * Geofield (https://www.drupal.org/project/geofield)
 
INSTALLATION
------------
 
Download the module using Composer with the following command:
 
  `composer require drupal/geofield_map`
  
  (that will also download the dependency module Geofield)
  
   Install as you would normally install a contributed module from the Drupal 
   backend,    or using Drush, with the following command:

 `drush en geofield_map`
 
CONFIGURATION
-------------

The module provides a Geofield Map settings page at the following path:

`/admin/config/system/geofield_map_settings`

accessible to every user role granted the `Configure Geofield Map` permission.
 
MAINTAINERS
------------

Original Author and Maintainer for Drupal 8:    
[itamair (Italo Mairo)](https://www.drupal.org/u/itamair)  


TECHNICAL SPECIFICATIONS
------------

#### Geofield Map 2.x. What's New

### **Dynamic Map Markers Theming & Contextual Legends.**

The Geofield Map 2.x version allows the Geofield Map View definition of Custom 
Markers & Icons Images based on dynamic values of the Map features.

Moreover, as an absolute novelty and uniqueness in the history of Drupal CMS 
(!), 
a custom Geofield Map Legend Block is defined by the module and is 
able to expose each Map Theming logics in the form of fully configurable and 
impressive Legends.

### **Technical Functionalities and specifications**

The actual module release implements the following components:

#### **Geofield Map widget**

An highly customizable Map widget, providing an interactive and very intuitive 
map on which to perform localization and input of geographic coordinates 
throughout:

*   MULTIPOINTS Geofield mapping support;
*   Search Address Geocoding throughout [Google Maps Geocoder service](https://developers.google.com/maps/documentation/javascript/geocoding) and 
[Google Maps Places Autocomplete Service](https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete)
or throughout other Geocoder providers provided by the 
[Geocoder module](https://www.drupal.org/project/geocoder) integration ;
*   Google Map or Leaflet Map JS libraries and Mapping UXs;
*   Map click and marker dragging, with instant reverse geocoding;
*   HTML5 Geolocation of the user position;
*   the possibility to permanently store the Geocoded address into the Entity 
Title or in a "string type" field (among the content's ones);
*   etc.

#### **Geofield Map Formatter**

An highly customizable Google Map formatter, by which render and expose the 
contents Geofields / Geolocations, throughout:

*   a wide set of Map options fully compliant with 
[Google Maps Js v3 APIs](https://developers.google.com/maps/documentation/javascript/);
*   the possibility to fully personalize the Map Marker Icon and its Infowindow 
content;
*   the integration of 
[Markecluster Google Maps Library](https://github.com/googlemaps/js-marker-clusterer) 
functionalities and its personalization;

Additional formatters (**Geofield Map Static Formatter and Geofield Map Embed 
Formatter**) are provided in the sub-module **geofield_map_extras**.
Please refer to its README.md file for more information.

#### Views Integration

A dedicated Geofield Map View style plugin able to render a Views result on a 
higly customizable Google Map, with Marker and Infowindow specifications and 
Markers Clustering capabilities.

#### Advanced Google Map and Markeclustering Features for the front-end maps

Both in Geofield Map Formatter and in the Geofield Map View style it is 
possible:

*   to add additional Map and Markecluster Options, as Object Literal in valid 
Json format;
*   define and manage a
[Google Custom Map Style](https://developers.google.com/maps/documentation/javascript/examples/maptype-styled-simple)
*   use the 
[Overlapping Marker Spiderfier Library (for Google Maps)](https://github.com/jawj/OverlappingMarkerSpiderfier#overlapping-marker-spiderfier-for-google-maps-api-v3) 
to manage overlapping markers;

### **Basic Installation and Use**

Geofield Map module needs to be installed 
[using Composer to manage Drupal site dependencies](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies), which will also download the required [Geofield Module](https://www.drupal.org/project/geofield) dependency and PHP libraries).

It means simply running the following command from your project root 
(where the main composer.json file is sited):

**$ composer require 'drupal/geofield_map'**

Once done, you can setup the following:

*   Geofield Widget: In a Content Type including a Geofield Field, go to 
"Manage form display" and select "Geofield Map" as Geofield Widget. Specify the
 Widget further settings for both Google or Leaflet Map types;
*   Geofield Google Map Formatter: In a Content Type including a Geofield Field,
go to "Manage display" and select "Geofield Google Map" as Geofield field 
Formatter. Specify the Formatter further settings for specific personalization;
*   Geofield Map Views: In a View Display select the Geofield Google Map Format,
 and be sure to add a Geofield type field in the fields list. Specify the View 
 Format settings for specific personalization;

#### Hints for Advanced Use

*   For each GeofieldMapWidget it is possible the enable (and custom configure) 
addresses Geocoding via the 
[Google Maps Places Autocomplete Service](https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete).
*   GeofieldMapWidget uses Leaflet MapTypes/Tiles pre-defined as 
LeafletTileLayers D8 plugins, but any third party module is able to define and 
add its new LeafletTileLayer Plugins;
*   As default (configurable) option, eventual overlapping markers will be 
Spiderfied, with the support of the 
[Overlapping Marker Spiderfier Library (for Google Maps)](https://github.com/jawj/OverlappingMarkerSpiderfier#overlapping-marker-spiderfier-for-google-maps-api-v3 "Overlapping Marker Spiderfier Library (for Google Maps)");
* Add, enable and configure 
["Geoocoder" module for D8](https://www.drupal.org/project/geocoder) to enable 
Geocoder Control (with Autocomplete) for quick Address search and Geofield Map 
Pan & Zoom functionalities;
*   The Geofield Map View style plugin will pass to the client js 
(as drupalSettings.geofield_google_map[mapid] & Drupal.geoFieldMap[mapid] 
variables) the un-hidden fields values of the View, as markers/features' 
properties data;

### **Geofield Map 2.x Dynamic Markers Theming & Legends Specifications**

Geofield Map 2.x introduces the MapThemer Plugin system that allows the 
definition of MapThemer Plugins able to dynamically differentiate Map 
Features/Markers based on Contents Types, Taxonomy Terms, Values, etc. Each 
Plugin Type provides the automatic definition of a related Legend Build, that 
is able to fill the definition of a Custom GeofieldMapLegend block.

At the moment the following two Geofield Map Themers plugin types have been 
defined:

*   Custom Icon Image File, allows the definition of a unique custom Marker 
Icon, valid for all the Map Markers;
*   Entity Type, allows the definition of different Marker Icons based on the 
View filtered Entity Types/Bundles;
*   Taxonomy Term, allows the definition of different Marker Icons based on 
Taxonomy Terms reference field in View;
*   List Type Field, allows the definition of different Marker Icons based on 
List (Options) Type fields in View;

As Drupal 8 Plugin system based, the Geofield MapThemers Plugin and Legend 
block system is fully extendable and overridable. You, as D8 developer, are 
free to override and extend the existing ones, or create your custom MapThemer 
based on your specific needs and logics.

#### How to configure and use.

You are advised to use Geofield Map Themers based on pre-defined file path 
selection (insetad of file managed upload) so to make your local settings 
part of the Drupal 8 configuration management, and thus compatible with its
synchronization with stage and prod environments, and continuous integration.  

In the Geofield Map configuration page ('/admin/config/system/geofield_map_settings') 
it is possible to define the custom path location of the folder that the module
would look for markers icons into (default: 'public://geofieldmap_icons').
After defining it (confirming the default one or customizing your specific one)
you need to initially fill (and integrate afterword) it with your custom 
icons that would then be available as custom markers to the Map Theming engine. 
**Hint:** The module stores some example custom markers icons in its 
"marker_icons_samples" folder.

In a Geofield Map View Display, just go into its settings and choose the wanted 
MapThemer in the new Map Theming Options section/fieldset. It is possible to 
associate a Drupal File for each MapThemer plugin value and even the 
Image style the Icon should be rendered on the Map with. The Value labels and 
Icons might have an alias, might be reordered and might be hidden from the 
correspondent Legend Block.

Once defined and configured the Legend you are free to place it, once or 
several times, as a normal Drupal 8 block on the pages, with your logics and 
contextual rules.

#### **Notes & Warnings**

*   The Geofield Map module depends from the 
[Geofield](https://www.drupal.org/project/geofield) module;
*   A unique 
[Gmap Api Key](https://developers.google.com/maps/documentation/javascript/get-api-key) 
is required for both Google Mapping and Geocoding operations, all performed 
client-side by js. 
It might/should be restricted using the 
[Website Domain / HTTP referrers method](https://developers.google.com/maps/documentation/javascript/get-api-key#key-restrictions).

#### **Roadmap / Planned evolution**
*   Full integration with [Geocoder](https://www.drupal.org/project/geocoder) 
module;
*   Leaflet Map library support for the Geofield Map Formatter and the Map 
Views Plugin. Now please refer to the 
[Leaflet](https://www.drupal.org/project/leaflet "Leaflet") and the 
[Leaflet Markercluster](https://www.drupal.org/project/leaflet_markercluster) 
modules for Leaflet front-end mapping of Drupal 8 Geofields;
