/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { uniqBy, flatten } from 'lodash';

/**
 * Get a promise that resolves to a list of category objects from the Store API.
 *
 * @param {Object} queryArgs Query args to pass in.
 */
export const getCategories = ( queryArgs ) => {
	return apiFetch( {
		path: addQueryArgs( 'wc/store/v1/products/categories', {
			per_page: 0,
			...queryArgs,
		} ),
	} );
};

/**
 * Get product query requests for the Store API.
 *
 * @param {Object}                     request           A query object with the list of selected products and search term.
 * @param {number[]}                   request.selected  Currently selected products.
 * @param {string=}                    request.search    Search string.
 * @param {(Record<string, unknown>)=} request.queryArgs Query args to pass in.
 */
const getProductsRequests = ( {
	selected = [],
	search = '',
	queryArgs = {},
} ) => {
	const defaultArgs = {
		per_page: 0,
		catalog_visibility: 'any',
		search,
		orderby: 'title',
		order: 'asc',
	};
	const requests = [
		addQueryArgs( '/wc/store/v1/products', {
			...defaultArgs,
			...queryArgs,
		} ),
	];
	return requests;
};

/**
 * Get a promise that resolves to a list of products from the Store API.
 *
 * @param {Object}                     request           A query object with the list of selected products and search term.
 * @param {number[]}                   request.selected  Currently selected products.
 * @param {string=}                    request.search    Search string.
 * @param {(Record<string, unknown>)=} request.queryArgs Query args to pass in.
 * @return {Promise<unknown>} Promise resolving to a Product list.
 * @throws Exception if there is an error.
 */
export const getProducts = ( {
	selected = [],
	search = '',
	queryArgs = {},
} ) => {
	const requests = getProductsRequests( { selected, search, queryArgs } );

	return Promise.all( requests.map( ( path ) => apiFetch( { path } ) ) )
		.then( ( data ) => {
			const products = uniqBy( flatten( data ), 'id' );
			const list = products.map( ( product ) => ( {
				...product,
				parent: 0,
			} ) );
			return list;
		} )
		.catch( ( e ) => {
			throw e;
		} );
};

/**
 * Get product tag query requests for the Store API.
 *
 * @param {Object} request          A query object with the list of selected products and search term.
 * @param {Array}  request.selected Currently selected tags.
 * @param {string} request.search   Search string.
 */
const getProductTagsRequests = ( { selected = [], search } ) => {
	const requests = [
		addQueryArgs( `wc/store/v1/products/tags`, {
			per_page: 0,
			orderby: 'name',
			order: 'asc',
			search,
		} ),
	];

	return requests;
};

/**
 * Get a promise that resolves to a list of tags from the Store API.
 *
 * @param {Object} props          A query object with the list of selected products and search term.
 * @param {Array}  props.selected
 * @param {string} props.search
 */
export const getProductTags = ( { selected = [], search } ) => {
	const requests = getProductTagsRequests( { selected, search } );

	return Promise.all( requests.map( ( path ) => apiFetch( { path } ) ) ).then(
		( data ) => {
			return uniqBy( flatten( data ), 'id' );
		}
	);
};

const getReviewTagsRequests = ( { selected = [], search } ) => {
	const requests = [
		addQueryArgs( `wp/v2/cr_tag`, {
			orderby: 'name',
			order: 'asc',
			search,
		} ),
	];

	return requests;
};

export const getReviewTags = ( { selected = [], search } ) => {
	const requests = getReviewTagsRequests( { selected, search } );

	return Promise.all( requests.map( ( path ) => apiFetch( { path } ) ) ).then(
		( data ) => {
			return uniqBy( flatten( data ), 'id' );
		}
	);
};

/**
 * Given a JS error or a fetch response error, parse and format it so it can be displayed to the user.
 *
 * @param {Object}   error           Error object.
 * @param {Function} [error.json]    If a json method is specified, it will try parsing the error first.
 * @param {string}   [error.message] If a message is specified, it will be shown to the user.
 * @param {string}   [error.type]    The context in which the error was triggered.
 * @return {Promise<{message:string;type:string;}>}   Error object containing a message and type.
 */
export const formatError = async ( error ) => {
	if ( typeof error.json === 'function' ) {
		try {
			const parsedError = await error.json();
			return {
				message: parsedError.message,
				type: parsedError.type || 'api',
			};
		} catch ( e ) {
			return {
				message: e.message,
				type: 'general',
			};
		}
	}

	return {
		message: error.message,
		type: error.type || 'general',
	};
};
