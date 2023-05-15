module.exports = {
  extends: ['@vue/typescript/recommended', 'plugin:vue/vue3-recommended', 'prettier'],
  rules: {
    indent: ['error', 2],
    'vue/multi-word-component-names': 'off',
    indent: [
      'error',
      2,
      {
        SwitchCase: 1,
      },
    ],
  },
};