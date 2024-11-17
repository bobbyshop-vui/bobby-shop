/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';
import { Disabled, PanelBody, RangeControl, ToggleControl, SelectControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element'
import ServerSideRender from '@wordpress/server-side-render';

import ProductCategoryControl from '../editor-components/product-category-control';
import ProductsControl from '../editor-components/products-control';
import ProductTagControl from '../editor-components/product-tag-control';
import ReviewTagControl from '../editor-components/review-tag-control';
import ColorPickerWithLabel from '../editor-components/color-picker-with-label';
import json from './block.json';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-block-editor/#useBlockProps
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

const { name } = json;

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {

	useEffect( () => {
		//init after render
		let blockLoaded = false;
		let blockLoadedInterval = setInterval( function() {
			if (jQuery(".cr-reviews-slider").length) {
				blockLoaded = true;
				jQuery(".cr-reviews-slider").each(function () {
					if ( ! jQuery(this).hasClass("slick-initialized") ) jQuery(this).slickk();
				});
				clearInterval( blockLoadedInterval );
			}
		}, 3000 );
		return () => clearInterval( blockLoadedInterval );
	}, [attributes] );

	return (
		<div { ...useBlockProps() }>
			<InspectorControls key="setting">
				<PanelBody title={ __( 'Reviews Slider Settings', 'customer-reviews-woocommerce' ) } initialOpen={ true }>
					<RangeControl
						label={ __( 'Number of Reviews', 'customer-reviews-woocommerce' ) }
						value={ attributes.count }
						min={ 1 }
						max={ 6 }
						onChange={ ( newCount ) =>
							setAttributes( { count: newCount } )
						}
					/>
					<RangeControl
						label={ __( 'Number of Shop Reviews', 'customer-reviews-woocommerce' ) }
						value={ attributes.count_shop_reviews }
						min={ 0 }
						max={ 3 }
						onChange={ ( newCount_shop_reviews ) =>
							setAttributes( { count_shop_reviews: newCount_shop_reviews } )
						}
					/>
					<RangeControl
						label={ __( 'Number of Slides to Show', 'customer-reviews-woocommerce' ) }
						value={ attributes.slides_to_show }
						min={ 1 }
						max={ 6 }
						onChange={ ( newSlides_to_show ) =>
							setAttributes( { slides_to_show: newSlides_to_show } )
						}
					/>
					<RangeControl
						label={ __( 'Maximum Number of Characters to Display (0 = Unlimited)', 'customer-reviews-woocommerce' ) }
						value={ attributes.max_chars }
						min={ 0 }
						max={ 9999 }
						onChange={ ( newMax_chars ) =>
							setAttributes( { max_chars: newMax_chars } )
						}
					/>
					<RangeControl
						label={ __( 'Minimum Number of Characters in a Review (0 = Display All Reviews)', 'customer-reviews-woocommerce' ) }
						value={ attributes.min_chars }
						min={ 0 }
						max={ 9999 }
						onChange={ ( newMin_chars ) =>
							setAttributes( { min_chars: newMin_chars } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Products', 'customer-reviews-woocommerce' ) }
						checked={ attributes.show_products }
						onChange={ () => setAttributes( { show_products: ! attributes.show_products } ) }
					/>
					<ToggleControl
						label={ __( 'Product Links', 'customer-reviews-woocommerce' ) }
						checked={ attributes.product_links }
						onChange={ () => setAttributes( { product_links: ! attributes.product_links } ) }
					/>
					<ToggleControl
						label={ __( 'Shop Reviews', 'customer-reviews-woocommerce' ) }
						checked={ attributes.shop_reviews }
						onChange={ () => setAttributes( { shop_reviews: ! attributes.shop_reviews } ) }
					/>
					<ToggleControl
						label={ __( 'Inactive Products', 'customer-reviews-woocommerce' ) }
						checked={ attributes.inactive_products }
						onChange={ () => setAttributes( { inactive_products: ! attributes.inactive_products } ) }
					/>
					<ToggleControl
						label={ __( 'Autoplay', 'customer-reviews-woocommerce' ) }
						checked={ attributes.autoplay }
						onChange={ () => setAttributes( { autoplay: ! attributes.autoplay } ) }
					/>
					<ToggleControl
						label={ __( 'Show Dots', 'customer-reviews-woocommerce' ) }
						checked={ attributes.show_dots }
						onChange={ () => setAttributes( { show_dots: ! attributes.show_dots } ) }
					/>
					<SelectControl
						label={ __( 'Avatars', 'customer-reviews-woocommerce' ) }
						value={ attributes.avatars }
						options={ [
							{ label: __( 'Initials', 'customer-reviews-woocommerce' ), value: 'initials' },
							{ label: __( 'Standard', 'customer-reviews-woocommerce' ), value: 'standard' },
							{ label: __( 'No avatars', 'customer-reviews-woocommerce' ), value: 'false' }
						] }
						onChange={ ( newAvatars ) =>
							setAttributes( { avatars: newAvatars } )
						}
					/>
					<SelectControl
						label={ __( 'Sort By', 'customer-reviews-woocommerce' ) }
						value={ attributes.sort_by }
						options={ [
							{ label: __( 'Date', 'customer-reviews-woocommerce' ), value: 'date' },
							{ label: __( 'Rating', 'customer-reviews-woocommerce' ), value: 'rating' }
						] }
						onChange={ ( newSort_by ) =>
							setAttributes( { sort_by: newSort_by } )
						}
					/>
					<SelectControl
						label={ __( 'Sort Order', 'customer-reviews-woocommerce' ) }
						value={ attributes.sort }
						options={ [
							{ label: __( 'Ascending', 'customer-reviews-woocommerce' ), value: 'ASC' },
							{ label: __( 'Descending', 'customer-reviews-woocommerce' ), value: 'DESC' },
							{ label: __( 'Random', 'customer-reviews-woocommerce' ), value: 'RAND' }
						] }
						onChange={ ( newSort ) =>
							setAttributes( { sort: newSort } )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Product Categories', 'customer-reviews-woocommerce' ) } initialOpen={ false }>
					<div>
						{ __( 'Select which product categories to show reviews for.', 'customer-reviews-woocommerce' ) }
					</div>
					<ProductCategoryControl
						selected={ attributes.categories }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setAttributes( { categories: ids } );
						} }
						isCompact={ true }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Products', 'customer-reviews-woocommerce' ) } initialOpen={ false }>
					<div>
						{ __( 'Select which products to show reviews for.', 'customer-reviews-woocommerce' ) }
					</div>
					<ProductsControl
						selected={ attributes.products }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setAttributes( { products: ids } );
						} }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Product Tags', 'customer-reviews-woocommerce' ) } initialOpen={ false }>
					<div>
						{ __( 'Select which product tags to show reviews for.', 'customer-reviews-woocommerce' ) }
					</div>
					<ProductTagControl
						selected={ attributes.product_tags }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setAttributes( { product_tags: ids } );
						} }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Tags', 'customer-reviews-woocommerce' ) } initialOpen={ false }>
					<div>
						{ __( 'Select which tags to show reviews for.', 'customer-reviews-woocommerce' ) }
					</div>
					<ReviewTagControl
						selected={ attributes.tag_ids }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setAttributes( { tag_ids: ids } );
						} }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Colors', 'customer-reviews-woocommerce' ) } initialOpen={ false }>
					<ColorPickerWithLabel
						color={ attributes.color_ex_brdr }
						label={ __( 'External Border', 'customer-reviews-woocommerce' ) }
						disableAlpha={ true }
						onChange={ ( color ) => {
							setAttributes( { color_ex_brdr: color.hex } );
						} }
					/>
					<ColorPickerWithLabel
						color={ attributes.color_brdr }
						label={ __( 'Review Card Border', 'customer-reviews-woocommerce' ) }
						disableAlpha={ true }
						onChange={ ( color ) => {
							setAttributes( { color_brdr: color.hex } );
						} }
					/>
					<ColorPickerWithLabel
						color={ attributes.color_ex_bcrd }
						label={ __( 'Background', 'customer-reviews-woocommerce' ) }
						disableAlpha={ true }
						onChange={ ( color ) => {
							setAttributes( { color_ex_bcrd: color.hex } );
						} }
					/>
					<ColorPickerWithLabel
						color={ attributes.color_bcrd }
						label={ __( 'Review Card Background', 'customer-reviews-woocommerce' ) }
						disableAlpha={ true }
						onChange={ ( color ) => {
							setAttributes( { color_bcrd: color.hex } );
						} }
					/>
					<ColorPickerWithLabel
						color={ attributes.color_pr_bcrd }
						label={ __( 'Product Area Background', 'customer-reviews-woocommerce' ) }
						disableAlpha={ true }
						onChange={ ( color ) => {
							setAttributes( { color_pr_bcrd: color.hex } );
						} }
					/>
					<ColorPickerWithLabel
						color={ attributes.color_stars }
						label={ __( 'Stars', 'customer-reviews-woocommerce' ) }
						disableAlpha={ true }
						onChange={ ( color ) => {
							setAttributes( { color_stars: color.hex } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender
					block={ name }
					attributes={ attributes }
				/>
			</Disabled>
		</div>
	);
}
