import resolve from 'rollup-plugin-node-resolve';
import commonjs from 'rollup-plugin-commonjs';
import filesize from 'rollup-plugin-filesize';
import eslint from 'rollup-plugin-eslint';
import pkg from './package.json';

export default [
	// browser-friendly UMD build
	{
		input: 'assets/js/tusuploader.js',
		output: {
			file: pkg.main,
			format: 'iife',
			name: 'TusUploader'
		},
		plugins: [
			resolve({browser: true}),
			eslint(),
            commonjs(),
            filesize()
		]
	}
];
