/**
 * @file le_core/js/node-menu-enhancer.js
 * @author Felix Albroscheit, 2020
 * @package Leipziger Ecken
 * 
 * On node detail pages (= akteur, event or journal-article):
 * * Mark the current region context ("Stadtregion") as .active
 * * Mark the current main-menu-path as .active (doing this via hooks turned out to be overly complex)
 */
(function ($) {
    'use strict';
    Drupal.behaviors.leCoreMenuEnhancer = {
        attach: function (context, settings) {
            // Is it OOP? Nope! Is it FP? Nope! What is it then? Good old procedural jQuery! "OMG, this is so 2005..."
            var $contextRegion = $(context).find('.field--name-field-le-bezirk-region');

            if ($contextRegion.length === 0) {
                // Journal-article
                $contextRegion = $(context).find('.field--name-field-stadtteil .field--item');
            }
            
            if ($contextRegion.length === 1) {
                // Mark the current region context ("Stadtregion") as .active
                var region = $contextRegion.html().toLowerCase();
                var $target = $(context).find('.global-regions-nav li[data-region="'+ region +'"]');
                $target.addClass('active');
            }

            var $nodeIsAkteur = $(context).find('.region-header .breadcrumb li a[href="/akteure"]');
            
            if ($nodeIsAkteur.length === 1) {
                var $target = $(context).find('.menu--main li a[href="/akteure"]');
                $target.parent().addClass('active');
            }

            var $nodeIsEvent = $(context).find('.region-header .breadcrumb li a[href="/events"]');
            
            if ($nodeIsEvent.length === 1) {
                var $target = $(context).find('.menu--main li a[href="/events"]');
                $target.parent().addClass('active');
            }

            var $nodeIsArticle = $(context).find('.region-header .breadcrumb li a[href="/journal"]');
            
            if ($nodeIsArticle.length === 1) {
                var $target = $(context).find('.menu--main li a[href="/journal"]');
                $target.parent().addClass('active');
            }
        }
    }
})(jQuery);
