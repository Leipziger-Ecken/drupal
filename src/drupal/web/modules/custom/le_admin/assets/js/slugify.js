function slugify(value) {
  return value
  .trim()
  .toLowerCase()
  .replace(/\s/g, '-') // replace whitespace
  .replace(/(ä)/g, 'ae') // replace umlauts
  .replace(/(ö)/g, 'oe')
  .replace(/(ü)/g, 'ue')
  .replace(/(ß)/g, 'ss')
  .replace(/[^a-z0-9\-]/g, '') // remove anything thas is not alphanumeric
  ;
}
