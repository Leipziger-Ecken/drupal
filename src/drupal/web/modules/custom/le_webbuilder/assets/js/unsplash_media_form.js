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

(() => {
  let unsplashClient;

  function createUnsplashClient(apiUrl) {
    return new Unsplash.default({
      apiUrl: apiUrl
    });
  }

  function renderPreview() {
    const previewTarget = document.querySelector('.unsplash-preview');
    const urlSource = document.querySelector(previewTarget.getAttribute('data-url-source'));
    const altSource = document.querySelector(previewTarget.getAttribute('data-alt-source'));
    const url = urlSource.value.replace('.jpg', '&w=400');
    const alt = altSource.value;
    previewTarget.innerHTML = `
      <img src="${url}" alt="${alt}">
    `;
  }

  function renderClear(target) {
    target.innerHTML = '';
  }

  function renderSearching(target) {
    target.innerHTML = '<p class="unsplash-results__info">Suche Bilder ...</p>';
  }

  function renderNoResults(target) {
    target.innerHTML = '<p class="unsplash-results__info">Keine Bilder gefunden. Bitte versuchen Sie einen anderen Suchbegriff.</p>';
  }

  function renderResults(target, data, opts = {}) {
    let html = '<div class="unsplash-results__items">';
    html += data.results.reduce((html, result, index) => {
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
              </a> on Unsplash
            </div>
            <button data-role="add" class="unsplash-results__item-add button button--default">Bild verwenden</button>
          </figcaption>
        </figure>
      `;
    }, '');
    html += '</div>';
    if (data.total_pages > 0) {
      html += '<div class="unsplash-results__pagination">';
      if (opts.page > 1) {
        html += `
        <button data-role="prev-page" class="button button--default unsplash-results__prev-page">
          Zurück
        </button>
        `;
      }
      html += `
      <div class="unsplash-results__current-page">
        ${opts.page} / ${data.total_pages}
      </div>
      `;
      if (opts.page < data.total_pages) {
        html += `
        <button data-role="next-page" class="button button--default unsplash-results__next-page">
          Weitere anzeigen
        </button>
        `;
      }
      html += '</div>';
    }

    target.innerHTML = html;

    // attach listeners
    Array.from(target.querySelectorAll('*[data-role="wrapper"]')).forEach((el, index) => {
      const result = data.results[index];
      el.querySelector('*[data-role="add"]').addEventListener('click', (event) => opts.onResultAdd(event, result));
    });

    if (data.total_pages > 1) {
      if (opts.page > 1) {
        target.querySelector('*[data-role="prev-page"]').addEventListener('click', (event) => opts.onPrevPage(event, opts.page));
      }
      if (opts.page < data.total_pages) {
        target.querySelector('*[data-role="next-page"]').addEventListener('click', (event) => opts.onNextPage(event, opts.page));
      }
    }
  }

  function getAttribution(result) {
    return `
    <p>${result.description || result.alt_description || ''}</p>
    <p>
      by <a target="_blank" href="${result.user.links.html}">
        ${result.user.name} (${result.user.links.html})
      </a> on Unsplash
    </p>
    `;
  }

  function handleUnsplashSearchInput(input, page = 1) {
    const query = (input.value || '').trim();
    const resultsTarget = document.querySelector(input.getAttribute('data-results-target'));
    const attributionTarget = document.querySelector(input.getAttribute('data-attribution-target'));
    const urlTarget = document.querySelector(input.getAttribute('data-url-target'));
    const altTarget = document.querySelector(input.getAttribute('data-alt-target'));
    const titleTarget = document.querySelector(input.getAttribute('data-title-target'));

    // only allow minimum length
    if (query.length < 3) {
      renderClear(resultsTarget);
      return;
    }

    // create client, if not yet done so
    if (!unsplashClient) {
      unsplashClient = createUnsplashClient(
        input.getAttribute('data-api-url')
      );
    }

    if (page === 1) {
      renderSearching(resultsTarget);
    }

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
        renderNoResults(resultsTarget);
        return;
      }

      renderResults(resultsTarget, data, {
        page: page,
        onResultAdd: (event, result) => {
          event.preventDefault();
          urlTarget.value = result.urls.raw + '.jpg';
          altTarget.value = result.alt_description || result.description || '';
          titleTarget.value = result.description || result.alt_description || '';
          const attribution = getAttribution(result);
          attributionTarget.value = attribution;
          CKEDITOR.instances[attributionTarget.getAttribute('id')].setData(attribution);

          // trigger a download, as required by the unsplash API usage terms
          unsplashClient.photos.trackDownload(result);

          // clear search input and results
          input.value = '';
          renderClear(resultsTarget);
          renderPreview();
          return false;
        },
        onNextPage: (event, page) => {
          event.preventDefault();
          handleUnsplashSearchInput(input, page + 1);
          return false;
        },
        onPrevPage: (event, page) => {
          event.preventDefault();
          handleUnsplashSearchInput(input, page - 1);
          return false;
        }
      });
    })
    .catch(err => {
      console.error(err);
    });
  }

  window.handleUnsplashSearchInput = debounce(handleUnsplashSearchInput, 2000);

  renderPreview();
})();
