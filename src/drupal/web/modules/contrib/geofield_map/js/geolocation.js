/**
 * @file
 * Javascript for the Geolocation in Geofield Map.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.geofieldMapGeolocation = {
    attach: function (context, settings) {

      let fields = $(context);

      // Don't do anything if we're on field configuration.
      if (!fields.find('#edit-instance').length) {

        // Check that we have something to fill up
        // On multi values check only that the first one is empty.
        if (fields.find('.auto-geocode .geofield-lat').val() === '' && $fields.find('.auto-geocode .geofield-lon').val() === '') {

          // Check to see if we have geolocation support, either natively or through Google.
          if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(updateLocation, errorUpdateLocation);
          }
        }
      }

      $('input[name="geofield-html5-geocode-button"]').once('geofield_geolocation').click(function (e) {
        e.preventDefault();

        fields = $(this).parents('.auto-geocode').parent();
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(updateLocation, errorUpdateLocation);
        }
      });

      // Success callback for getCurrentPosition.
      function updateLocation(position) {
        fields.find('.auto-geocode .geofield-lat').val(position.coords.latitude.toFixed(6)).trigger('change');
        fields.find('.auto-geocode .geofield-lon').val(position.coords.longitude.toFixed(6)).trigger('change');
      }

      // Error callback for getCurrentPosition.
      function errorUpdateLocation(position) {
        /* eslint-disable no-console */
        console.log('didn\'t find any HTML5 position');
        /* eslint-enable no-console */
      }

    }
  };


})(jQuery, Drupal);

