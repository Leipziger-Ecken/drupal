(() => {
  const titleInput = document.getElementById('edit-title-0-value');
  const slugInput = document.getElementById('edit-field-slug-0-value');
  let slugManuallyUpdated = !!slugInput.value.trim();

  function handleTitleInput() {
    if (!slugManuallyUpdated) {
      slugInput.value = slugify(titleInput.value);
    }
  }

  function handleSlugInput() {
    if (!slugInput.value.trim()) {
      slugManuallyUpdated = false;
    } else {
      slugInput.value = slugify(slugInput.value);
    }
  }

  titleInput.addEventListener('input', handleTitleInput);
  slugInput.addEventListener('input', handleSlugInput);

  // if slug was not set yet, update it
  if (!slugManuallyUpdated) handleTitleInput();
})();
