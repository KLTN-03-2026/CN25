import js from '@eslint/js'
import pluginVue from 'eslint-plugin-vue'
import oxlintPlugin from 'eslint-plugin-oxlint'
import prettier from 'eslint-config-prettier'

export default [
  {
    ignores: [
      'node_modules/**',
      'dist/**',
      '*.min.js',
      'src/assets/**',
    ],
  },
  js.configs.recommended,
  ...pluginVue.configs['flat/essential'],
  {
    files: ['**/*.vue', '**/*.js'],
    plugins: {
      oxlint: oxlintPlugin,
    },
    languageOptions: {
      globals: {
        console: 'readonly',
        window: 'readonly',
        localStorage: 'readonly',
        setTimeout: 'readonly',
        clearTimeout: 'readonly',
        setInterval: 'readonly',
        clearInterval: 'readonly',
        FormData: 'readonly',
        FileReader: 'readonly',
        Audio: 'readonly',
        alert: 'readonly',
        confirm: 'readonly',
        __dirname: 'readonly',
      },
    },
    rules: {
      'vue/multi-word-component-names': 'off',
      'no-unused-vars': ['error', {
        argsIgnorePattern: '^_',
        varsIgnorePattern: '^_',
        caughtErrorsIgnorePattern: '^_',
      }],
    },
  },
  prettier,
]
