<?php

/**
 * @file
 * API documentation for Geocoder module.
 */

/**
 * Alter the Address String to Geocode.
 *
 * Allow others modules to adjust the address string.
 *
 * @param string $address_string
 *   The address string to geocode.
 * */
function hook_geocode_address_string_alter(string &$address_string) {
  // Make custom alterations to adjust the address string.
}

/**
 * Alter the Coordinates to Reverse Geocode.
 *
 * Allow others modules to adjust the Coordinates to Reverse Geocode.
 *
 * @param string $latitude
 *   The latitude.
 * @param string $longitude
 *   The longitude.
 * */
function hook_reverse_geocode_coordinates_alter(string &$latitude, string &$longitude) {
  // Make custom alterations to the Coordinates to Reverse Geocode.
}
