/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from '@wordpress/block-editor';

// import { TextControl } from '@wordpress/components';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
import { SelectControl } from '@wordpress/components';

const CAMPAIGN_OPTIONS = charitable_block_data;

// const CAMPAIGN_OPTIONS = [
// 	{ label: '', value: '' },
// 	{ label: 'Save The Museum', value: '561' },
// 	{ label: 'Medical Bills', value: '560' },
// 	{ label: 'Medical Causes', value: '559' },
// ];

export default function Edit(props) {
	const { attributes, setAttributes } = props;
	const { campaignID } = attributes;
	const blockProps = useBlockProps();

	console.log( 'campaignID' );
	console.log( campaignID );
	console.log( attributes );
	console.log( charitable_block_data.campaigns );
	console.log( CAMPAIGN_OPTIONS.campaigns );

	return (
		<div { ...blockProps }>
			<div
				className="charitable-block charitable-logo">
				<img src="{ charitable_block_data.logo }" />
			</div>
			<SelectControl
				label="Select the Campaign"
				value={ campaignID }
				options= { charitable_block_data.campaigns }
				onChange={ ( newCampaignID ) => setAttributes( { campaignID: newCampaignID } ) }
			/>
		</div>
	);
}

