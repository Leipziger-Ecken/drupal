(function($, Drupal, drupalSettings) {

  Drupal.geoFieldMap.query_url_serialize = function(obj, prefix) {
    let str = [], p;
    for (p in obj) {
      if (obj.hasOwnProperty(p)) {
        let k = prefix ? prefix + "[" + p + "]" : p,
          v = obj[p];
        str.push((v !== null && typeof v === "object") ?
          Drupal.geoFieldMap.query_url_serialize(v, k) :
          encodeURIComponent(k) + "=" + encodeURIComponent(v));
      }
    }
    return str.join("&");
  };

  Drupal.geoFieldMap.geocoder_geocode = function(address, providers, options) {
    let base_url = drupalSettings.path.baseUrl;
    let geocode_path = base_url + 'geocoder/api/geocode';
    options = Drupal.geoFieldMap.query_url_serialize(options);
    return $.ajax({
      url: geocode_path + '?address=' +  encodeURIComponent(address) + '&geocoder=' + providers + '&' + options,
      type:"GET",
      contentType:"application/json; charset=utf-8",
      dataType: "json",
    });
  };

  Drupal.geoFieldMap.geocoder_reverse_geocode = function(latlng, providers, options) {
    let base_url = drupalSettings.path.baseUrl;
    let reverse_geocode_path = base_url + 'geocoder/api/reverse_geocode';
    options = Drupal.geoFieldMap.query_url_serialize(options);
    return $.ajax({
      url: reverse_geocode_path + '?latlng=' +  latlng + '&geocoder=' + providers + '&' + options,
      type:"GET",
      contentType:"application/json; charset=utf-8",
      dataType: "json",
    });
  };

  Drupal.geoFieldMap.map_geocoder_control = function(controlDiv, mapid) {
    let geocoder_settings = drupalSettings.geofield_google_map[mapid].map_settings.map_geocoder.settings;
    let controlUI = document.createElement('div');
    controlUI.id = mapid + '--geofield-map--geocoder-control--container';
    controlDiv.appendChild(controlUI);

    // Set CSS for the control search interior.
    let controlSearch = document.createElement('input');
    controlSearch.placeholder = Drupal.t('Search Address');
    controlSearch.id = mapid + '--geofield-map--geocoder-control';
    controlSearch.title = Drupal.t('Search an Address on the Map');
    controlSearch.style.color = 'rgb(25,25,25)';
    controlSearch.style.margin = '10px';
    controlSearch.style.padding = '0 17px';
    controlSearch.style.fontSize = '18px';
    controlSearch.style.display = 'table-cell';
    controlSearch.style.height = '40px';
    controlSearch.size = geocoder_settings.input_size || 25;
    controlSearch.maxlength = 256;
    controlUI.appendChild(controlSearch);
    return controlSearch;
  };

  Drupal.geoFieldMap.map_geocoder_control.autocomplete = function(mapid, geocoder_settings, selector, type, map_library) {
    let providers = geocoder_settings.providers.toString();
    let options = geocoder_settings.options;
    selector.autocomplete({
      autoFocus: true,
      minLength: geocoder_settings.min_terms || 4,
      delay: geocoder_settings.delay || 800,
      // This bit uses the geocoder to fetch address values.
      source: function (request, response) {
        // Execute the geocoder.
        $.when(Drupal.geoFieldMap.geocoder_geocode(request.term, providers, options).then(
          // On Resolve/Success.
          function (results) {
            response($.map(results, function (item) {
              return {
                // the value property is needed to be passed to the select.
                value: item.formatted_address,
                lat: item.geometry.location.lat,
                lng: item.geometry.location.lng
              };
            }));
          },
          // On Reject/Error.
          function() {
            response(function(){
              return false;
            });
          }));
      },
      // This bit is executed upon selection of an address.
      select: function (event, ui) {
        let self = Drupal.geoFieldMap;
        let map = self.map_data[mapid].map;
        let zoom = geocoder_settings.zoom || 14;
        let position;
        switch (type) {
          case 'widget':
            position = self.getLatLng(mapid, ui.item.lat, ui.item.lng);
            self.trigger_geocode(mapid, position);
            break;

          case 'formatter':
            switch (map_library) {
              case 'gmap':
                position = new google.maps.LatLng(ui.item.lat, ui.item.lng);
                map.setOptions({
                  center: position,
                  zoom: parseInt(zoom)}
                );
                if (geocoder_settings.infowindow) {
                  map.infowindow.setContent('<div class="geofield-map-geocoder-popup">' + ui.item.value + '</div>');
                  map.infowindow.setPosition(position);
                  setTimeout(function () {
                    map.infowindow.open(map);
                  }, 200);;
                }
                break;

              case 'leaflet':
                break;
            }
            break;
        }
      }
    });
  }

})(jQuery, Drupal, drupalSettings);
