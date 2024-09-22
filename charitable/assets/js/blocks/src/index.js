/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

/**
 * Internal dependencies
 */
import Edit from './edit';
import save from './save';
import metadata from './block.json';

// Import the element creator function (React abstraction layer)
const el = wp.element.createElement;
/**
 * Example of a custom SVG path taken from fontastic
*/

const customSvgPath = "M50.0541 25C50.0541 38.7987 38.8528 50 25.0541 50C11.2554 50 0 38.7987 0 25C0 11.2013 11.2013 0 25 0C38.7987 0 50.0541 11.2013 50.0541 25Z";
const customSvgPath2 = "M34.8483 11.9048C32.9544 9.79442 31.0604 8.98273 29.4912 9.52386C29.1665 9.63208 28.8959 9.79442 28.6795 10.0109L24.0799 13.8529L12.1211 23.9178L9.52365 26.0823C8.65785 26.7858 8.33317 28.0304 8.71196 29.0585L9.19897 30.4654L9.68599 31.8723C10.0648 32.9546 11.0929 33.6581 12.2293 33.6581H37.2293C37.554 33.6581 38.2033 33.4957 38.2033 33.4957C39.7726 32.9005 40.6925 31.0607 40.8007 28.2468C40.9089 25.7035 40.3137 22.6191 39.1773 19.5888C38.0951 16.5044 36.5258 13.7987 34.8483 11.9048ZM34.0908 24.2966L32.2509 19.3182C33.6037 18.8312 35.173 19.5347 35.66 20.8875C36.2011 22.2944 35.4436 23.8096 34.0908 24.2966ZM29.2747 10.7143C29.2206 10.7684 29.2206 10.7684 29.1665 10.8226C27.2726 12.3918 27.3267 17.6407 29.3288 23.2143C30.7899 27.1646 32.8462 30.3031 34.8483 31.8182H12.2834C11.9587 31.8182 11.634 31.6018 11.5258 31.2771L11.0388 29.8702L27.8678 23.7555C25.7033 17.9654 26.4068 12.3377 29.2747 10.7143Z";
const customSvgPath3 ="M30.0322 33.4957C29.8699 36.0931 27.7054 37.9329 25.0539 37.987H24.9998C22.3482 37.987 20.1296 36.1472 19.9673 33.4957H21.8612C22.0777 35.0649 23.3764 36.1472 24.9998 36.1472H25.0539C26.6231 36.1472 27.9218 35.0108 28.1383 33.4957";

const iconEl = el('svg', { width: 50, height: 50, viewBox: '0 0 50 50', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' },
  el('path', { d: customSvgPath, fill: '#F99E36' }),
  el('path', { d: customSvgPath2, fill: 'white' }),
  el('path', { d: customSvgPath3, fill: 'white' }),
);


/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType( metadata.name, {
	icon: iconEl,
	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * @see ./save.js
	 */
	save: () => null,
} );
