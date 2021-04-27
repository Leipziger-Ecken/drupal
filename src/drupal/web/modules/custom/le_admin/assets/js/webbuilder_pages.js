(() => {
  const wrapper = document.querySelector('*[data-role="webbuilder-pages"]');

  if (!wrapper) {
    return;
  }

  const webbuilderId = wrapper.getAttribute('data-webbuilder');
  const destination = wrapper.getAttribute('data-destination');
  const addPageButton = wrapper.querySelector('*[data-role="webbuilder-add-page"]');
  const frontpageSelect = wrapper.querySelector('*[data-role="webbuilder-frontpage"]');
  const blogpageSelect = wrapper.querySelector('*[data-role="webbuilder-blogpage"]');
  const eventspageSelect = wrapper.querySelector('*[data-role="webbuilder-eventspage"]');
  const projectspageSelect = wrapper.querySelector('*[data-role="webbuilder-projectspage"]');

  if (!webbuilderId) {
    return;
  }

  const pageTreeWrapper = wrapper.querySelector('*[data-role="webbuilder-page-tree"]');

  if (!pageTreeWrapper) {
    return;
  }

  if (addPageButton) {
    addPageButton.addEventListener('click', handleAddPageClick);
  }

  if (frontpageSelect) {
    frontpageSelect.addEventListener('change', handleFrontpageChange);
  }
  if (blogpageSelect) {
    blogpageSelect.addEventListener('change', handleBlogpageChange);
  }
  if (eventspageSelect) {
    eventspageSelect.addEventListener('change', handleEventspageChange);
  }
  if (projectspageSelect) {
    projectspageSelect.addEventListener('change', handleProjectspageChange);
  }

  function apiRequest(method, path, query = null, body = null) {
    return fetch(`/api/${path}${query ? '?' + query : ''}`, {
      method: method,
      headers: {
        'Content-type': 'application/json'
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

  function renderPages(pages, parentPage = null, level = 0) {
    return pages
    .map((page, index) => renderPage(page, index, parentPage, level))
    .join('\n');
  }

  function renderPage(page, index, parentPage = null, level = 0) {
    return `
    ${level < 2 && index === 0 ? renderAddPagePlaceholder(null, parentPage) + renderMovePagePlaceholder(null, parentPage) : ''}
    <li data-role="webbuilder-page" data-page="${page.nid}">
      <div 
        data-preview-url="${page.preview_url}" 
        data-preview-title="${page.title}"
        data-role="item" 
        class="webbuilder-page-tree__page mb-4 flex justify-between p-2 rounded-md border border-transparent bg-gray-200 cursor-pointer"
      >
        <div class="mr-auto">
          <span class="text-black font-bold mr-2">${page.title}</span>
          <span class="text-sm ${page.status ? 'text-green-600' : 'text-gray-400'}">${page.status ? Drupal.t('published') : Drupa.t('unpublished')}</span>
        </div>
        <div class="ml-auto">
          <button
            class="link webbuilder-page-tree__move-page"
            data-role="webbuilder-move-page"
          >
            <span class="webbuilder-page-tree__move-page-move-label">${Drupal.t('Move')}</span>
            <span class="webbuilder-page-tree__move-page-cancel-label">${Drupal.t('Cancel move')}</span>
          </button>
          <a class="link webbuilder-page-tree__edit-page" href="${page.edit_url}">
            ${Drupal.t('Edit')}
          </a>
        </div>
      </div>
      <ul data-role="webbuilder-page-children">
        ${level < 1 && page.children.length === 0 ? renderAddPagePlaceholder(null, page) + renderMovePagePlaceholder(null, page) : ''}
        ${renderPages(page.children, page, level + 1)}
      </ul>
    </li>
    ${level < 2 ? renderAddPagePlaceholder(page, parentPage) + renderMovePagePlaceholder(page, parentPage) : ''}`;
  }

  function renderAddPagePlaceholder(siblingPage = null, parentPage = null) {
    return `<li 
      class="webbuilder-page-tree__add-page-placeholder rounded-md border cursor-pointer p-2 mb-4"
      data-role="webbuilder-add-page-placeholder"
      data-parent-page="${parentPage ? parentPage.nid : ''}"
      data-sibling-page="${siblingPage ? siblingPage.nid : ''}"
    >
      ${Drupal.t('Add page here')}
    </li>`;
  }

  function renderMovePagePlaceholder(siblingPage = null, parentPage = null) {
    return `<li 
      class="webbuilder-page-tree__move-page-placeholder rounded-md border cursor-pointer p-2 mb-4"
      data-role="webbuilder-move-page-placeholder"
      data-parent-page="${parentPage ? parentPage.nid : ''}"
      data-sibling-page="${siblingPage ? siblingPage.nid : ''}"
    >
      ${Drupal.t('Move page here')}
    </li>`;
  }

  function renderPageOptions(pages, frontpageId, level = 0) {
    return `<option value="" ${!frontpageId ? 'selected' : ''}> - </option>` + 
    pages
    .map((page, index) => renderPageOption(page, index, frontpageId, level))
    .join('\n');
  }

  function renderPageOption(page, index, frontpageId, level = 0) {
    prefix = '';
    for(let i=0; i<level; i++) {
      prefix+='-';
    }
    return `<option value="${page.nid}" ${frontpageId == page.nid ? 'selected' : ''}>${prefix}${page.title}</option>`;
  }

  function attachListeners() {
    Array.from(pageTreeWrapper.querySelectorAll('*[data-role="webbuilder-add-page-placeholder"]'))
    .forEach((el) => {
      el.addEventListener('click', handleAddPagePlaceholderClick);
    });

     Array.from(pageTreeWrapper.querySelectorAll('*[data-role="webbuilder-move-page-placeholder"]'))
    .forEach((el) => {
      el.addEventListener('click', handleMovePagePlaceholderClick);
    });

    Array.from(pageTreeWrapper.querySelectorAll('*[data-role="webbuilder-move-page"]'))
    .forEach((el) => {
      el.addEventListener('click', handleMovePageClick);
    });
  }

  function removeListeners() {
    Array.from(pageTreeWrapper.querySelectorAll('*[data-role="webbuilder-add-page-placeholder"]'))
    .forEach((el) => {
      el.removeEventListener('click', handleAddPagePlaceholderClick);
    });

    Array.from(pageTreeWrapper.querySelectorAll('*[data-role="webbuilder-move-page-placeholder"]'))
    .forEach((el) => {
      el.removeEventListener('click', handleMovePagePlaceholderClick);
    });

    Array.from(pageTreeWrapper.querySelectorAll('*[data-role="webbuilder-move-page"]'))
    .forEach((el) => {
      el.removeEventListener('click', handleMovePageClick);
    });
  }

  function handleAddPageClick(event) {
    event.preventDefault();
    event.stopPropagation();

    wrapper.classList.toggle('is-adding-page');

    return false;
  }

  function handleMovePageClick(event) {
    event.preventDefault();
    event.stopPropagation();
    
    wrapper.classList.toggle('is-moving-page');

    return false;
  }

  function handleAddPagePlaceholderClick(event) {
    wrapper.classList.toggle('is-adding-page');
    const el = event.target;
    const parentId = el.getAttribute('data-parent-page') || '';
    const siblingId = el.getAttribute('data-sibling-page') || '';
    const url = addPageButton.getAttribute('href') + `&parent_page=${parentId}&sibling_page=${siblingId}`;
    window.location.href = url;
  }

  function handleMovePagePlaceholderClick(event) {
    event.preventDefault();
    event.stopPropagation();

    wrapper.classList.toggle('is-moving-page');
    let activeEl = pageTreeWrapper.querySelector('.is-active[data-role="item"]');
    if (!activeEl) {
      return false;
    }
    activeEl = activeEl.parentElement;
    const el = event.target;
    const parentId = el.getAttribute('data-parent-page') || '';
    const siblingId = el.getAttribute('data-sibling-page') || '';
    const pageId = activeEl.getAttribute('data-page');
    
    apiRequest('POST', `webbuilder/${webbuilderId}/sort-page/${pageId}`, null, {
      parent_id: parentId,
      sibling_id: siblingId
    })
    .then(() => {
      updatePageTree(webbuilderId);
    }, (err) => {
      console.error(err);
    });

    return false;
  }

  function handleFrontpageChange(event) {
    const pageId = event.target.value;
    apiRequest('POST', `webbuilder/${webbuilderId}/frontpage`, null, {
      page_id: pageId
    })
    .catch((err) => {
      console.error(err);
    });
  }

  function handleBlogpageChange(event) {
    const pageId = event.target.value;
    apiRequest('POST', `webbuilder/${webbuilderId}/blogpage`, null, {
      page_id: pageId
    })
    .catch((err) => {
      console.error(err);
    });
  }

  function handleEventspageChange(event) {
    const pageId = event.target.value;
    apiRequest('POST', `webbuilder/${webbuilderId}/eventspage`, null, {
      page_id: pageId
    })
    .catch((err) => {
      console.error(err);
    });
  }

  function handleProjectspageChange(event) {
    const pageId = event.target.value;
    apiRequest('POST', `webbuilder/${webbuilderId}/projectspage`, null, {
      page_id: pageId
    })
    .catch((err) => {
      console.error(err);
    });
  }

  function updatePageTree(webbuilderId) {
    loadPageTree(webbuilderId)
    .then((pageTree) => {
      pageTreeWrapper.innerHTML = renderPages(pageTree);
      pageTreeWrapper.dispatchEvent(new Event('listupdate', {
        bubbles: true,
        cancelable: true
      }));

      if (frontpageSelect) {
        frontpageId = frontpageSelect.getAttribute('data-frontpage');
        frontpageSelect.innerHTML = renderPageOptions(pageTree, frontpageId);
      }

      if (blogpageSelect) {
        blogpageId = blogpageSelect.getAttribute('data-blogpage');
        blogpageSelect.innerHTML = renderPageOptions(pageTree, blogpageId);
      }

      if (eventspageSelect) {
        eventspageId = eventspageSelect.getAttribute('data-eventspage');
        eventspageSelect.innerHTML = renderPageOptions(pageTree, eventspageId);
      }

      if (projectspageSelect) {
        projectspageId = projectspageSelect.getAttribute('data-projectspage');
        projectspageSelect.innerHTML = renderPageOptions(pageTree, projectspageId);
      }

      removeListeners();
      attachListeners();
    }, (err) => {
      pageTreeWrapper.innerHTML =  err;
    })
  }
  
  updatePageTree(webbuilderId);
})();