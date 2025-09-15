// @ts-check

import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';

const ignores = [
  '**/node_modules/**',
  '**/playwright-report/**',
  '**/report-summary/**',
  '**/test-results/**',
  '**/.playwright-artifacts-*/**',
  'package-lock.json',
  'playwright.config.ts',
  'tsconfig.json'
];

export default tseslint.config(
  {
    ...eslint.configs.recommended,
    ignores,
    rules: {
      'no-unused-vars': 'off',
      '@typescript-eslint/no-unused-vars': [
        'warn',
        {
          argsIgnorePattern: '^_',
          varsIgnorePattern: '^_',
          caughtErrorsIgnorePattern: '^_'
        }
      ],
      'no-console': ['warn', {allow: ['warn', 'error']}]
    }
  },
  ...tseslint.configs.recommended.map((config) => ({
    ...config,
    ignores
  }))
);
