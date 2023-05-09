(() => {
  const app = Stimulus.Application.start();

  app.register('dropdown', class extends Stimulus.Controller {
    static targets = [
      'trigger',
      'triggericon',
      'dropdown',
    ];
    toggle(event) {
      const classes = (
        this.dropdownTarget.getAttribute('data-dropdown-toggle-class') || 'opacity-0 opacity-100 translate-y-1 translate-y-0'
      ).split(' ');
      classes.forEach((className) => {
        this.dropdownTarget.classList.toggle(className);
      });
      this.triggericonTarget.classList.toggle('rotate-0');
      this.triggericonTarget.classList.toggle('rotate-180');
    }
  });

  app.register('collapsible', class extends Stimulus.Controller {
    static targets = [
      'trigger',
      'triggericon',
      'collapsible',
    ];
    toggle(event) {
      const classes = (
        this.collapsibleTarget.getAttribute('data-collapsible-toggle-classes') || 'opacity-0 opacity-100 max-h-0 max-h-lg'
      ).split(' ');
      classes.forEach((className) => {
        this.collapsibleTarget.classList.toggle(className);
      });
      this.triggericonTarget.classList.toggle('rotate-0');
      this.triggericonTarget.classList.toggle('rotate-180');
    }
  });

  app.register('main-menu', class extends Stimulus.Controller {
    static targets =  [
      'header',
      'mobileMenu',
    ];
    connect() {
      window.addEventListener('scroll', () => this.handleScroll() );
    };
    handleScroll() {
      const toggleClassNames = (this.headerTarget.getAttribute('data-toggle-class') || '').split(' ');

      if (!toggleClassNames.length) {
        return;
      }

      if (window.scrollY > 30) {
        toggleClassNames.forEach((className) => {
          this.headerTarget.classList.add(className);
        });
      } else {
        toggleClassNames.forEach((className) => {
          this.headerTarget.classList.remove(className);
        });
      }
    };
    toggleMobileMenu() {
      (this.mobileMenuTarget.getAttribute('data-toggle-class') || '')
      .split(' ')
      .forEach((className) => {
        this.mobileMenuTarget.classList.toggle(className);
      });

      setTimeout(() => {
        (this.mobileMenuTarget.getAttribute('data-toggle-class-lazy') || '')
        .split(' ')
        .forEach((className) => {
          this.mobileMenuTarget.classList.toggle(className);
        });
      }, 200);
    };
  });

  app.register('slider', class extends Stimulus.Controller {
    static targets = [
      'slider',
    ]
    connect() {
      const prevClassNames = this.sliderTarget.getAttribute('data-prev-class') || '';
      const nextClassNames = this.sliderTarget.getAttribute('data-next-class') || '';
      this.splide = new Splide(this.sliderTarget, {
        classes: {
          prev: 'splide__arrow--prev ' + prevClassNames,
          next: 'splide__arrow--next ' + nextClassNames,
        },
      });
      this.splide.mount();
    }
  });
})();
