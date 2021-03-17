(() => {
  const wrapper = document.querySelector('*[data-role="webbuilder-pages"]');

  if (!wrapper) {
    return;
  }

  const webbuilderId = wrapper.getAttribute('data-webbuilder');
  const destination = wrapper.getAttribute('data-destination');

  if (!webbuilderId) {
    return;
  }

  const pageTreeWrapper = wrapper.querySelector('*[data-role="webbuilder-page-tree"]');

  if (!pageTreeWrapper) {
    return;
  }

  function apiRequest(method, path, query = null, body = null) {
    return fetch(`/api/${path}${query ? '?' + query : ''}`, {
      headers: {
        'Content-type': 'application/json',
        'Method': method
      },
      credentials: 'include',
      body: body ? JSON.stringify(body) : null
    })
    .then((res) => {
      if (res.ok) {
        return res.json();
      } else {
        return res.text()
        .then((msg) => {
          throw new Error(msg);
        });
      }
    });
  }

  function loadPageTree(webbuilderId) {
    return apiRequest('GET', `webbuilder/${webbuilderId}/page-tree`, `destination=${destination}`);
  }

  function renderPages(pages) {
    return pages.map(renderPage).join('\n');
  }

  function renderPage(page) {
    return `<li data-role="webbuilder-page" data-page="${page.nid}">
      <div data-preview-url="${page.preview_url}">
        <span>${page.title}</span> <a href="${page.edit_url}">Edit</a>
      </div>
      <ul data-role="webbuilder-page-children">
      ${renderPages(page.children)}
      </ul>
    </li>`;
  }

  loadPageTree(webbuilderId)
  .then((pageTree) => {
    pageTreeWrapper.innerHTML = renderPages(pageTree);
  }, (err) => {
    pageTreeWrapper.innerHTML =  err;
  })
})();