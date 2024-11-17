/**
 * External dependencies
 */
import { useEffect, useState, useCallback, useRef } from '@wordpress/element';
import { getProducts, formatError } from '../utils';
import { useDebouncedCallback } from 'use-debounce';

/**
 * A higher order component that enhances the provided component with products from a search query.
 */
const withSearchedProducts = ( OriginalComponent ) => {
	return ( { selected, ...props } ) => {
		const [ isLoading, setIsLoading ] = useState( true );
		const [ error, setError ] = useState( null );
		const [ productsList, setProductsList ] = useState( [] );

		const setErrorState = async ( e ) => {
			const formattedError = await formatError( e );
			setError( formattedError );
			setIsLoading( false );
		};

		const selectedRef = useRef( selected );

		useEffect( () => {
			getProducts( { selected: selectedRef.current } )
				.then( ( results ) => {
					setProductsList( results );
					setIsLoading( false );
				} )
				.catch( setErrorState );
		}, [ selectedRef ] );

		const debouncedSearch = useDebouncedCallback( ( search ) => {
			getProducts( { selected, search } )
				.then( ( results ) => {
					setProductsList( results );
					setIsLoading( false );
				} )
				.catch( setErrorState );
		}, 400 );

		const onSearch = useCallback(
			( search ) => {
				setIsLoading( true );
				debouncedSearch( search );
			},
			[ setIsLoading, debouncedSearch ]
		);

		return (
			<OriginalComponent
				{ ...props }
				selected={ selected }
				error={ error }
				products={ productsList }
				isLoading={ isLoading }
				onSearch={ onSearch }
			/>
		);
	};
};

export default withSearchedProducts;
