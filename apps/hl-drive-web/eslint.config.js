// eslint.config.js
import js from '@eslint/js';
import vue from 'eslint-plugin-vue';
import vueParser from 'vue-eslint-parser';
import tsParser from '@typescript-eslint/parser';
import tseslint from '@typescript-eslint/eslint-plugin';
import globals from 'globals';
import prettier from 'eslint-config-prettier';

export default [
    // Base recommended rules (JavaScript)
    js.configs.recommended,

    // Vue recommended rules (flat config)
    ...vue.configs['flat/recommended'],

    // Project-specific configuration
    {
        files: [
            '**/*.vue',
            '**/*.js',
            '**/*.ts',
            'open-in-editor-server.js',
            //
        ],

        languageOptions: {
            // Use Vue parser to properly handle .vue files
            parser: vueParser,

            parserOptions: {
                // Use TypeScript parser inside <script> blocks
                parser: tsParser,
                ecmaVersion: 'latest',
                sourceType: 'module',
                // project: './tsconfig.json',
                project: './tsconfig.app.json',
                // project: './tsconfig.eslint.json',
                extraFileExtensions: ['.vue'],
            },

            // Define browser globals (window, localStorage, etc.)
            globals: {
                ...globals.node,
                ...globals.browser,
            },
        },

        plugins: {
            '@typescript-eslint': tseslint,
        },

        rules: {
            '@typescript-eslint/no-unnecessary-type-assertion': 'warn',
            '@typescript-eslint/prefer-nullish-coalescing': 'off',
            'vue/require-default-prop': 'off',
            'preserve-caught-error': 'warn',

            'no-useless-escape': 'off',

            /**
             * DISABLE NOISY / CONFLICTING RULES
             */

            'vue/v-slot-style': 'off',
            'vue/attributes-order': 'off',
            'vue/attribute-hyphenation': 'off',

            // Disable undefined variable errors (handled by environment)
            'no-undef': 'off',

            // Disable unused variables rule
            'no-unused-vars': 'off',
            /*
            "no-unused-vars": [
                "warn",
                {
                    argsIgnorePattern: "^_",
                    varsIgnorePattern: "^_",
                },
            ],
            /**/

            // Vue template formatting rules (delegated to Prettier)
            'vue/html-indent': 'off',
            'vue/max-attributes-per-line': 'off',
            'vue/singleline-html-element-content-newline': 'off',

            /**
             * BASIC CODE STYLE RULES
             */

            // Enforce semicolons
            semi: ['error', 'always'],

            // Enforce 4-space indentation
            indent: ['error', 4],
        },

        ignores: [
            '**/no-commit/**',
            'eslint.config.js',
            'vite.config.ts',
            '*-no*commit*',
            '.vite',
            '**/.vite/**',
            '**/node_modules/**',
            '**/no-commit/**',
            'dist',
            '**/dist/**',
            'open-in-editor-server.js',
            'tailwind.config.*',
            '**/open-in-editor-server.js',
            //
        ],
    },

    /**
     * PRETTIER INTEGRATION (must be last)
     * Disables ESLint rules that conflict with Prettier
     */
    prettier,
];
