/**
 * @file le_core/js/events-date-filter-picker.js
 * @author Felix Albroscheit, 2021
 * @package Leipziger Ecken
 * 
 * Turns backend-rendered custom "date"-filter on events-list
 * into single-select dropdown allowing custom start- & end-dates
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
            this.$customOptionSelect = this.$selectField.find('option[value="custom"]');
            this.$startInput = this.$el.find('#edit-start');
            this.$endInput = this.$el.find('#edit-ende');

            if (
                (!this.$form || this.$form.length === 0) ||
                (!this.$selectField || this.$selectFieldlength === 0) ||
                (!this.$el || this.$el.length === 0) ||
                (!this.$customOptionSelect || this.$customOptionSelect.length === 0) ||
                (!this.$startInput || this.$startInput.length === 0) ||
                (!this.$endInput || this.$endInput.length === 0)
            ) {
                return;
            }

            this.bindEventHandlers();
        },

        bindEventHandlers() {
            var that = this;

            this.$selectField.on('mousedown', function(ev) {
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

            this.$el.find('.categories-select__options__option a').bind('click', this.onOptionClick.bind(this));
            this.$startInput.bind('input', this.onDateInputFocusout.bind(this));
            this.$endInput.bind('input', this.onDateInputFocusout.bind(this));
        },

        open() {
            this.$el.addClass('active');
        },

        close() {
            this.$el.removeClass('active');
        },

        onDateInputFocusout(ev) {
            // Validate date (for now let's trust the browser)
            // var val = $(ev.currentTarget).val();
            // var date = new Date(val);
            // if (Object.prototype.toString.call(date) !== '[object Date]') {
            //     return;
            // }

            // @todo Only pass through if valid data
            this.setSelected('custom');
            this.setCustomSelectOptionDate();
            return true;
        },

        setCustomSelectOptionDate() {
            var start = this.$startInput.val();
            var end = this.$endInput.val();
            var html = 
                (start !== null && start !== '' ? new Date(start).toLocaleDateString() + ' ' : '') +
                (end !== null && end !== '' ? '- ' + new Date(end).toLocaleDateString() : '');

            this.$customOptionSelect.html(
                html === '' ? 'Zeitraum eingeben' : html
            );
        },

        onOptionClick(ev) {
            var $target = $(ev.currentTarget);
            var $li = $target.parent();
            var dataVal = $li.attr('data-val') || 'custom';
            
            this.setSelected(dataVal);

            if ($li.closest('.has-child').length >= 1) {
                // Child/Input of "custom" date tab: Skip any action
                this.setCustomSelectOptionDate();
            } else {
                // Close dropdown, unset any further stored data
                this.$startInput.val('');
                this.$endInput.val('');
                this.close();
                ev.preventDefault();
                return false;
            }
        },

        setSelected(val) {
            this.$el.find('.categories-select__options__option.selected').removeClass('selected');
            this.$el.find('.categories-select__options__option[data-val="'+ val +'"]').addClass('selected');
            this.$selectField.find('option[value="'+ val +'"]').prop('selected', 'selected');
        }
    };
})(jQuery);
