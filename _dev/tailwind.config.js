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
      backgroundImage: {
        'bicycle': "url('/modules/ps_accounts/views/img/login-background-bicycle.png')"
      },
      backgroundColor: {
        'quote': '#f8e08e'
      }
    },
  },
};
