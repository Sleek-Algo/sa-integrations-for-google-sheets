const path = require( 'path' );
const FileManagerPlugin = require( 'filemanager-webpack-plugin' );
const create_zip = process.env.CREATE_ZIP || 'no'; // Change this to 'yes' to create the zip
const NODE_ENV = process.env.NODE_ENV || 'development';
const TerserPlugin = require( 'terser-webpack-plugin' );

/**
 * WordPress Dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );

// Base plugins array from default config
const plugins = [ ...defaultConfig.plugins ];

// Conditionally add FileManagerPlugin based on create_zip
if ( create_zip === 'yes' ) {
	const DevelopmentZipVersionFolder =
		'../../../saifgs-backups/saifgs-development-latest-version/sa-integrations-for-google-sheets';
	const DevelopmentZipFileSource =
		'../../../saifgs-backups/saifgs-development-latest-version';
	const ProductionZipVersionFolder =
		'../../../saifgs-backups/saifgs-production-latest-version/sa-integrations-for-google-sheets';
	const ProductionZipFileSource =
		'../../../saifgs-backups/saifgs-production-latest-version';

	plugins.push(
		new FileManagerPlugin( {
			events: {
				onStart: {
					delete: [
						{
							source: DevelopmentZipVersionFolder,
							options: {
								force: true,
							},
						},
						{
							source: DevelopmentZipVersionFolder + '.zip',
							options: {
								force: true,
							},
						},
						{
							source: ProductionZipVersionFolder,
							options: {
								force: true,
							},
						},
						{
							source: ProductionZipVersionFolder + '.zip',
							options: {
								force: true,
							},
						},
					],
				},
				onEnd: {
					mkdir: [
						ProductionZipVersionFolder,
						DevelopmentZipVersionFolder,
					],
					copy: [
						/**
						 * Start - Development Version
						 */
						{
							source: '../assets',
							destination:
								DevelopmentZipVersionFolder + '/assets',
						},
						{
							source: '../includes',
							destination:
								DevelopmentZipVersionFolder + '/includes',
						},
						{
							source: '../languages',
							destination:
								DevelopmentZipVersionFolder + '/languages',
						},
						{
							source: '../libraries',
							destination:
								DevelopmentZipVersionFolder + '/libraries',
						},
						{
							source: './src',
							destination:
								DevelopmentZipVersionFolder + '/node-dev/src',
						},
						{
							source: './package.json',
							destination:
								DevelopmentZipVersionFolder +
								'/node-dev/package.json',
						},
						{
							source: './webpack.config.js',
							destination:
								DevelopmentZipVersionFolder +
								'/node-dev/webpack.config.js',
						},
						{
							source: '../vendor',
							destination:
								DevelopmentZipVersionFolder + '/vendor',
						},
						{
							source: '../composer.json',
							destination:
								DevelopmentZipVersionFolder + '/composer.json',
						},
						{
							source: '../readme.txt',
							destination:
								DevelopmentZipVersionFolder + '/readme.txt',
						},
						{
							source: '../sa-integrations-for-google-sheets.php',
							destination:
								DevelopmentZipVersionFolder +
								'/sa-integrations-for-google-sheets.php',
						},
						/**
						 * End - Development Version
						 */

						/**
						 * Start - Production Version
						 */
						{
							source: '../assets',
							destination: ProductionZipVersionFolder + '/assets',
						},
						{
							source: '../includes',
							destination:
								ProductionZipVersionFolder + '/includes',
						},
						{
							source: '../languages',
							destination:
								ProductionZipVersionFolder + '/languages',
						},
						{
							source: '../libraries',
							destination:
								ProductionZipVersionFolder + '/libraries',
						},
						{
							source: '../vendor',
							destination: ProductionZipVersionFolder + '/vendor',
						},
						{
							source: '../composer.json',
							destination:
								ProductionZipVersionFolder + '/composer.json',
						},
						{
							source: '../readme.txt',
							destination:
								ProductionZipVersionFolder + '/readme.txt',
						},
						{
							source: '../sa-integrations-for-google-sheets.php',
							destination:
								ProductionZipVersionFolder +
								'/sa-integrations-for-google-sheets.php',
						},
						/**
						 * End - Production Version
						 */
					],
					archive: [
						{
							source: DevelopmentZipFileSource,
							destination: DevelopmentZipVersionFolder + '.zip',
							options: {
								globOptions: {
									// https://github.com/Yqnn/node-readdir-glob#options
									dot: true,
								},
							},
						},
						{
							source: ProductionZipFileSource,
							destination: ProductionZipVersionFolder + '.zip',
							options: {
								globOptions: {
									// https://github.com/Yqnn/node-readdir-glob#options
									dot: true,
								},
							},
						},
					],
				},
			},
		} )
	);
}

module.exports = {
	...defaultConfig,
	...{
		mode: NODE_ENV,
		entry: {
			'free-app/index': './src/free-app/index.jsx',
			'free-app/contact-form-7-entries':
				'./src/free-app/contact-form-7-entries.jsx',
		},
		output: {
			path: path.resolve( __dirname, '../assets/backend' ),
			filename: '[name].js',
		},
		plugins: plugins,
	},
};
