<script>
import {Popover, PopoverButton, PopoverPanel, Transiti } from "@headlessui/vue";

export default {
	name: 'ToolbarTogglerLeft',

	template: require('./ToolbarTogglerLeft.html').default,

	props: ['customStyle'],

	components: { Popover, PopoverButton, PopoverPanel },

	computed: {
		style: function () {
			return this.customStyle ?? 'mt-2 faction-bd-dark border border-solid';
		},

		uniqueKey: function () {
			return Math.random().toString(36);
		}
	}
}
</script>
