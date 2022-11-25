// 255 - (255 * 0.9 - 0.5)

function colorVariant(colorName, s = 1, l = 1) {
  if (s === 1 && l === 1) {
    return `hsl(var(--color-${colorName}-h), var(--color-${colorName}-s), var(--color-${colorName}-l))`;
  }

  return `hsl(
    var(--color-${colorName}-h),
    calc(var(--color-${colorName}-s) * ${s}),
    calc(var(--color-${colorName}-l) * ${l})
  )`;
}

const colors = {};

['primary'].forEach((colorName) => {
  colors[colorName] = colorVariant(colorName);
  colors[colorName + '-900'] = colorVariant(colorName, 1, 0.2);
  colors[colorName + '-800'] = colorVariant(colorName, 1, 0.3);
  colors[colorName + '-700'] = colorVariant(colorName, 1, 0.4);
  colors[colorName + '-600'] = colorVariant(colorName, 1, 0.5);
  colors[colorName + '-500'] = colorVariant(colorName, 1, 1);
  colors[colorName + '-400'] = colorVariant(colorName, 1, 1.275);
  colors[colorName + '-300'] = colorVariant(colorName, 0.95, 1.45);
  colors[colorName + '-200'] = colorVariant(colorName, 0.9, 1.65);
  colors[colorName + '-100'] = colorVariant(colorName, 0.85, 1.825);
  colors[colorName + '-50'] = colorVariant(colorName, 0.8, 1.95);
});

const heights = {};
for (let h = 10; h < 100; h += 10) {
  heights[h + '-screen'] = h + 'vh';
}
const widths = {};
for (let w = 10; w < 100; w += 10) {
  widths[w + '-screen'] = w + 'vw';
}

module.exports = {
  mode: 'jit',
  corePlugins: {
    fontFamily: false,
  },
  content: [
    './templates/**/*.twig',
    './assets/js/**/*.js',
    './leipzigerEckenWebbuilder.theme',
    '../../../modules/custom/**/*.php',
    '../../../modules/custom/**/*.module',
  ],
  theme: {
    extend: {
      colors: colors,
      height: heights,
      width: widths,
      maxWidth: {
        prose: '75ch',
      },
      typography: {
        DEFAULT: {
          css: {
            maxWidth: '75ch',
          },
        },
      },
    },
  },
  plugins: [
    require('@tailwindcss/typography'),
    require('@tailwindcss/forms'),
  ],
}
