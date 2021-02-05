if (typeof 'debounce' !== 'function') {
  // Returns a function, that, as long as it continues to be invoked, will not
  // be triggered. The function will be called after it stops being called for
  // N milliseconds. If `immediate` is passed, trigger the function on the
  // leading edge, instead of the trailing.
  function debounce(func, wait, immediate) {
  	var timeout;
  	return function() {
  		var context = this, args = arguments;
  		var later = function() {
  			timeout = null;
  			if (!immediate) func.apply(context, args);
  		};
  		var callNow = immediate && !timeout;
  		clearTimeout(timeout);
  		timeout = setTimeout(later, wait);
  		if (callNow) func.apply(context, args);
  	};
  };
}

let unsplashClient;

const handleUnsplashSearchInput = debounce((input) => {
  const query = (input.value || '').trim();
  const page = parseInt(input.getAttribute('page') || 1, 10);
  const resultsTarget = document.querySelector(input.getAttribute('data-results-target'));
  const attributionTarget = document.querySelector(input.getAttribute('data-attribution-target'));
  const previewTarget = document.querySelector(input.getAttribute('data-preview-target'));
  const urlTarget = document.querySelector(input.getAttribute('data-url-target'));
  const altTarget = document.querySelector(input.getAttribute('data-alt-target'));
  const titleTarget = document.querySelector(input.getAttribute('data-title-target'));

  // only allow minimum length
  if (query.length < 3) {
    resultsTarget.innerHTML = '';
    return;
  }

  // create client, if not yet done so
  if (!unsplashClient) {
    const apiUrl = input.getAttribute('data-api-url');
    unsplashClient = new Unsplash.default({
      apiUrl: apiUrl
    });
  }

  resultsTarget.innerHTML = '<p class="unsplash-results__info">Suche Bilder ...</p>';

  unsplashClient.search.photos(query, page)
  .then(res => {
    if (res.ok) {
      return res.json();
    } else {
      throw new Error('Invalid Response');
    }
  })
  .then(data => {
    if (!data.results.length) {
      resultsTarget.innerHTML = '<p class="unsplash-results__info">Keine Bilder gefunden. Bitte versuchen Sie einen anderen Suchbegriff.</p>';
      return;
    }
    const html = data.results.reduce((html, result, index) => {
      return html + `
        <figure class="unsplash-results__item" data-role="wrapper" data-index="${index}">
          <a class="unsplash-results__item-image" target="_blank" href="${result.links.download}">
            <img src="${result.urls.small}" alt="${result.alt_description || result.description || ''}">
          </a>
          <figcaption class="unsplash-results__item-caption">
            <p>${result.description || result.alt_description || ''}</p>
            <div class="unsplash-results__item-author">
              <div>by</div>
              <a target="_blank" href="${result.user.links.html}" class="unsplash-results__item-author-image">
                <img src="${result.user.profile_image.medium}" alt="${result.user.name}"><br/>
              </a>
              <a target="_blank" href="${result.user.links.html}" class="unsplash-results__item-author-name">
                ${result.user.name}
              </a>
            </div>
            <button data-role="add" class="unsplash-results__item-add button button--default">Bild verwenden</button>
          </figcaption>
        </figure>
      `;
    }, '');
    resultsTarget.innerHTML = html;
    Array.from(resultsTarget.querySelectorAll('*[data-role="wrapper"]')).forEach((el, index) => {
      const result = data.results[index];
      el.querySelector('*[data-role="add"]').addEventListener('click', (event) => {
        event.preventDefault();
        urlTarget.value = result.urls.raw + '.jpg';
        altTarget.value = result.alt_description || result.description || '';
        titleTarget.value = result.description || result.alt_description || '';
        attributionTarget.value = `
        <p>${result.description || result.alt_description || ''}</p>
        <p>
          by <a target="_blank" href="${result.user.links.html}">
            ${result.user.name} (${result.user.links.html})
          </a>
        </p>
        `;

        // trigger a download, as required by the unsplash API usage terms
        unsplashClient.photos.trackDownload(result);

        // clear search input and results
        input.value = '';
        resultsTarget.innerHTML = '';
        previewTarget.innerHTML = `
          <img src="${result.urls.small}" alt="${result.alt_description || result.description || ''}">
        `;
        return false;
      });
    });
  })
  .catch(err => {
    console.warn(err);
  });
}, 1000);
