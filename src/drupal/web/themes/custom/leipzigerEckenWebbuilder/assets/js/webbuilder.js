(() => {
  const app = Stimulus.Application.start();

  app.register('dropdown', class extends Stimulus.Controller {
    static targets = [
      'trigger',
      'triggericon',
      'dropdown',
    ];
    toggle(event) {
      this.dropdownTarget.classList.toggle('opacity-0');
      this.dropdownTarget.classList.toggle('opacity-100');
      this.dropdownTarget.classList.toggle('translate-y-1');
      this.dropdownTarget.classList.toggle('translate-y-0');
      this.triggericonTarget.classList.toggle('rotate-0');
      this.triggericonTarget.classList.toggle('rotate-180');
      setTimeout(() => {
        this.dropdownTarget.classList.toggle('duration-200');
        this.dropdownTarget.classList.toggle('duration-150');
        this.dropdownTarget.classList.toggle('ease-out');
        this.dropdownTarget.classList.toggle('ease-in');
      }, 200);
    }
  });

  app.register('collapsible', class extends Stimulus.Controller {
    static targets = [
      'trigger',
      'triggericon',
      'collapsible',
    ];
    toggle(event) {
      this.collapsibleTarget.classList.toggle('opacity-0');
      this.collapsibleTarget.classList.toggle('opacity-100');
      this.collapsibleTarget.classList.toggle('max-h-0');
      this.collapsibleTarget.classList.toggle('max-h-lg');
      this.triggericonTarget.classList.toggle('rotate-0');
      this.triggericonTarget.classList.toggle('rotate-180');
      setTimeout(() => {
        this.collapsibleTarget.classList.toggle('duration-200');
        this.collapsibleTarget.classList.toggle('duration-150');
        this.collapsibleTarget.classList.toggle('ease-out');
        this.collapsibleTarget.classList.toggle('ease-in');
      }, 200);
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
