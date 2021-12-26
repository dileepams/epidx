import { handleVariablesFor } from 'customizer-sync-helpers'
import { applyPrefixFor } from 'blocksy-customizer-sync'

const prefix = 'blc-product-review_single'

handleVariablesFor({

	[`${prefix}_product_scores_width`]: {
		selector: applyPrefixFor('.ct-product-scores', prefix),
		variable: 'product-scores-width',
		unit: 'px',
	},

	[`${prefix}_star_rating_color`]: [
		{
			selector: applyPrefixFor('.ct-product-scores', prefix),
			variable: 'star-rating-initial-color',
			type: 'color:default',
		},

		{
			selector: applyPrefixFor('.ct-product-scores', prefix),
			variable: 'star-rating-inactive-color',
			type: 'color:inactive',
		},
	],

	[`${prefix}_overall_score_text`]: [
		{
			selector: applyPrefixFor('.ct-product-scores', prefix),
			variable: 'overall-score-text-color',
			type: 'color:default',
		},
	],

	[`${prefix}_overall_score_backgroud`]: [
		{
			selector: applyPrefixFor('.ct-product-scores', prefix),
			variable: 'overall-score-box-background',
			type: 'color:default',
		},
	],
})
