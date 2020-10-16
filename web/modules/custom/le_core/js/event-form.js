(function ($) {
      'use strict';
      Drupal.behaviors.leCoreEventForm = {
        attach: function (context, settings) {
            /**
             * @file le_core/js/event-form.js
             * 
             * On node-type=le_event form:
             * - Add custom delete button (doing so via e.g. form_alter turned out to not work properly),
             * - Auto-set end-field value
             */
            var $dateListItems = $(context).find('.date-recur-modular-alpha-widget').once('le-core-custom-delete');

            $dateListItems.each(function (_, dateListItem) {
                var $dateListItem = $(dateListItem);

                $dateListItem.find('input.form-element--type-date').each(function(__, dateInputItem) {
                    // onFocusOut start-field, auto-set its value in neighbouring end-field
                    var $dateInputItem = $(dateInputItem);
                    var id = $dateInputItem.attr('id');

                    if (id.indexOf('start') >= 0) {
                        var $dateInputItemSibling = $dateInputItem.closest('.dates').find('.date').eq(1).find('input').eq(0);

                        $dateInputItem.on('blur', function() {
                            if ($dateInputItemSibling.val() === '') {
                              $dateInputItemSibling.val($dateInputItem.val());
                            }
                        });
                    }
                });

                $dateListItem.append(
                  '<a href="#" class="action-link action-link--danger action-link--icon-trash" title="Werte der Felder Beginn und Ende zurücksetzen">' +
                    'Termin zurücksetzen' +
                  '</a>'
                );

                var $deleteBtn = $(this).find('.action-link');
                $deleteBtn.on('click', function() {
                    $dateListItem.find('input').val('');
                    // Fading out the whole row would be great... but breaks as soon as
                    // user clicks "Weiteren Eintrag hinzufügen" :(
                    // $dateListItem.closest('tr').fadeOut('fast');
                    return false;
                })
            });
        }
    }
})(jQuery);
