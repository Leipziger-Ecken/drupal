(() => {
  function querySelectorParent(el, selector) {
    if (!el.parentElement && !el.parentElement.parentElement) {
      return null;
    }
    const match = Array.from(el.parentElement.parentElement.querySelectorAll(selector))
    .filter((parent) => {
      return parent == el.parentElement;
    })[0] || null;
    if (!match) {
      return querySelectorParent(el.parentElement, selector);
    } else {
      return match;
    }
  }

  function processForms() {
    function handleFormSubmitClick(event) {
      const form = querySelectorParent(event.target, 'form');
      if (!form) {
        return;
      }
      
      let match = false;

      Array.from(form.querySelectorAll('.ui-accordion'))
      .forEach((accordion) => {
        Array.from(accordion.querySelectorAll('.accordion-item'))
        .forEach((item) => {
          // ignore item, if a match was found previously
          if (match) return;

          const content = document.getElementById(item.getAttribute('aria-controls'));
          if (!content) {
            return;
          }
          
          const requiredEmptyItems = Array.from(content.querySelectorAll('input[required],textarea[required],select[required]'))
          .filter((input) => {
            return !input.value;
          });
          
          if (requiredEmptyItems.length) {
            item.click();
            setTimeout(() => {
              window.scrollTo(requiredEmptyItems[0]);
            }, 500);
            match = true;
          }
        });
      });
    }

    Array.from(document.querySelectorAll('.form-submit[type="submit"]')).forEach((el) => {
      el.addEventListener('click', handleFormSubmitClick);
    });
  }

  function processPreviewableItemLists() {
    Array.from(document.querySelectorAll('*[data-role="previewable-item-list"]'))
    .forEach((list) => {
      const preview = list.querySelector('iframe[data-role="preview"]');
      const previewTitle = list.querySelector('[data-role="preview-title"]');
      const previewLink = list.querySelector('[data-role="preview-link"]');
      if (!preview) {
        return;
      }

      const items = Array.from(list.querySelectorAll('*[data-role="item"]'));

      function handleItemClick(event) {
        previewItem(
          event.target.getAttribute('data-role') === 'item' 
          ? event.target 
          : querySelectorParent(event.target, '*[data-role="item"]')
        );
      }

      function previewItem(item) {
        if (item.classList.contains('is-active')) {
          return;
        }
        const title = item.getAttribute('data-preview-title');
        const url = item.getAttribute('data-preview-url');
        previewTitle.innerHTML = title;
        previewLink.setAttribute('href', url);
        preview.setAttribute('src', url);
        
        item.classList.add('is-active');
        items.forEach((_item) => {
          if (_item !== item) {
            _item.classList.remove('is-active');
          }
        });
      }
      
      items.forEach((item) => {
        item.addEventListener('click', handleItemClick);
      });

      previewItem(items[0]);
    });
  }
  processForms();
  processPreviewableItemLists();
})();
