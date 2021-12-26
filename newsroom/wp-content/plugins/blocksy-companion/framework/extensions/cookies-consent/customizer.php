<?php

$options = [
	'title' => __('Cookie Consent', 'blocksy-companion'),
	'container' => [ 'priority' => 8 ],
	'options' => [

		'cookie_consent_section_options' => [
			'type' => 'ct-options',
			'setting' => [ 'transport' => 'postMessage' ],
			'inner-options' => [
				blocksy_rand_md5() => [
					'type' => 'ct-title',
					'label' => __( 'Cookies Notification', 'blocksy' ),
				],

				blocksy_rand_md5() => [
					'title' => __( 'General', 'blocksy-companion' ),
					'type' => 'tab',
					'options' => [

						'cookie_consent_type' => [
							'label' => false,
							'type' => 'ct-image-picker',
							'value' => 'type-1',
							'setting' => [ 'transport' => 'postMessage' ],
							'choices' => [

								'type-1' => [
									'src'   => BLOCKSY_URL . 'framework/extensions/cookies-consent/static/images/type-1.svg',
									'title' => __( 'Type 1', 'blocksy-companion' ),
								],

								'type-2' => [
									'src'   => BLOCKSY_URL . 'framework/extensions/cookies-consent/static/images/type-2.svg',
									'title' => __( 'Type 2', 'blocksy-companion' ),
								],

							],
						],

						'cookie_consent_period' => [
							'label' => __('Cookie period', 'blocksy-companion'),
							'type' => 'ct-select',
							'value' => 'forever',
							'design' => 'inline',
							'setting' => [ 'transport' => 'postMessage' ],
							'choices' => blocksy_ordered_keys(

								[
									'onehour' => __( 'One hour', 'blocksy-companion' ),
									'oneday' => __( 'One day', 'blocksy-companion' ),
									'oneweek' => __( 'One week', 'blocksy-companion' ),
									'onemonth' => __( 'One month', 'blocksy-companion' ),
									'threemonths' => __( 'Three months', 'blocksy-companion' ),
									'sixmonths' => __( 'Six months', 'blocksy-companion' ),
									'oneyear' => __( 'One year', 'blocksy-companion' ),
									'forever' => __('Forever', 'blocksy-companion')
								]

							),
						],

						'cookie_consent_content' => [
							'label' => __( 'Content', 'blocksy-companion' ),
							'type' => 'wp-editor',
							'value' => __('We use cookies to ensure that we give you the best experience on our website.', 'blocksy-companion'),
							'disableRevertButton' => true,
							'setting' => [ 'transport' => 'postMessage' ],

							'quicktags' => false,
							'mediaButtons' => false,
							'tinymce' => [
								'toolbar1' => 'bold,italic,link,alignleft,aligncenter,alignright,undo,redo',
							],
						],

						'cookie_consent_button_text' => [
							'label' => __( 'Button text', 'blocksy-companion' ),
							'type' => 'text',
							'design' => 'block',
							'value' => __('Accept', 'blocksy-companion'),
							'setting' => [ 'transport' => 'postMessage' ],
						],

					],
				],

				blocksy_rand_md5() => [
					'title' => __( 'Design', 'blocksy-companion' ),
					'type' => 'tab',
					'options' => [

						'cookieContentColor' => [
							'label' => __( 'Font Color', 'blocksy-companion' ),
							'type'  => 'ct-color-picker',
							'design' => 'inline',
							'setting' => [ 'transport' => 'postMessage' ],

							'value' => [
								'default' => [
									'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
								],

								'hover' => [
									'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
								],
							],

							'pickers' => [
								[
									'title' => __( 'Initial', 'blocksy-companion' ),
									'id' => 'default',
									'inherit' => 'var(--color)'
								],

								[
									'title' => __( 'Hover', 'blocksy-companion' ),
									'id' => 'hover',
									'inherit' => 'var(--colorHover)'
								],
							],
						],

						'cookieButtonBackground' => [
							'label' => __( 'Button Color', 'blocksy-companion' ),
							'type'  => 'ct-color-picker',
							'design' => 'inline',
							'setting' => [ 'transport' => 'postMessage' ],
							'value' => [
								'default' => [
									'color' => 'var(--paletteColor1)',
								],

								'hover' => [
									'color' => 'var(--paletteColor2)',
								],
							],

							'pickers' => [
								[
									'title' => __( 'Initial', 'blocksy-companion' ),
									'id' => 'default',
								],

								[
									'title' => __( 'Hover', 'blocksy-companion' ),
									'id' => 'hover',
								],
							],
						],

						'cookieBackground' => [
							'label' => __( 'Background Color', 'blocksy-companion' ),
							'type'  => 'ct-color-picker',
							'design' => 'inline',
							'setting' => [ 'transport' => 'postMessage' ],

							'value' => [
								'default' => [
									'color' => '#ffffff',
								],
							],

							'pickers' => [
								[
									'title' => __( 'Initial', 'blocksy-companion' ),
									'id' => 'default',
								],
							],
						],

						blocksy_rand_md5() => [
							'type' => 'ct-condition',
							'condition' => [ 'cookie_consent_type' => 'type-1' ],
							'options' => [

								'cookieMaxWidth' => [
									'label' => __( 'Maximum Width', 'blocksy-companion' ),
									'type' => 'ct-slider',
									'value' => 400,
									'min' => 200,
									'max' => 500,
									'setting' => [ 'transport' => 'postMessage' ],
								],

							],
						],

					],
				],

				blocksy_rand_md5() => [
					'type' => 'ct-title',
					'label' => __( 'Forms Cookies Content', 'blocksy' ),
				],

				'forms_cookie_consent_content' => [
					'label' => false,
					'type' => 'wp-editor',
					'value' => sprintf(
						__('I accept the %sPrivacy Policy%s*', 'blocksy-companion'),
						'<a href="/privacy-policy">',
						'</a>'
					),
					'desc' => __( 'This text will appear under each comment form and subscribe form.', 'blocksy-companion' ),
					// 'attr' => [ 'data-height' => 'heading-label' ],
					'disableRevertButton' => true,
					'setting' => [ 'transport' => 'postMessage' ],

					'quicktags' => false,
					'mediaButtons' => false,
					'tinymce' => [
						'toolbar1' => 'bold,italic,link,alignleft,aligncenter,alignright,undo,redo',
						'forced_root_block' => '',
						'force_br_newlines' => true,
						'force_p_newlines' => false
					],
				],

			],
		],
	],
];
