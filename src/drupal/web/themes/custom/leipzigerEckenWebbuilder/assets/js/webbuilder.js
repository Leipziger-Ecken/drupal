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
        this.dropdownTarget.getAttribute('data-dropdown-toggle-classes') || 'opacity-0 opacity-100 translate-y-1 translate-y-0'
      ).split(' ');
      console.log(classes);
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
      if (window.scrollY > 30) {
        this.headerTarget.classList.add('shadow');
      } else {
        this.headerTarget.classList.remove('shadow');
      }
    };
    toggleMobileMenu() {
      this.mobileMenuTarget.classList.toggle('opacity-0');
      this.mobileMenuTarget.classList.toggle('scale-95');
      this.mobileMenuTarget.classList.toggle('opacity-100');
      this.mobileMenuTarget.classList.toggle('scale-100');
      setTimeout(() => {
        this.mobileMenuTarget.classList.toggle('duration-200');
        this.mobileMenuTarget.classList.toggle('duration-100');
        this.mobileMenuTarget.classList.toggle('ease-out');
        this.mobileMenuTarget.classList.toggle('ease-in');
      }, 200);
    };
  });
})();
