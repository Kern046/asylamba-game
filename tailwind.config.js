module.exports = {
	content: [
		'templates/**/*.html.twig',
		'assets/**/*.html',
		'assets/**/*.vue',
	],
	theme: {
		extend: {},
	},
	plugins: [
		require('@tailwindcss/forms'),
	],
	safelist: [
		'py-2',
	],
}
