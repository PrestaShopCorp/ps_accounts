const { puikTailwindPreset } = require("@prestashopcorp/puik") 
/** @type {import('tailwindcss').Config} */
module.exports = {
  important: true,
  corePlugins: {
    preflight: false,
  },
  prefix: 'psacc-',
  presets: [puikTailwindPreset],
  content: ["./apps/**/*.vue", "./apps/**/*.css", "../views/templates/override/**/*.tpl"],
  theme: {
    extend: {
      colors: {
        brand: {
          dark: "#011638",
        },
      },
    },
  },
};
