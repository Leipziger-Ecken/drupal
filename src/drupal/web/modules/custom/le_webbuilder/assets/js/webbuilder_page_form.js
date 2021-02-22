(() => {
  const titleInput = document.getElementById('edit-title-0-value');
  const slugInput = document.getElementById('edit-field-slug-0-value');

  function updateSlug() {
    console.log('change', slugify(titleInput.value));
    slugInput.value = slugify(titleInput.value);
  }

  titleInput.addEventListener('input', updateSlug);

  // if slug was not set yet, update it
  if (titleInput.value.trim() && !slugInput.value.trim()) updateSlug();
})();
