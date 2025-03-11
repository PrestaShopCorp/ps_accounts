const { puikTailwindPreset } = require('@prestashopcorp/puik-tailwind-preset')
/** @type {import('tailwindcss').Config} */
module.exports = {
  important: true,
  corePlugins: {
    preflight: false,
  },
  prefix: 'psacc-',
  presets: [puikTailwindPreset],
  content: ["./apps/**/*.vue", "./apps/**/*.css", "../views/templates/admin/**/*.tpl"],
  theme: {
    extend: {
      colors: {
        brand: {
          dark: "#011638",
        },
      },
      backgroundImage: {
        'bicycle': "url('https://assets.prestashop3.com/dst/accounts/assets/login-background-default.png')"
      },
      backgroundColor: {
        'quote': '#f8e08e'
      }
    },
  },
};
