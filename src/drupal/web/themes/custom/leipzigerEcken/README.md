# LeipzigerEcken

Official Leipziger Ecken v2 theme based on [Bootstrap theme](https://www.drupal.org/project/bootstrap). See *web/themes/contrib/bootstrap/README.md* for more information and supported modules.

### Usage

To compile SCSS-files into CSS [install SASS](https://sass-lang.com/install) (e.g. via *npm install -g sass*). Then run ```sass scss/style.scss css/style.css```.

Or, for categories-select only: ```sass scss/categories-select.scss css/categories-select.css```.

#### Building for production

```sass scss/style.scss css/style.css --style=compressed --no-source-map```

### Icons

Some custom icons are available as webfont (see *scss/_fonts.scss*). Each icon is prefixed by an "i", e.g. *class="i-akteur"*.
