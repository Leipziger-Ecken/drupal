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
})();
