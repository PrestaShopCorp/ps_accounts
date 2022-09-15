module.exports = {
  important: true,
  corePlugins: {
    preflight: false,
  },
  prefix: "tw-",
  purge: {
    content: ["./src/**/*.vue"],
  },
  theme: {
    extend: {
      colors: {
        brand: {
          dark: "#011638",
        },
      },
    },
  }
};
