module.exports = {
  extends: [
    './rules/common',
    './rules/imports',
    './rules/variables',
    './rules/env',
  ].map(require.resolve),
  parser: 'babel-eslint',
  rules: {
    strict: 'error',
  },
};
