import ctEvents from 'ct-events'

let prevInnerWidth = null

const renderHeader = () => {
	if (!prevInnerWidth || window.innerWidth !== prevInnerWidth) {
		prevInnerWidth = window.innerWidth
		ctEvents.trigger('ct:header:render-frame')
	}

	requestAnimationFrame(renderHeader)
}

export const mountRenderHeaderLoop = () => {
	requestAnimationFrame(renderHeader)
}
