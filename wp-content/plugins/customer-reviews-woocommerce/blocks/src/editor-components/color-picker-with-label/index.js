import { BaseControl, ColorPicker } from '@wordpress/components';

const ColorPickerWithLabel = ( props ) => {
	return (
		<BaseControl id={ props.instanceId || Math.random() } label={ props.label || '' } >
			<ColorPicker
				color={ props.color }
				label={ props.label }
				disableAlpha={ props.disableAlpha }
				onChangeComplete={ props.onChange }
			/>
		</BaseControl>
	);
}

export default ColorPickerWithLabel;
