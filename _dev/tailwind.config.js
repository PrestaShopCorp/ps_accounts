/** @type {import('tailwindcss').Config} */
module.exports = {
  important: true,
  content: ["./src/**/*.vue", "../views/templates/override/**"],
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
