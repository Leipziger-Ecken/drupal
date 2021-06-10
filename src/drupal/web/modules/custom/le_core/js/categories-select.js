/**
 * @file le_core/js/categories-select.js
 * @author Felix Albroscheit, 2020
 * @package Leipziger Ecken
 * 
 * Provides a good old stateless UI component for turning a native [multiple]-select-field
 * into a nested dropdown container. Full of JQuery powa.
 * 
 * Features:
 * * Browser-agnostic; Clients with <noscript> keep using native [multiple]-select
 * * Would work on single-select-fields without many modifications
 * * Responsive
 * 
 * @todos
 * * Accessiblity (ARIA-labels, keyboard-navigator-events like :focus, :active)
 * * Formal Drupal-integration via field renderer
 * * JQuery.once()
 * * X-Close-Button
 * 
 * To use with Views-module, ensure that filter type is of "Select list" w/ "Show hierarchy in select list" and
 * "Allow multiple choises" are checked
 */
(function ($) {
    'use strict';
    Drupal.behaviors.leCoreCategoriesSelect = {
        enableReset: true,
        initialSelected: [],
        isMultipleSelect: true, // @todo functionalize
        isFormMode: false,
        options: null,
        $el: null,
        $form: null,
        $selectField: null,
        labels: { // @todo Use Drupal-i18n
            'emptySelection': '- Alle -',
            'chooseCategory': 'Nach dieser Kategorie filtern',
            'chosen': 'gewählt',
            'resetLabel': 'Alle',
            'resetLinkTitle': 'Filter zurücksetzen',
        },
        
        /**
         * "Constructor"
         * Bootstrap & render the component
         */
        attach (context, settings) {
            // if ($(context).find('.shortcut-action').length >= 1 || $(context).find('.block-system-main-block').length >= 1) {
            //     // Form displayed in Drupal backend; skip initialization.
            //     return;
            // }

            this.$selectField = $(context).find('#edit-kategorie-id');
            var settingsSelectTarget = settings.le_categories_select_target;

            if (!this.$selectField || this.$selectField.length === 0) {
                if (settingsSelectTarget) {
                    // Fallback on provided input target
                    // Quick & dirty implementation for akteur-/event-form select-field
                    this.$selectField = $(context).find(settingsSelectTarget);

                    this.labels.emptySelection = '- Keine -';
                    this.labels.resetLabel = 'Keine';
                    this.labels.chooseCategory = 'Diese Kategorie auswählen';
                    this.labels.resetLinkTitle = 'Auswahl zurücksetzen';
                } else {
                    return;
                }
            }
            
            this.$form = this.$selectField.closest('form');

            if (!this.$form || this.$form.length === 0) {
                return;
            }

            this.initializeState();
            this.render();
        },

        /**
         * Map select-field-options to local state.
         */
        initializeState() {
            // Set selected options
            this.initialSelected = this.$selectField.val() || [];

            // Set available options (nested)
            var options = [];
            var latestOption = options;

            this.$selectField.find('option').each(function() {
                var id = $(this)[0].value;
                var label = $(this)[0].innerText;
                var isChild = label.indexOf('-') === 0 && id !== 'All';

                if (id === 'All') {
                    // We'll provide an own solution (see this.enableReset)
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

        bindEventHandlers() {
            var that = this;

            // (Un-)select option-items
            this.$el.find('.categories-select__options__option a').click(this.onOptionClick.bind(this));

            // On form submit: Map local state into selected select-fields options,
            // then submit. Do so by temporary faking a multiple-select.
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

            // Simple Dropdown functionality
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
        },

        onOptionClick(ev) {
            var $el = $(ev.currentTarget).parent();
            var categoryId = $el.attr('data-cat-id');
            var level = parseInt($el.closest('.categories-select__options').attr('data-level'));
            var isParent = $el.hasClass('has-child');
            var isChild = level >= 2;

            if (categoryId === 'all') {
                this.unsetSelection();
                this.close();
            } else {
                var toggleMethod = $el.hasClass('selected') ? 'removeClass' : 'addClass';
                $el[toggleMethod]('selected');

                if (isParent) {
                    if (this.isFormMode) {
                        // Ensure that no else nearby option is selected
                        $el.siblings('.selected').removeClass('selected');

                        // Query-selector from hell: Unselect all child-items that are nearby siblings
                        var $activeSiblingChilds = this.$el.find('.categories-select__options[data-level="'+ level +'"] > .categories-select__options__option:not([data-cat-id="'+ categoryId +'"]) .selected');
                        $activeSiblingChilds.removeClass('selected');
                    }

                    // (Un-)select children
                    $el.find('.categories-select__options__option')[toggleMethod]('selected');
                }

                if (isChild) {
                    var $parent = $el.closest('.has-child');
                    var parentCategoryId = $parent.attr('data-cat-id');

                    if (this.isFormMode) {
                        // As only one selected option on first layer is allowed,
                        // temporary unselect any selected parent option
                        var $activeParent = this.$el.find('.categories-select__options[data-level="'+ (level-1) +'"] > .selected');
                        $activeParent.removeClass('selected');

                        // Query-selector from hell: Unselect all child-items that are not nearby siblings
                        var $activeNonSiblingChilds = this.$el.find('.categories-select__options[data-level="'+ (level-1) +'"] > .categories-select__options__option:not([data-cat-id="'+ parentCategoryId +'"]) .selected');
                        $activeNonSiblingChilds.removeClass('selected');
                    }

                    // Lastly: Select parent option
                    $parent.addClass('selected');
                }
            }

            this.setSelectFieldPlaceholder();
            ev.preventDefault();
            return false;
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

        /**
         * Generate and append dropdown-container-content, set init state, set events
         */
        render() {
            if (this.$el) {
                return;
            }

            this.$selectField.wrap('<div class="select-wrapper"></div>');

            this.$selectField.removeAttr('multiple')
                             .removeAttr('size');

            var uniqueId = Date.now().toString().substr(0,5);

            var html = 
                '<div class="categories-select" data-id="'+ uniqueId +'">' +
                    this.optionsListTpl(this.options, 1) +
                '</div>';

            this.$selectField.after(html);
            this.$el = $('.categories-select[data-id="'+ uniqueId +'"]');

            this.bindEventHandlers();
            this.setSelectFieldPlaceholder();
        },

        /**
         * Map selected-options to label state (e.g. "2 selected").
         */
        setSelectFieldPlaceholder() {
            var text = '';
            var target = this.$selectField.find('option')[0];
            var options = this.getSelectedOptions();

            if (options.length === 1) {
                text = options.find('>a')[0].innerText;
            } else if (options.length === 0) {
                text = this.labels.emptySelection;
            } else {
                text = options.length +' '+ this.labels.chosen;
            }

            target.setAttribute('selected','selected'); // First item MUST be selected
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
        }
    }
})(jQuery);
