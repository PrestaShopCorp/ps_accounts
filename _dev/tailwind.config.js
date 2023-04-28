const { puikTailwindPreset } = require("@prestashopcorp/puik")
/** @type {import('tailwindcss').Config} */
module.exports = {
  important: true,
  corePlugins: {
    preflight: false,
  },
  prefix: 'psacc-',
  presets: [puikTailwindPreset],
  content: ["./src/**/*.vue", "./src/**/*.css"],
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