module.exports = {
  root: true,
  env: {
    node: true,
  },
  parserOptions: {
    ecmaVersion: 6,
    ecmaFeatures: {
      experimentalObjectRestSpread: true,
      jsx: true,
    },
    sourceType: "module",
    parser: "babel-eslint",
  },
  extends: [
    "prestashop",
    "plugin:vue/strongly-recommended",
    "eslint:recommended",
    "plugin:prettier/recommended",
    "prettier/vue",
  ],
  plugins: ["import", "vue", "prettier"],
  rules: {
    "prettier/prettier": ["warn"],
    "vue/component-name-in-template-casing": ["error", "PascalCase"],
    "no-console": process.env.NODE_ENV === "production" ? "error" : "off",
    "no-debugger": process.env.NODE_ENV === "production" ? "error" : "off",
    "template-curly-spacing": "off",
    indent: "off",
    "no-param-reassign": ["error", { props: false }],
    "prefer-destructuring": ["error", { object: true, array: false }],
    "import/no-extraneous-dependencies": [
      "error",
      {
        devDependencies: [".storybook/**", "./src/stories/**"],
      },
    ],
  },
  overrides: [
    {
      files: ["*.vue"],
      rules: {
        indent: 0,
      },
    },
  ],
};
