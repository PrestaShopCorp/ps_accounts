import js from "@eslint/js";
import tsPlugin from "@typescript-eslint/eslint-plugin";
import tsParser from "@typescript-eslint/parser";
import globals from "globals";

export default [
  { ignores: ["node_modules/**"] },
  js.configs.recommended,
  {
    // front sources: browser context
    files: ["apps/**/*.ts"],
    languageOptions: { parser: tsParser, globals: globals.browser },
    plugins: { "@typescript-eslint": tsPlugin },
    rules: { ...tsPlugin.configs.recommended.rules },
  },
  {
    // vite configs: TypeScript, but Node context (__dirname)
    files: ["*.config.ts"],
    languageOptions: { parser: tsParser, globals: globals.node },
    plugins: { "@typescript-eslint": tsPlugin },
    rules: { ...tsPlugin.configs.recommended.rules },
  },
  {
    // postcss.config.js, tailwind.config.js: CommonJS
    files: ["**/*.js"],
    languageOptions: { sourceType: "commonjs", globals: globals.node },
  },
  { rules: { indent: ["error", 2, { SwitchCase: 1 }] } },
];
