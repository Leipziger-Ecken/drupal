setTimeout(() => {
  const akteurSelect = document.getElementById('edit-field-akteur');
  const titleInput = document.getElementById('edit-title-0-value');
  jQuery(akteurSelect).on('select2:select select2:close', (event) => {
    titleInput.value = akteurSelect.querySelector('option[value="' + akteurSelect.value + '"]').innerHTML;
  });
}, 500);
