const base = require('@checkout/config/eslint-e2e-test');
module.exports = {
  ...base,
  settings: {
    'import/parsers': {
      '@typescript-eslint/parser': ['.ts']
    },
    'import/resolver': {
      typescript: {
        alwaysTryTypes: true,
        project: ['tsconfig.json', 'dev-tools/test/e2e/tsconfig.json'],
        root: true,
        sourceType: 'module',
        tsconfigRootDir: __dirname
      }
    }
  },
  parserOptions: {
    root: true,
    project: 'tsconfig.json',
    sourceType: 'module',
    tsconfigRootDir: __dirname
  }
};
