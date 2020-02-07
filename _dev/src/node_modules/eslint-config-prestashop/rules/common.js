module.exports = {
  extends: 'airbnb-base',
  env: {
    node: true,
    es6: true,
  },
  parserOptions: {
    ecmaVersion: 6,
    sourceType: 'module',
  },
  rules: {
    indent: ['error', 2, {SwitchCase: 1}],
    'function-paren-newline': ['off', 'never'],
    'object-curly-spacing': ['error', 'never'],
  },
};
