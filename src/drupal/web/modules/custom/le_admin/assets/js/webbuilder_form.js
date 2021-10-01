(() => {
  function enhanceFontsDropdown() {
    const fontsOptions = Array.from(document.querySelectorAll('input[name="field_fonts"]'));
    
    fontsOptions.forEach((option) => {
      const value = option.getAttribute('value');
      const label = document.querySelector(`label[for="${option.getAttribute('id')}"]`);
      const fonts = value.split('+');
      label.innerHTML = [
        `<div class="webbuilder-heading-font-preview" style="font-family: '${fonts[0]}';">${fonts[0]}</div>`,
        `<div class="webbuilder-body-font-preview" style="font-family: '${fonts[1]}';">${fonts[1]}</div>`,
      ].join('\n');
    });
  }

  enhanceFontsDropdown();
})();
