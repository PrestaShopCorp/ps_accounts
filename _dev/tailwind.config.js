module.exports = {
  important: true,
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
  },
};
