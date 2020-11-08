/**
 * @file le_core/js/categories-select.js
 * @author Felix Albroscheit, 2020
 * @package Leipziger Ecken
 * 
 * Provides a good old stateless UI component for turning a native [multiple]-select-field
 * into a nested dropdown container. Full of JQuery powa. i18n-support targeted.
 * 
 * Features:
 * * Browser-agnostic; Clients with <noscript> keep using native [multiple]-select
 * * Would work on single-select-fields without many modifications
 * * Responsive
 * 
 * @todo Accessiblity (ARIA-labels, keyboard-navigator-events like :focus, :active), formal Drupal-integration
 * 
 * To use with Views-module, ensure that filter type is of "Select list" w/ "Show hierarchy in select list" and
 * "Allow multiple choises" are checked
 */
(function ($) {
    'use strict';
    Drupal.behaviors.leCoreCategoriesSelect = {
        enableReset: true,
        options: null,
        initialSelected: [],
        $el: null,
        $form: null,
        $selectField: null,
        labels: {
            'emptySelection': '',
            'chooseCategory': 'Nach dieser Kategorie filtern',
            'chosen': 'gewählt',
            'resetLabel': 'Alle',
            'resetLinkTitle': 'Filter zurücksetzen',
        },
        setLocalOptions() {
            var options = [];
            var latestOption = options;

            this.$selectField.find('option').each(function() {
                var id = $(this)[0].value;
                var label = $(this)[0].innerText;
                var isChild = label.indexOf('-') === 0 && id !== 'All';

                if (id === 'All') {
                    // We provide an own solution (see this.enableReset)
                    return;
                }

                if (!isChild) {
                    latestOption = options;
                }

                var key = latestOption.push({
                    id: id,
                    label: label.replace('-',''),
                    children: [],
                });

                if (!isChild) {
                    latestOption = options[--key]['children'];
                }
            });

            if (this.enableReset) {
                options.unshift({
                    id: 'all',
                    label: this.labels.resetLabel,
                    title: this.labels.resetLinkTitle,
                    children: []
                });
            }

            this.options = options;
        },
        optionsListTpl(options, level = 1) {
            var html = '<div class="categories-select__options" data-level="'+ level +'">';

            for (var i = 0; i < options.length; i++) {
                var option = options[i];
                var hasChild = option['children'].length > 0;
                var isSelected = this.initialSelected.includes(option.id);
                var classes = (hasChild ? ' has-child' : '') + (isSelected ? ' selected' : '');
                var title = typeof option.title !== 'undefined' ? option.title : this.labels.chooseCategory;
                var childrenHtml = hasChild ? this.optionsListTpl(option['children'], level + 1) : '';

                html +=
                    '<div class="categories-select__options__option'+ classes +'" data-cat-id="'+ option.id +'">'+
                    '   <a href="#" title="'+ title +'">'+ option.label +'</a>'+
                    '    '+ childrenHtml +''+
                    '</div>';
            }

            html += '</div>';

            return html;
        },
        render() {
            if (this.$el) {
                return;
            }

            var uniqueId = Date.now().toString().substr(0,5);

            var html = 
                '<div class="categories-select" data-id="'+ uniqueId +'">' +
                this.optionsListTpl(this.options, 1) +
                '</div>';

            this.$selectField.after(html);
            this.$el = $('.categories-select[data-id="'+ uniqueId +'"]');

            var that = this;

            this.$el.find('.categories-select__options__option a').click(function() {
                var parent = $(this).parent();
                var id = parent.attr('data-cat-id');

                if (id === 'all') {
                    that.unsetSelection();
                    that.close();
                } else {
                    parent.toggleClass('selected');
                }

                var options = that.getSelectedOptions();

                if (options.length === 1) {
                    that.setSelectFieldLabel(options[0].innerText);
                } else if (options.length === 0) {
                    that.setSelectFieldLabel(that.labels.emptySelection);
                } else {
                    that.setSelectFieldLabel(options.length +' '+ that.labels.chosen);
                }

                return false;
            });

            if (this.initialSelected.length >= 2) {
                this.setSelectFieldLabel(this.initialSelected.length +' '+ this.labels.chosen);
            }
        },
        setSelectFieldLabel(text) {
            var target = this.$selectField.find('option')[0];
            target.setAttribute('selected','selected');
            target.innerText = text;
        },
        getSelectedOptions() {
            return this.$el.find('.categories-select__options__option.selected');
        },
        unsetSelection() {
            this.getSelectedOptions().each(function() {
                $(this).removeClass('selected');
            });
        },
        open() {
            this.$el.addClass('active');
        },
        close() {
            this.$el.removeClass('active');
        },
        attach: function (context, settings) {
            var that = this;

            this.$selectField = $(context).find('#edit-kategorie-id');
            this.$form = this.$selectField.closest('form');

            if (!this.$selectField || !this.$form) {
                return;
            }

            this.initialSelected = this.$selectField.val() || [];
            this.$selectField.wrap('<div class="select-wrapper"></div>');

            this.$selectField.removeAttr('multiple')
                             .removeAttr('size');

            this.setLocalOptions();
            this.render();

            // Event handler
            $('body').on('click', function(ev) {
                var target = $(ev.target);
                var targetIsSelf = target.closest('.categories-select[data-id="'+ that.$el.attr('data-id') +'"]').length !== 0;
                var targetIsSelectField = target.closest('.select-wrapper').length !== 0;

                if (!targetIsSelf && !targetIsSelectField) {
                    that.close();
                }
            });

            this.$selectField.on('mousedown', function(ev) { // || active / focus!
                ev.preventDefault();
                that.open();
                return false;
            });

            this.$form.on('submit', function(ev) {
                var selected = [];
                that.$selectField.attr('multiple','multiple');
                that.getSelectedOptions().each(function() {
                    selected.push($(this).attr('data-cat-id'));
                });
                that.$selectField.val(selected);
                window.requestAnimationFrame(function() {
                    that.$selectField.removeAttr('multiple');
                });

                return true;
            });            
       }
    }
})(jQuery);