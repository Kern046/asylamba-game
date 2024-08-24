const webpack = require('webpack');
const Encore = require('@symfony/webpack-encore');
const path = require("path");

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
	Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
	// directory where compiled assets will be stored
	.setOutputPath('public/build/')
	// public path used by the web server to access the output path
	.setPublicPath('/build/')
	// only needed for CDN's or sub-directory deploy
	//.setManifestKeyPrefix('build/')

	/*
	 * ENTRY CONFIG
	 *
	 * Each entry will result in one JavaScript file (e.g. app.js)
	 * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
	 */
	.addEntry('app', './assets/js/app.js')
	.addEntry('financial_reports', './assets/js/financial/report.js')

	.copyFiles({
		from: './assets/media-src',
		to: './media/[path][name].[ext]',
	})
	.copyFiles({
		from: './assets/css-media',
		to: './css-media/[path][name].[ext]',
	})
	.copyFiles({
		from: './assets/js',
		to: './js/[path][name].[ext]',
	})

	.enableLessLoader()
	.enablePostCssLoader((options) => {
		options.postcssOptions = {
			config: './postcss.config.js'
		}
	})

	// When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
	.splitEntryChunks()

	// will require an extra script tag for runtime.js
	// but, you probably want this, unless you're building a single-page app
	.enableSingleRuntimeChunk()

	/*
	 * FEATURE CONFIG
	 *
	 * Enable & configure other features below. For a full
	 * list of features, see:
	 * https://symfony.com/doc/current/frontend.html#adding-more-features
	 */
	.cleanupOutputBeforeBuild()
	.enableBuildNotifications()
	.enableSourceMaps(!Encore.isProduction())
	// enables hashed filenames (e.g. app.abc123.css)
	.enableVersioning(Encore.isProduction())

	.addPlugin(
		new webpack.DefinePlugin({
			__VUE_OPTIONS_API__: true,
			__VUE_PROD_DEVTOOLS__: false,
		})
	)

	.configureBabel((config) => {
		config.plugins.push('@babel/plugin-transform-class-properties');
	})

	// enables @babel/preset-env polyfills
	.configureBabelPresetEnv((config) => {
		config.useBuiltIns = 'usage';
		config.corejs = 3;
	})

	// uncomment if you use TypeScript
	//.enableTypeScriptLoader()

	.enableVueLoader(() => {}, {
		runtimeCompilerBuild: true,
		useJsx: false,
	})
	.addLoader({
		test: /\.(html|twig)$/,
		loader: 'raw-loader',
	})

	// uncomment to get integrity="..." attributes on your script & link tags
	// requires WebpackEncoreBundle 1.4 or higher
	//.enableIntegrityHashes(Encore.isProduction())

	// uncomment if you're having problems with a jQuery plugin
	//.autoProvidejQuery()

	.configureDevServerOptions(options => {
		options.server = {
			type: 'https',
			options: {
				pfx: path.join(process.env.HOME, '.symfony5/certs/default.p12'),
				allowedHosts: 'game.kalaxia.wip',
			}
		};
	});

for (let i of Array(12).keys()) {
	i++;
	Encore.addStyleEntry(`css/main.faction${i}`, `./assets/less/colors/color${i}.less`);
}

module.exports = Encore.getWebpackConfig();
