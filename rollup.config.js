import resolve from 'rollup-plugin-node-resolve';
import commonjs from 'rollup-plugin-commonjs';
import filesize from 'rollup-plugin-filesize';
import {eslint} from 'rollup-plugin-eslint';
import {terser} from 'rollup-plugin-terser';
import pkg from './package.json';

export default [
	{
		input: 'assets/js/tusuploader.js',
		output: [
			{
				file: pkg.browser,
				format: 'iife',
				name: 'TusUploader'
			},
			{
				file: pkg.main,
				format: 'cjs',
			}
		],
		plugins: [
			resolve({browser: true}),
			eslint(),
			commonjs(),
            filesize(),
			terser(),
		]
	}
];
