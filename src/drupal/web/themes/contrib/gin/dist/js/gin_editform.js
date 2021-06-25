!function($, Drupal, drupalSettings) {
  Drupal.behaviors.ginEditForm = {
    attach: function() {
      var form = document.querySelector('.region-content form');
      var sticky = $('.gin-sticky').clone(true, true);
      var newParent = document.querySelector('.region-sticky__items__inner');

      if (newParent.querySelectorAll('.gin-sticky').length === 0) {
        sticky.appendTo($(newParent));

        // Input Elements
        newParent.querySelectorAll('input[type="submit"]')
          .forEach((el) => {
            el.setAttribute('form', form.id);
            el.setAttribute('id', el.getAttribute('id') + '--gin-edit-form');
          });

        // Make Published Status reactive
        document.querySelectorAll('.field--name-status [name="status[value]"]').forEach((publishedState) => {
          publishedState.addEventListener('click', (event) => {
            var value = event.target.checked;
            // Sync value
            document.querySelectorAll('.field--name-status [name="status[value]"]').forEach((publishedState) => {
              publishedState.checked = value;
            });
          });
        });

        setTimeout(() => {
          sticky.addClass('gin-sticky--visible');
        });
      }
    }
  };
}(jQuery, Drupal, drupalSettings);
