/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';
import { Disabled, PanelBody, ToggleControl, SelectControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element'
import ServerSideRender from '@wordpress/server-side-render';

import ColorPickerWithLabel from '../editor-components/color-picker-with-label';
import PlaceholderTrustBadge from '../editor-components/placeholder-trust-badge';
import json from './block.json';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-block-editor/#useBlockProps
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

const { name } = json;

const TriggerWhenLoadingFinished = attributes => {
	return () => {
		useEffect( () => {
			// Call action when the loading component unmounts because loading is finished.
			return () => {
				if (typeof crResizeTrustBadges === "function") {
					crResizeTrustBadges();
				}
			};
		} );

		return (
			<PlaceholderTrustBadge/>
		);
	};
};

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {

	return (
		<div { ...useBlockProps() }>
			<InspectorControls key="setting">
				<PanelBody title={ __( 'Trust Badge Settings', 'customer-reviews-woocommerce' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'Badge Size', 'customer-reviews-woocommerce' ) }
						value={ attributes.badge_size }
						options={ [
							{ label: __( 'Small', 'customer-reviews-woocommerce' ), value: 'small' },
							{ label: __( 'Wide', 'customer-reviews-woocommerce' ), value: 'wide' },
							{ label: __( 'Compact', 'customer-reviews-woocommerce' ), value: 'compact' }
						] }
						onChange={ ( size ) =>
							setAttributes( { badge_size: size } )
						}
					/>
					<SelectControl
						label={ __( 'Badge Style', 'customer-reviews-woocommerce' ) }
						value={ attributes.badge_style }
						options={ [
							{ label: __( 'Light', 'customer-reviews-woocommerce' ), value: 'light' },
							{ label: __( 'Dark', 'customer-reviews-woocommerce' ), value: 'dark' }
						] }
						onChange={ ( style ) =>
							setAttributes( { badge_style: style } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Store Rating', 'customer-reviews-woocommerce' ) }
						help={
							attributes.store_rating
								? __( 'Include store rating', 'customer-reviews-woocommerce' )
								: __( 'Exclude store rating', 'customer-reviews-woocommerce' )
						}
						checked={ attributes.store_rating }
						onChange={ () => setAttributes( { store_rating: ! attributes.store_rating } ) }
					/>
					<ToggleControl
						label={ __( 'Show Border', 'customer-reviews-woocommerce' ) }
						help={
							attributes.badge_border
								? __( 'Display badge border', 'customer-reviews-woocommerce' )
								: __( 'No badge border', 'customer-reviews-woocommerce' )
						}
						checked={ attributes.badge_border }
						onChange={ () => setAttributes( { badge_border: ! attributes.badge_border } ) }
					/>
					<ColorPickerWithLabel
						color={ attributes.badge_color }
						label={ __( 'Background Color', 'customer-reviews-woocommerce' ) }
						help={ __( 'Set a custom background color for the badge', 'customer-reviews-woocommerce' ) }
						disableAlpha={ true }
						onChange={ ( color ) => {
							setAttributes( { badge_color: color.hex } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender
					LoadingResponsePlaceholder={ TriggerWhenLoadingFinished( attributes ) }
					block={ name }
					attributes={ attributes }
				/>
			</Disabled>
		</div>
	);
}
