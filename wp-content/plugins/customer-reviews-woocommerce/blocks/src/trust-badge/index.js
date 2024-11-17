import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import json from './block.json';
import Editor from './edit';
import save from './save';

const { name, description } = json;

registerBlockType(
	name, {
		description: __( description, 'customer-reviews-woocommerce' ),
		edit ( props ) {
			return <Editor { ...props } />;
		},
		save, // Object shorthand property - same as writing: save: save,
	}
);
