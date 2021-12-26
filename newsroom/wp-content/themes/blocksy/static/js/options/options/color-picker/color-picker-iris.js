import { createElement, Component, createRef } from '@wordpress/element'
import { ColorPicker } from '@wordpress/components'
import _ from '_'
import $ from 'jquery'
import { __ } from 'ct-i18n'

const ColorPickerIris = ({ onChange, value, value: { color } }) => {
	return (
		<div
			className={
				wp.components.GradientPicker
					? 'ct-gutenberg-color-picker-new'
					: 'ct-gutenberg-color-picker'
			}>
			<ColorPicker
				color={color}
				enableAlpha
				onChangeComplete={(result) => {
					if (result.rgb) {
						onChange({
							...value,
							color:
								result.rgb.a === 1
									? result.hex
									: `rgba(${result.rgb.r}, ${result.rgb.g}, ${result.rgb.b}, ${result.rgb.a})`,
						})

						return
					}

					onChange({
						...value,
						color:
							color.getAlpha() === 1 ? hex : color.toRgbString(),
					})
				}}
			/>
		</div>
	)
}

export default ColorPickerIris
