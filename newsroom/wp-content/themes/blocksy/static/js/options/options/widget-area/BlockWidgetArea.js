import {
	useContext,
	createElement,
	useEffect,
	useRef,
} from '@wordpress/element'
import $ from 'jquery'

import { __ } from 'ct-i18n'

import { PanelContext } from '../../components/PanelLevel'

const BlockWidgetArea = ({
	value,
	option,
	option: { sidebarId = 'ct-footer-sidebar-1' },
	onChange,
}) => {
	const parentEl = useRef()

	const { panelsState, panelsHelpers, panelsDispatch } = useContext(
		PanelContext
	)

	useEffect(() => {
		const controlForSidebarId =
			wp.customize.control._value[`sidebars_widgets[${sidebarId}]`]

		const config = { attributes: true, childList: true, subtree: true }

		const callback = function (mutationsList, observer) {
			if (
				controlForSidebarId.container[0].closest('.ct-customizer-panel')
			) {
				return
			}

			const currentTab =
				document.querySelector(
					'.ct-customizer-panel .ct-current-tab'
				) ||
				document.querySelector(
					'.ct-customizer-panel .customizer-panel-content'
				)

			if (currentTab) {
				currentTab.prepend(controlForSidebarId.container[0])
			}
		}

		const observer = new MutationObserver(callback)

		const actuallyMountWidgetArea = () => {
			if (!parentEl.current) {
				return
			}

			parentEl.current.classList.remove('ct-loading')
			;[...parentEl.current.querySelectorAll('.ct-loader')].map((el) =>
				el.remove()
			)
			let sidebarForCleanup = 'ct-footer-sidebar-1'

			if (sidebarId === 'ct-footer-sidebar-1') {
				sidebarForCleanup = 'ct-footer-sidebar-2'
			}

			wp.customize.control._value[
				`sidebars_widgets[${sidebarForCleanup}]`
			].subscribers.forEach((c) => {
				c(true)
			})

			requestAnimationFrame(() => {
				controlForSidebarId.subscribers.forEach((c) => {
					c(true)
				})
			})

			controlForSidebarId.oldContainer = controlForSidebarId.container
			controlForSidebarId.container = $(parentEl.current)

			setTimeout(() => {
				controlForSidebarId.inspector.open = () => {
					panelsHelpers.openSecondLevel()
				}

				panelsDispatch({
					type: 'PANEL_RECEIVE_META',
					payload: {
						secondLevelTitleLabel: __('Block Settings', 'blocksy'),
					},
				})
				setTimeout(() => {
					if (
						!controlForSidebarId.inspector.contentContainer[0].querySelector(
							'form'
						)
					) {
						return
					}

					if (!parentEl.current) {
						return
					}

					controlForSidebarId.inspector.oldExpanded =
						controlForSidebarId.inspector.expanded
					controlForSidebarId.inspector.expanded = () => true
					controlForSidebarId.inspector.expanded.get = () => true
					controlForSidebarId.inspector.expanded.set = () => {}

					parentEl.current
						.closest('.ct-customizer-panel')
						.lastElementChild.querySelector(
							'.customizer-panel-content'
						)
						.appendChild(
							controlForSidebarId.inspector.contentContainer[0].querySelector(
								'form'
							)
						)
				})
			}, 10)

			controlForSidebarId.oldContainer.remove()

			wp.customize.section(controlForSidebarId.section()).container = $(
				parentEl.current
			)

			observer.observe(parentEl.current.parentNode, config)
		}

		const hasWidgetAreaMounted = document.querySelector(
			'.ct-customizer-panel .customize-widgets-header'
		)

		setTimeout(() => {
			actuallyMountWidgetArea()
		}, 600)

		return () => {
			if (!parentEl.current) {
				return
			}
			if (!parentEl.current.querySelector('.customize-widgets-header')) {
				return
			}

			if (
				!parentEl.current
					.closest('.ct-customizer-panel')
					.lastElementChild.querySelector(
						'.customizer-panel-content form'
					)
			) {
				return
			}

			controlForSidebarId.container = controlForSidebarId.oldContainer
			observer.disconnect()

			controlForSidebarId.inspector.expanded =
				controlForSidebarId.inspector.oldExpanded

			controlForSidebarId.inspector.contentContainer[0].appendChild(
				parentEl.current
					.closest('.ct-customizer-panel')
					.lastElementChild.querySelector(
						'.customizer-panel-content form'
					)
			)

			panelsDispatch({
				type: 'PANEL_RECEIVE_META',
				payload: {
					secondLevelTitleLabel: null,
				},
			})
		}
	}, [])

	return (
		<div
			className="ct-option-widget-area customize-control-sidebar_block_editor ct-loading"
			ref={parentEl}>
			<svg
				width="15"
				height="15"
				viewBox="0 0 100 100"
				className="ct-loader">
				<g transform="translate(50,50)">
					<g transform="scale(1)">
						<circle cx="0" cy="0" r="50" fill="#687c93" />
						<circle
							cx="0"
							cy="-26"
							r="12"
							fill="#ffffff"
							transform="rotate(161.634)">
							<animateTransform
								attributeName="transform"
								type="rotate"
								calcMode="linear"
								values="0 0 0;360 0 0"
								keyTimes="0;1"
								dur="1s"
								begin="0s"
								repeatCount="indefinite"
							/>
						</circle>
					</g>
				</g>
			</svg>
		</div>
	)
}

export default BlockWidgetArea
