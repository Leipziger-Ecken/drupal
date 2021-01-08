/**
 * Attach functionality for modifying map markers.
 */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.leaflet_widget = {
    attach: function (context, settings) {
      $.each(settings.leaflet_widget, function (map_id, widgetSettings) {
        $('#' + map_id, context).each(function () {
          let map = $(this);
          // If the attached context contains any leaflet maps with widgets, make sure we have a
          // Drupal.leaflet_widget object.
          if (map.data('leaflet_widget') === undefined) {
            let lMap = drupalSettings.leaflet[map_id].lMap;
            map.data('leaflet_widget', new Drupal.leaflet_widget(map, lMap, widgetSettings));
          }
          else {
            // If we already had a widget, update map to make sure that WKT and map are synchronized.
            map.data('leaflet_widget').update_map();
            map.data('leaflet_widget').update_input_state();
          }
        });
      });
    }
  };

  Drupal.leaflet_widget = function (map_container, lMap, widgetSettings) {

    // A FeatureGroup is required to store editable layers
    this.drawnItems = new L.LayerGroup();
    this.settings = widgetSettings;
    this.settings.path_style = this.settings.path ? JSON.parse(this.settings.path) : {};

    this.container = $(map_container).parent();
    this.json_selector = this.settings.jsonElement;
    this.layers = [];

    this.map = undefined;
    this.set_leaflet_map(lMap);

    // If map is initialised (or re-initialised) then use the new instance.
    this.container.on('leafletMapInit', $.proxy(function (event, _m, lMap) {
      this.set_leaflet_map(lMap);
    }, this));

    if (this.settings.fullscreenControl) {
      lMap.addControl(new L.Control.Fullscreen());
    }

    // Update map whenever the input field is changed.
    this.container.on('change', this.json_selector, $.proxy(this.update_map, this));

    // Show, hide, mark read-only.
    this.update_input_state();
  };

  /**
   * Set the leaflet map object.
   */
  Drupal.leaflet_widget.prototype.set_leaflet_map = function (map) {
    if (map !== undefined) {
      this.map = map;
      map.addLayer(this.drawnItems);

      if (this.settings.scrollZoomEnabled) {
        map.on('focus', function () {
          map.scrollWheelZoom.enable();
        });
        map.on('blur', function () {
          map.scrollWheelZoom.disable();
        });
      }

      // Adjust toolbar to show defaultMarker or circleMarker.
      this.settings.toolbarSettings.drawMarker = false;
      this.settings.toolbarSettings.drawCircleMarker = false;
      if (this.settings.toolbarSettings.marker === "defaultMarker") {
        this.settings.toolbarSettings.drawMarker = 1;
      } else if (this.settings.toolbarSettings.marker === "circleMarker") {
        this.settings.toolbarSettings.drawCircleMarker = 1;
      }
      map.pm.addControls(this.settings.toolbarSettings);

      map.on('pm:create', function(event){
        let layer = event.layer;
        this.drawnItems.addLayer(layer);
        layer.pm.enable({ allowSelfIntersection: false });
        this.update_text();
        // Listen to changes on the new layer
        this.add_layer_listeners(layer);
      }, this);
      this.update_map();
    }
  };

  /**
   * Update the WKT text input field.disableGlobalEditMode()
   */
  Drupal.leaflet_widget.prototype.update_text = function () {
    if (this.drawnItems.getLayers().length === 0) {
      $(this.json_selector, this.container).val('');
    }
    else {
      let json_string = JSON.stringify(this.drawnItems.toGeoJSON());
      $(this.json_selector, this.container).val(json_string);
    }
    this.container.trigger("change");
  };

  /**
   * Set visibility and readonly attribute of the input element.
   */
  Drupal.leaflet_widget.prototype.update_input_state = function () {
    $('.form-item.form-type-textarea', this.container).toggle(!this.settings.inputHidden);
    $(this.json_selector, this.container).prop('readonly', this.settings.inputReadonly);
  };

  /**
   * Add/Set Listeners to the Drawn Map Layers.
   */
  Drupal.leaflet_widget.prototype.add_layer_listeners = function (layer) {

    // Listen to changes on the layer.
    layer.on('pm:edit', function(event) {
      this.update_text();
    }, this);

    // Listen to changes on the layer.
    layer.on('pm:update', function(event) {
      this.update_text();
    }, this);

    // Listen to drag events on the layer.
    layer.on('pm:dragend', function(event) {
      this.update_text();
    }, this);

    // Listen to cut events on the layer.
    layer.on('pm:cut', function(event) {
      this.drawnItems.removeLayer(event.originalLayer);
      this.drawnItems.addLayer(event.layer);
      this.update_text();
    }, this);

    // Listen to remove events on the layer.
    layer.on('pm:remove', function(event) {
      this.drawnItems.removeLayer(event.layer);
      this.update_text();
    }, this);

  };

  /**
   * Update the leaflet map from text.
   */
  Drupal.leaflet_widget.prototype.update_map = function () {
    let self = this;
    let value = $(this.json_selector, this.container).val();

    // Always clear the layers in drawnItems on map updates.
    this.drawnItems.clearLayers();

    // Nothing to do if we don't have any data.
    if (value.length === 0) {

      // If no layer available, locate the user position.
      if (this.settings.locate) {
        this.map.locate({setView: true, maxZoom: 18});
      }

      return;
    }

    try {
      let layerOpts = {
        style: function (feature) {
          return self.settings.path_style;
        }
      };
      // Use circleMarkers if specified.
      if (self.settings.toolbarSettings.marker === "circleMarker") {
        layerOpts.pointToLayer = function (feature, latlng) {
          return L.circleMarker(latlng);
        };
      }
      // Apply styles to pm drawn items.
      this.map.pm.setGlobalOptions({
        pathOptions: self.settings.path_style
      });
      let obj = L.geoJson(JSON.parse(value), layerOpts);
      // See https://github.com/Leaflet/Leaflet.draw/issues/398
      obj.eachLayer(function(layer) {
        if (typeof layer.getLayers === "function") {
          let subLayers = layer.getLayers();
          for (let i = 0; i < subLayers.length; i++) {
            this.drawnItems.addLayer(subLayers[i]);
            this.add_layer_listeners(subLayers[i]);
          }
        }
        else {
          this.drawnItems.addLayer(layer);
          this.add_layer_listeners(layer);
        }

      }, this);

      // Pan the map to the feature
      if (this.settings.autoCenter) {
        let start_zoom;
        let start_center;
        if (obj.getBounds !== undefined && typeof obj.getBounds === 'function') {
          // For objects that have defined bounds or a way to get them
          let bounds = obj.getBounds();
          this.map.fitBounds(bounds);
          // Update the map start zoom and center, for correct working of Map Reset control.
          start_zoom = this.map.getBoundsZoom(bounds);
          start_center = bounds.getCenter();

          // In case of Map Zoom Forced, use the custom Map Zoom set.
          if (this.settings.map_position.force && this.settings.map_position.zoom) {
            start_zoom = this.settings.map_position.zoom;
            this.map.setZoom(start_zoom );
          }

        } else if (obj.getLatLng !== undefined && typeof obj.getLatLng === 'function') {
          this.map.panTo(obj.getLatLng());
          // Update the map start center, for correct working of Map Reset control.
          start_center = this.map.getCenter();
          start_zoom = this.map.getZoom();
        }

        // In case of map initial position not forced, and zooFiner not null/neutral,
        // adapt the Map Zoom and the Start Zoom accordingly.
        if (!this.settings.map_position.force && this.settings.map_position.hasOwnProperty('zoomFiner') && parseInt(this.settings.map_position['zoomFiner']) !== 0) {
          start_zoom += parseFloat(this.settings.map_position['zoomFiner']);
          this.map.setView(start_center, start_zoom);
        }

        Drupal.Leaflet[this.settings.map_id].start_zoom = start_zoom;
        Drupal.Leaflet[this.settings.map_id].start_center = start_center;

      }
    } catch (error) {
      if (window.console) console.error(error.message);
    }
  };

})(jQuery, Drupal, drupalSettings);
