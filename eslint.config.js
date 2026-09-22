/**
 * Catch the Vue mistakes the build cannot.
 *
 * WHY THIS EXISTS, AND WHY IT IS THIS SMALL
 * -----------------------------------------
 * Two bugs shipped in one week that webpack compiled without complaint and that
 * no test could see, because both produce valid-looking markup that simply does
 * nothing:
 *
 *   1. `:value.sync="localWidget.feedUrl"` — `.sync` was removed in Vue 3. The
 *      field rendered its value but never wrote typing back, so a feed URL
 *      looked accepted and saved empty.
 *   2. A second `components:` key in the same object literal. JavaScript keeps
 *      the last, so every Nc* component went unregistered and Vue rendered
 *      <NCTEXTFIELD> as an unknown element.
 *
 * Both were found by a person looking at the screen. That is the gap this
 * closes.
 *
 * This config is deliberately NOT a style pass. The repository has 40+ Vue
 * components written over two years; turning on `flat/recommended` would report
 * hundreds of formatting opinions and the whole thing would be switched off
 * within a week. It runs `flat/essential` — the rules Vue itself classifies as
 * "prevents errors" — plus the two core rules that catch the duplicate-key
 * class of bug.
 *
 * Adding a rule here should follow the same test: it must catch something that
 * compiles, renders, and is wrong.
 */
const pluginVue = require('eslint-plugin-vue');

module.exports = [
	{
		// Build output, dependencies and vendored PHP. js/ is webpack's own
		// output and would otherwise dominate every run.
		ignores: ['js/**', 'node_modules/**', 'vendor/**', 'dist/**', 'translationfiles/**'],
	},

	...pluginVue.configs['flat/essential'],

	{
		files: ['src/**/*.{js,vue}'],
		languageOptions: {
			ecmaVersion: 2022,
			sourceType: 'module',
			globals: {
				// Nextcloud injects these; they are not imports.
				OC: 'readonly',
				OCA: 'readonly',
				OCP: 'readonly',
				t: 'readonly',
				n: 'readonly',
				window: 'readonly',
				document: 'readonly',
				console: 'readonly',
				setTimeout: 'readonly',
				clearTimeout: 'readonly',
				setInterval: 'readonly',
				clearInterval: 'readonly',
				requestIdleCallback: 'readonly',
				requestAnimationFrame: 'readonly',
				fetch: 'readonly',
				URL: 'readonly',
				URLSearchParams: 'readonly',
				IntersectionObserver: 'readonly',
				ResizeObserver: 'readonly',
				FormData: 'readonly',
				Blob: 'readonly',
				File: 'readonly',
				FileReader: 'readonly',
				Image: 'readonly',
				Event: 'readonly',
				CustomEvent: 'readonly',
				localStorage: 'readonly',
				sessionStorage: 'readonly',
				navigator: 'readonly',
				location: 'readonly',
				performance: 'readonly',
				caches: 'readonly',
				structuredClone: 'readonly',
			},
		},
		rules: {
			// The two bugs above, by name.
			'vue/no-deprecated-v-bind-sync': 'error',
			'no-dupe-keys': 'error',

			// The rest of the Vue 2 syntax that Vue 3 accepts and ignores. None
			// of these appear in the codebase today; they are here so the next
			// one does not ship either.
			'vue/no-deprecated-dollar-listeners-api': 'error',
			'vue/no-deprecated-dollar-scopedslots-api': 'error',
			'vue/no-deprecated-filter': 'error',
			'vue/no-deprecated-events-api': 'error',
			'vue/no-deprecated-destroyed-lifecycle': 'error',
			'vue/no-deprecated-v-on-native-modifier': 'error',
			'vue/no-deprecated-functional-template': 'error',
			'vue/no-deprecated-props-default-this': 'error',

			// Same family: written, ignored, silently wrong.
			'no-dupe-class-members': 'error',
			'no-dupe-else-if': 'error',
			'no-unsafe-negation': 'error',
			'no-unreachable': 'error',

			// A component name is a naming preference, not a defect.
			'vue/multi-word-component-names': 'off',

			// OFF ON PURPOSE — pre-existing, and none of them silent.
			//
			// Measured when this config was added: 10 + 9 + 3 findings across
			// components written over two years. Real, but all of a kind a
			// reader can see: icon components called Text/Image/Video, data
			// keys prefixed with _, an import kept for a commented-out block.
			//
			// Leaving them as errors would mean a gate that fails on day one,
			// and a gate that cannot pass gets switched off — taking the two
			// rules that caught real bugs with it. They are recorded as debt
			// in the VoxCloud design guidelines instead, to be cleared in a
			// pass of their own.
			'vue/no-reserved-component-names': 'off',
			'vue/no-reserved-keys': 'off',
			'vue/no-unused-components': 'off',

			// Left ON: mutating a prop is a real defect, and there is exactly
			// one. Fixing it is part of this change.
			'vue/no-mutating-props': 'error',
		},
	},
];
