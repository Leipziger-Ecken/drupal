/**
 * @file
 * Javascript for the Geofield Map widget.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.geofieldMapInit = {
    attach: function (context, drupalSettings) {

      // Init all maps in drupalSettings.
      $.each(drupalSettings['geofield_map'], function (mapid, options) {

        // Define the first map id, for a multivalue geofield map.
        if (mapid.indexOf('0-value') !== -1) {
          Drupal.geoFieldMap.firstMapId = mapid;
        }
        // Check if the Map container really exists and hasn't been yet initialized.
        if ($('#' + mapid, context).length > 0 && !Drupal.geoFieldMap.map_data[mapid]) {

          // Set the map_data[mapid] settings.
          Drupal.geoFieldMap.map_data[mapid] = options;

          // Google maps library shouldn't be requested if the following
          // conditions apply:
          // - leaflet js is the chosen map library;
          // - geocoder integration is enabled;
          if (options.map_library === 'leaflet' && options.gmap_geocoder) {
            Drupal.geoFieldMap.map_initialize(options, context);
          }
          else {
            // Load before the Gmap Library, if needed, then initialize the Map.
            Drupal.geoFieldMap.loadGoogle(mapid, options.gmap_api_key, function () {
              Drupal.geoFieldMap.map_initialize(options, context);
            });
          }
        }
      });

    }
  };

  Drupal.geoFieldMap = {

    geocoder: null,
    map_data: {},
    firstMapId: null,

    // Google Maps are loaded lazily. In some situations load_google() is called twice, which results in
    // "You have included the Google Maps API multiple times on this page. This may cause unexpected errors." errors.
    // This flag will prevent repeat $.getScript() calls.
    maps_api_loading: false,

    /**
     * Returns the re-coded google maps api language parameter, from html lang
     * attribute.
     *
     * @param {string} html_language - The language id string
     *
     * @return {string} - The transformed language id string
     */
    googleMapsLanguage: function (html_language) {
      switch (html_language) {
        case 'zh-hans':
          html_language = 'zh-CN';
          break;

        case 'zh-hant':
          html_language = 'zh-TW';
          break;
      }
      return html_language;
    },

    /**
     * Provides the callback that is called when maps loads.
     */
    googleCallback: function () {
      let self = this;
      // Wait until the window load event to try to use the maps library.
      $(document).ready(function (e) {
        _.each(self.googleCallbacks, function(callback) {
          callback.callback();
        });
        self.googleCallbacks = [];
      });
    },

    /**
     * Adds a callback that will be called once the maps library is loaded.
     *
     * @param {string} callback - The callback
     */
    addCallback: function (callback) {
      let self = this;
      // Ensure callbacks array.
      self.googleCallbacks = self.googleCallbacks || [];
      self.googleCallbacks.push({callback: callback});
    },

    /**
     * Load Google Maps library.
     *
     * @param {string} mapid - The map id
     * @param {string} gmap_api_key - The gmap api key
     * @param {object} callback - the Callback function
     */
    loadGoogle: function (mapid, gmap_api_key, callback) {
      let self = this;
      let html_language = $('html').attr('lang') || 'en';

      // Add the callback.
      self.addCallback(callback);

      // Check for google maps.
      if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        if (self.maps_api_loading === true) {
          return;
        }

        self.maps_api_loading = true;

        // Google maps isn't loaded so lazy load google maps.
        // Default script path.
        let scriptPath = self.map_data[mapid]['gmap_api_localization'] + '?v=3.exp&sensor=false&libraries=places&language=' + self.googleMapsLanguage(html_language);

        // If a Google API key is set, use it.
        if (gmap_api_key) {
          scriptPath += '&key=' + gmap_api_key;
        }

        $.getScript(scriptPath)
          .done(function () {
            self.maps_api_loading = false;
            self.googleCallback();
          });

      }
      else {
        // Google maps loaded. Run callback.
        self.googleCallback();
      }
    },

    // Center the map to the marker position.
    find_marker: function (mapid) {
      let self = this;
      self.mapSetCenter(mapid, self.getMarkerPosition(mapid));
    },

    // Place marker at the current center of the map.
    place_marker: function (mapid) {
      let self = this;
      if (self.map_data[mapid].click_to_place_marker) {
        if (!window.confirm('Change marker position ?')) {
          return;
        }
      }
      let position = self.map_data[mapid].map.getCenter();
      self.setMarkerPosition(mapid, position);
      self.geofields_update(mapid, position);
    },

    // Geofields update.
    geofields_update: function (mapid, position) {
      let self = this;
      self.setLatLngValues(mapid, position);
      self.reverse_geocode(mapid, position);
    },

    // Onchange of Geofields.
    geofield_onchange: function (mapid) {
      let self = this;
      let position = {};
      switch (self.map_data[mapid].map_library) {
        case 'leaflet':
          position = L.latLng(
            $('#' + self.map_data[mapid].latid).val(),
            $('#' + self.map_data[mapid].lngid).val()
          );
          break;

        case 'gmap':
          position = new google.maps.LatLng(
            $('#' + self.map_data[mapid].latid).val(),
            $('#' + self.map_data[mapid].lngid).val()
          );
      }
      self.setMarkerPosition(mapid, position);
      self.mapSetCenter(mapid, position);
      self.setZoomToFocus(mapid);
      self.reverse_geocode(mapid, position);
    },

    // Coordinates update.
    setLatLngValues: function (mapid, position) {
      let self = this;
      switch (self.map_data[mapid].map_library) {
        case 'leaflet':
          $('#' + self.map_data[mapid].latid).val(position.lat.toFixed(6));
          $('#' + self.map_data[mapid].lngid).val(position.lng.toFixed(6));
          break;

        case 'gmap':
          $('#' + self.map_data[mapid].latid).val(position.lat().toFixed(6));
          $('#' + self.map_data[mapid].lngid).val(position.lng().toFixed(6));
      }
    },

    // Set the Reverse Geocode result into the Client Side Storage.
    set_reverse_geocode_storage: function (mapid, latlng, address) {
      let self = this;
      let storage_type = self.map_data[mapid].geocode_cache.clientside;
      switch (storage_type) {
        case 'session_storage':
          sessionStorage.setItem('Drupal.geofield_map.reverse_geocode.' + latlng, address);
          break;

        case 'local_storage':
          localStorage.setItem('Drupal.geofield_map.reverse_geocode.' + latlng, address);
          break;
      }
    },

    // Get the Reverse Geocode result from Client Side Storage.
    get_reverse_geocode_storage: function (mapid, latlng) {
      let self = this;
      let result;
      let storage_type = self.map_data[mapid].geocode_cache.clientside;
      switch (storage_type) {
        case 'session_storage':
          result = sessionStorage.getItem('Drupal.geofield_map.reverse_geocode.' + latlng);
          break;

        case 'local_storage':
          result = localStorage.getItem('Drupal.geofield_map.reverse_geocode.' + latlng);
          break;

        default:
          result = null;
      }
      return result;
    },

    // Reverse geocode.
    reverse_geocode: function (mapid, position) {
      let self = this;
      let latlng;
      switch (self.map_data[mapid].map_library) {
        case 'leaflet':
          latlng = position.lat.toFixed(6) + ',' + position.lng.toFixed(6);
          break;

        case 'gmap':
          latlng = position.lat().toFixed(6) + ',' + position.lng().toFixed(6);
      }
      // Check the result from the chosen client side storage, and use it eventually.
      let reverse_geocode_storage = self.get_reverse_geocode_storage(mapid, latlng);
      if (localStorage && self.map_data[mapid].geocode_cache.clientside && self.map_data[mapid].geocode_cache.clientside !== '_none_' && reverse_geocode_storage !== null) {
        self.map_data[mapid].search.val(reverse_geocode_storage);
        self.setGeoaddressField(mapid, reverse_geocode_storage);
      }
      else if (self.map_data[mapid].gmap_geocoder === 1) {
        let providers = self.map_data[mapid].gmap_geocoder_settings.providers.toString();
        let options = self.map_data[mapid].gmap_geocoder_settings.options;
        self.geocoder_reverse_geocode(latlng, providers, options).done(function (results, status, jqXHR) {
          if(status === 'success' && results[0]) {
            self.set_reverse_geocode_result(mapid, latlng, results[0].formatted_address)
          }
        });
      }
      else if (self.geocoder) {
        self.geocoder.geocode({latLng: position}, function (results, status) {
          if (status === google.maps.GeocoderStatus.OK && results[0]) {
            self.set_reverse_geocode_result(mapid, latlng, results[0].formatted_address)
          }
        });
      }
      return status;
    },

    // Write the Reverse Geocode result in the Search Input field, in the
    // Geoaddress-ed field and in the Localstorage.
    set_reverse_geocode_result: function (mapid, latlng, formatted_address) {
      let self = this;
      self.map_data[mapid].search.val(formatted_address);
      self.setGeoaddressField(mapid, formatted_address);
      // Set the result into the chosen client side storage.
      if (localStorage && self.map_data[mapid].geocode_cache.clientside && self.map_data[mapid].geocode_cache.clientside !== '_none_') {
        self.set_reverse_geocode_storage(mapid, latlng, formatted_address);
      }
    },

    // Triggers the Geocode on the Geofield Map Widget.
    trigger_geocode: function (mapid, position) {
      let self = this;
      self.setMarkerPosition(mapid, position);
      self.mapSetCenter(mapid, position);
      self.setZoomToFocus(mapid);
      self.setLatLngValues(mapid, position);
      self.setGeoaddressField(mapid, self.map_data[mapid].search.val());
    },

    // Define a Geographical point, from coordinates.
    getLatLng: function (mapid, lat, lng) {
      let self = this;
      let latLng = {};
      switch (self.map_data[mapid].map_library) {
        case 'leaflet':
          latLng = L.latLng(lat, lng);
          break;

        case 'gmap':
          latLng = new google.maps.LatLng(lat, lng);
      }
      return latLng;
    },

    // Returns the Map Bounds, in the specific Map Library format.
    getMapBounds: function (mapid, map_library) {
      let self = this;
      let mapid_map_library = self.map_data[mapid].map_library;
      let ne;
      let sw;
      let bounds;
      let bounds_array;
      let bounds_obj;

      if (!map_library) {
        map_library = mapid_map_library;
      }

      // Define the bounds object.
      // Note: At the moment both Google Maps and Leaflet libraries use the same method names.
      bounds = self.map_data[mapid].map.getBounds();
      if (typeof bounds === 'object') {
        ne = bounds.getNorthEast();
        sw = bounds.getSouthWest();
        bounds_array = [sw, ne];
        switch (map_library) {
          case 'leaflet':
            bounds_obj = new L.latLngBounds(bounds_array);
            break;

          case 'gmap':
            bounds_obj = new google.maps.LatLngBounds(bounds_array);
        }
      }
      return bounds_obj;
    },

    // Define the Geofield Map.
    getGeofieldMap: function (mapid) {
      let self = this;
      let map = {};
      let zoom_start = self.map_data[mapid].entity_operation !== 'edit' ? Number(self.map_data[mapid].zoom_start) : Number(self.map_data[mapid].zoom_focus);
      let zoom_min = Number(self.map_data[mapid].zoom_min);
      let zoom_max = Number(self.map_data[mapid].zoom_max);
      switch (self.map_data[mapid].map_library) {
        case 'leaflet':
          map = L.map(mapid, {
            center: self.map_data[mapid].position,
            zoom: zoom_start,
            minZoom: zoom_min,
            maxZoom: zoom_max
          });

          let baseLayers = {};
          for (let key in self.map_data[mapid].map_types_leaflet) {
            if (self.map_data[mapid].map_types_leaflet.hasOwnProperty(key)) {
              baseLayers[key] = L.tileLayer(self.map_data[mapid].map_types_leaflet[key].url, self.map_data[mapid].map_types_leaflet[key].options);
            }
          }
          baseLayers[self.map_data[mapid].map_type].addTo(map);
          if (self.map_data[mapid].map_type_selector) {
            L.control.layers(baseLayers).addTo(map);
          }

          break;

        case 'gmap':
          let options = {
            zoom: zoom_start,
            minZoom: zoom_min,
            maxZoom: zoom_max,
            center: self.map_data[mapid].position,
            mapTypeId: self.map_data[mapid].map_type,
            mapTypeControl: !!self.map_data[mapid].map_type_selector,
            mapTypeControlOptions: {
              position: google.maps.ControlPosition.TOP_RIGHT
            },
            scaleControl: true,
            streetViewControlOptions: {
              position: google.maps.ControlPosition.TOP_RIGHT
            },
            zoomControlOptions: {
              style: google.maps.ZoomControlStyle.LARGE,
              position: google.maps.ControlPosition.TOP_LEFT
            }
          };
          map = new google.maps.Map(document.getElementById(mapid), options);
      }
      return map;
    },

    setZoomToFocus: function (mapid) {
      let self = this;
      switch (self.map_data[mapid].map_library) {
        case 'leaflet':
          self.map_data[mapid].map.setZoom(self.map_data[mapid].zoom_focus, {animate: false});
          break;

        case 'gmap':
          self.map_data[mapid].map.setZoom(self.map_data[mapid].zoom_focus);
      }
    },

    setMarker: function (mapid, position) {
      let self = this;
      let marker = {};
      switch (self.map_data[mapid].map_library) {
        case 'leaflet':
          marker = L.marker(position, {draggable: true});
          marker.addTo(self.map_data[mapid].map);
          break;

        case 'gmap':
          marker = new google.maps.Marker({
            map: self.map_data[mapid].map,
            draggable: self.map_data[mapid].widget
          });
          marker.setPosition(position);
      }
      return marker;
    },

    setMarkerPosition: function (mapid, position) {
      let self = this;
      switch (self.map_data[mapid].map_library) {
        case 'leaflet':
          self.map_data[mapid].marker.setLatLng(position);
          break;

        case 'gmap':
          self.map_data[mapid].marker.setPosition(position);
      }
    },

    getMarkerPosition: function (mapid) {
      let self = this;
      let latLng = {};
      switch (self.map_data[mapid].map_library) {
        case 'leaflet':
          latLng = self.map_data[mapid].marker.getLatLng();
          break;

        case 'gmap':
          latLng = self.map_data[mapid].marker.getPosition();
      }
      return latLng;
    },

    mapSetCenter: function (mapid, position) {
      let self = this;
      switch (self.map_data[mapid].map_library) {
        case 'leaflet':
          self.map_data[mapid].map.panTo(position, {animate: false});
          break;

        case 'gmap':
          self.map_data[mapid].map.setCenter(position);
      }
    },

    setGeoaddressField: function (mapid, address) {
      let self = this;
      if (mapid && self.map_data[mapid].geoaddress_field) {
        self.map_data[mapid].geoaddress_field.val(address);
      }
    },

    map_refresh: function (mapid) {
      let self = this;
      setTimeout(function () {
        google.maps.event.trigger(self.map_data[mapid].map, 'resize');
        self.find_marker(mapid);
      }, 10);
    },

    // Init Geofield Map and its functions.
    map_initialize: function (params, context) {
      let self = this;
      $.noConflict();

      if (params.searchid !== null) {

        // If not enabled Geocoding based upn Geocoder module,
        // define a google Geocoder, if not yet done.
        if (params.gmap_geocoder !== 1 && !self.geocoder) {
          self.geocoder = new google.maps.Geocoder();
        }

        // Define the Geocoder Search Field Selector.
        self.map_data[params.mapid].search = $('#' + params.searchid);
      }

      // Define the Geoaddress Associated Field Selector, if set.
      if (params.geoaddress_field_id !== null) {
        self.map_data[params.mapid].geoaddress_field = $('#' + params.geoaddress_field_id);
      }

      // Define the Geofield Position.
      let position = self.getLatLng(params.mapid, params.lat, params.lng);
      self.map_data[params.mapid].position = position;

      // Define the Geofield Map.
      let map = self.getGeofieldMap(params.mapid);

      // Define a map self property, so other code can interact with it.
      self.map_data[params.mapid].map = map;

      // Add the Geocoder Control and Options, if requested/enabled, and supported.
      if(params['gmap_geocoder'] === 1) {
        self.map_data[params.mapid].gmap_geocoder = params['gmap_geocoder'];
        self.map_data[params.mapid].gmap_geocoder_settings = params['gmap_geocoder_settings'];
      }
      // Add the Google Places Options, if requested/enabled, and supported.
      else if (typeof google !== 'undefined' && (params.gmap_api_key && params.gmap_api_key.length > 0) && params['gmap_places']) {
        self.map_data[params.mapid].gmap_places = params['gmap_places'];
        // Extend defaults placesAutocompleteServiceOptions.
        self.map_data[params.mapid].gmap_places_options = params['gmap_places_options'].length > 0 ? $.extend(
          {}, {fields: ['place_id', 'name', 'types'], strictBounds: 'false'}, JSON.parse(params['gmap_places_options'])
        ) : {fields: ['place_id', 'name', 'types'], strictBounds: 'false'};
      }

      // Generate and Set/Place Marker Position.
      let marker = self.setMarker(params.mapid, position);

      // Define a Drupal.geofield_map marker self property.
      self.map_data[params.mapid].marker = marker;

      // Bind click to find_marker functionality.
      $('#' + self.map_data[params.mapid].click_to_find_marker_id).click(function (e) {
        e.preventDefault();
        self.find_marker(self.map_data[params.mapid].mapid);
      });

      // Bind click to place_marker functionality.
      $('#' + self.map_data[params.mapid].click_to_place_marker_id).click(function (e) {
        e.preventDefault();
        self.place_marker(self.map_data[params.mapid].mapid);
      });

      // Define Lat & Lng input selectors and all related functionalities and Geofield Map Listeners.
      if (params.widget && params.latid && params.lngid) {

        // If it is defined the Geocode address Search field (dependant on the Gmaps API key)
        if (self.map_data[params.mapid].search) {

          if (self.map_data[params.mapid].gmap_geocoder === 1) {
            Drupal.geoFieldMap.map_geocoder_control.autocomplete(params.mapid, self.map_data[params.mapid].gmap_geocoder_settings, self.map_data[params.mapid].search, 'widget', params.map_library);
          }
          // If the Google Places Autocomplete is not requested/enabled.
          else if (!self.map_data[params.mapid].gmap_places) {
            // Apply the Jquery Autocomplete widget, enabled by core/drupal.autocomplete.
            self.map_data[params.mapid].search.autocomplete({
              // This bit uses the geocoder to fetch address values.
              source: function (request, response) {
                self.geocoder.geocode({address: request.term}, function (results, status) {
                  response($.map(results, function (item) {
                    return {
                      // The value property is needed to be passed to the select.
                      value: item.formatted_address,
                      latitude: item.geometry.location.lat(),
                      longitude: item.geometry.location.lng()
                    };
                  }));
                });
              },
              // This bit is executed upon selection of an address.
              select: function (event, ui) {
                // Update the Geocode address Search field value with the value (or label)
                // property that is passed as the selected autocomplete text.
                self.map_data[params.mapid].search.val(ui.item.value);
                // Triggers the Geocode on the Geofield Map Widget.
                let position = self.getLatLng(params.mapid, ui.item.latitude, ui.item.longitude);
                self.trigger_geocode(params.mapid, position);
              }
            });

          }
          // If the Google Places Autocomplete is requested/enabled.
          else {
            // Apply the Google Places Service to the Geocoder Search Field Selector.
            self.map_data[params.mapid].autocompletePlacesService = new google.maps.places.Autocomplete(
              self.map_data[params.mapid].search.get(0), self.map_data[params.mapid].gmap_places_options
            );
            // Set the bias to the Google Maps Bounds, if the Google Maps exists, as the map library.
            if (self.getMapBounds(params.mapid) === 'object') {
              self.map_data[params.mapid].autocompletePlacesService.bindTo('bounds', self.getMapBounds(params.mapid));
            }
            self.map_data[params.mapid].autocompletePlacesService.addListener('place_changed', function () {
              self.map_data[params.mapid].search.removeClass('ui-autocomplete-loading');
              let place = self.map_data[params.mapid].autocompletePlacesService.getPlace();
              if (!place.place_id) {
                return;
              }
              self.geocoder.geocode({placeId: place.place_id}, function (results, status) {
                if (status === google.maps.GeocoderStatus.OK && results[0]) {
                  // Triggers the Geocode on the Geofield Map Widget.
                  let position = self.getLatLng(params.mapid, results[0].geometry.location.lat(), results[0].geometry.location.lng());
                  // Replace the Google Place name with its formatted address.
                  self.map_data[params.mapid].search.val(results[0].formatted_address);
                  self.trigger_geocode(params.mapid, position);
                }
              });
            });
          }

          // Geocode user input on enter.
          self.map_data[params.mapid].search.keydown(function (e) {
            if (e.which === 13) {
              e.preventDefault();
              let input = self.map_data[params.mapid].search.val();
              // Execute the geocoder.
              self.geocoder.geocode({address: input}, function (results, status) {
                if (status === google.maps.GeocoderStatus.OK && results[0]) {
                  // Triggers the Geocode on the Geofield Map Widget.
                  let position = self.getLatLng(params.mapid, results[0].geometry.location.lat(), results[0].geometry.location.lng());
                  self.trigger_geocode(params.mapid, position);
                }
              });
            }
          });
        }

        if (params.map_library === 'gmap') {

          // Add listener to marker for reverse geocoding.
          google.maps.event.addListener(marker, 'dragend', function () {
            self.geofields_update(params.mapid, marker.getPosition());
          });

          // Change marker position with mouse click.
          google.maps.event.addListener(map, 'click', function (event) {
            let position = self.getLatLng(params.mapid, event.latLng.lat(), event.latLng.lng());
            self.setMarkerPosition(params.mapid, position);
            self.geofields_update(params.mapid, position);
          });

        }

        if (params.map_library === 'leaflet') {
          marker.on('dragend', function (e) {
            self.geofields_update(params.mapid, marker.getLatLng());
          });

          map.on('click', function (event) {
            let position = event.latlng;
            self.setMarkerPosition(params.mapid, position);
            self.geofields_update(params.mapid, position);
          });

        }

        // Events on Lat field change.
        $('#' + self.map_data[params.mapid].latid).on('change', function (e) {
          self.geofield_onchange(params.mapid);
        }).keydown(function (e) {
          if (e.which === 13) {
            e.preventDefault();
            self.geofield_onchange(params.mapid);
          }
        });

        // Events on Lon field change.
        $('#' + self.map_data[params.mapid].lngid).on('change', function (e) {
          self.geofield_onchange(params.mapid);
        }).keydown(function (e) {
          if (e.which === 13) {
            e.preventDefault();
            self.geofield_onchange(params.mapid);
          }
        });

        // Set default search field value (just to the first geofield_map).
        if (self.map_data[params.mapid].search && self.map_data[params.mapid].geoaddress_field && !!self.map_data[params.mapid].geoaddress_field.val()) {
          // Copy from the geoaddress_field.val.
          self.map_data[params.mapid].search.val(self.map_data[params.mapid].geoaddress_field.val());
        }
        // If the coordinates are valid, provide a Gmap Reverse Geocode.
        else if (self.map_data[params.mapid].search && (Math.abs(params.lat) > 0 && Math.abs(params.lng) > 0)) {
          // The following will work only if a google geocoder has been defined.
          self.reverse_geocode(params.mapid, position);
        }
      }

      // Trigger a custom event on Geofield Map initialized, with mapid.
      $(context).trigger('geofieldMapInit', params.mapid);
    }
  };

})(jQuery, Drupal, drupalSettings);
