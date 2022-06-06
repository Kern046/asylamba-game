<script>
export default {
	name: 'ToolbarPicto',

	template: require('./ToolbarPicto.html').default,

	props: ['href', 'route', 'routes', 'requested_route', 'alt'],

	computed: {
		hasCustomStyle() {
			return typeof this.routes !== 'undefined' && this.routes.includes(this.requested_route) || typeof this.route !== 'undefined' && this.requested_route === this.route;
		},

		title() {
			return this.alt || this.route || '';
		}
	},
}
</script>
