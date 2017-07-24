import resolve from 'rollup-plugin-node-resolve';
import commonjs from 'rollup-plugin-commonjs';
import filesize from 'rollup-plugin-filesize';
import pkg from './package.json';

export default [
	// browser-friendly UMD build
	{
		entry: 'assets/js/tusuploader.js',
		dest: pkg.main,
		format: 'iife',
		moduleName: 'TusUploader',
		plugins: [
			resolve({browser: true}),
            commonjs(),
            filesize()
		]
	}
];
