/**
 * Modified for Leipziger Ecken project (https://github.com/Leipziger-Ecken/drupal):
 * 
 * * OnDocumentReady: Load initial occurrences table list via AJAX
 */

/**
 * Javascript for Sierra widget.
 */

(function ($, Drupal, debounce) {
  Drupal.behaviors.sierraWidgetRecurrences = {
    attach: function attach(context, settings) {
      // Lazily load initial occurrences table list via AJAX
      $(context).find('.date-recur-modular-sierra-widget-start-end').once().trigger('blur');

      var $recurrenceOptionDropdowns = $(context).find('.date-recur-modular-sierra-widget .date-recur-modular-sierra-widget-recurrence-option').once('date-recur-modular-sierra-widget-recurrence-option');
      $recurrenceOptionDropdowns.each(function () {
        $(this).change(function() {
          var value = $(this).val();
          if ('custom_open' === value) {
            /* Traverse and find the outer container, then find the recurring
            rules button. */
            var $recurrenceOpen = $(this).closest('.date-recur-modular-sierra-widget').find('.date-recur-modular-sierra-widget-recurrence-open');
            $recurrenceOpen.click();
          }
          if ('custom' !== value) {
            /** Delete custom option if de-selected */
            $(this).find('option[value="custom"]').remove();
          }
        });
      });

      // Click the reload button when the value changes, with debounce.
      var $startDates = $(context).find('.date-recur-modular-sierra-widget .date-recur-modular-sierra-widget-start-date').once('date-recur-modular-sierra-widget-start-date');
      $startDates.each(function () {
        $(this).on('change', debounce(function () {
          $(this).closest('.date-recur-modular-sierra-widget').find('.date-recur-modular-sierra-widget-reload-recurrence-options').click();

          /* Set the end date to start date if start date is greater than end
          date or end date is empty/invalid */
          var $startMatches = $(this).val().match(/^(\d{4})\-(\d{2})\-(\d{2})$/);
          if ($startMatches !== null) {
            var $startDate = new Date($startMatches[1], $startMatches[2] - 1, $startMatches[3]);

            var $endDateElement = $(this).closest('.date-recur-modular-sierra-widget').find('.date-recur-modular-sierra-widget-start-end');
            var $endDate = null;
            var $endMatches = $($endDateElement).val().match(/^(\d{4})\-(\d{2})\-(\d{2})$/);
            if ($endMatches !== null) {
              $endDate = new Date($endMatches[1], $endMatches[2] - 1, $endMatches[3]);
            }
            if ($endDate === null || ($endDate.getTime()) < $startDate.getTime()) {
              $endDateElement.val($(this).val());
            }
          }
        }, 1000));
      });
    }
  };
})(jQuery, Drupal, Drupal.debounce);
