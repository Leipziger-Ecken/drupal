/**
 * @file
 * Defines Javascript behaviors for the http_client_manager module.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behaviors for tabs in the service api preview form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior for tabs in the service api preview form.
   */
  Drupal.behaviors.serviceApiPreviewSummaries = {
    attach: function (context) {
      var $context = $(context);
      $context.find("#service-commands-wrapper .vertical-tabs__menu-item > a", context).each(function () {
        var $self = $(this),
          id = $self.attr('href'),
          $command = $(id, context),
          summary = [];

        $command.find('.http-client-manager-service-summary', context).each(function () {
          summary.push($(this).text());
        });

        $self.find('.vertical-tabs__menu-item-summary').html(summary.join('<br>'));
      });
    }
  };

})(jQuery, Drupal);
