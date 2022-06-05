<script>
import {Popover, PopoverButton, PopoverPanel, Transiti } from "@headlessui/vue";

export default {
	name: 'ToolbarToggler',

	template: require('./ToolbarToggler.html').default,

	props: ['customStyle'],

	components: { Popover, PopoverButton, PopoverPanel },

	computed: {
		style: function () {
			return this.customStyle ?? 'left-1/2 mt-3 w-screen max-w-sm -translate-x-1/2 transform';
		},

		uniqueKey: function () {
			return Math.random().toString(36);
		}
	}
}
</script>
