/**
 * @file le_core/js/events-date-filter-picker.js
 * @author Felix Albroscheit, 2021
 * @package Leipziger Ecken
 * 
 * On node-type=le_event-form:
 * * Add custom delete button (doing so via e.g. form_alter turned out to not work properly),
 * * Auto-set end-field value
 */
(function ($) {
    'use strict';
    Drupal.behaviors.leCoreEventsDateFilterPicker = {
        attach: function (context, settings) {
            this.$datePickerContainer = $(context).find('.le-date-picker');
            if (!this.$datePickerContainer || this.$datePickerContainer.length !== 1) {
                return;
            }

            this.$form = this.$datePickerContainer.closest('form');
            this.$selectField = this.$datePickerContainer.find('select');
            this.$el = this.$datePickerContainer.find('.categories-select');

            if (
                (!this.$form || this.$form.length === 0) ||
                (!this.$selectField || this.$selectFieldlength === 0) ||
                (!this.$el || this.$el.length === 0)
            ) {
                return;
            }

            this.bindEventHandlers();
        },

        bindEventHandlers() {
            var that = this;

            this.$selectField.on('mousedown', function(ev) {
                console.log('MOUSe');
                ev.preventDefault();
                that.open();
                return false;
            });

            // Simple Dropdown functionality
            $('body').on('click', function(ev) {
                var target = $(ev.target);
                var targetIsSelf = target.closest('.categories-select[data-id="le-date-picker"]').length !== 0;
                var targetIsSelectField = target.closest('.select-wrapper').length !== 0;

                if (!targetIsSelf && !targetIsSelectField) {
                    that.close();
                }
            });

            // @todo Click on element
            // @todo Focus, Focusout on date-input: keep layer open, make ev.preventDefault()
        },

        open() {
            this.$el.addClass('active');
        },

        close() {
            this.$el.removeClass('active');
        }
    }
})(jQuery);
