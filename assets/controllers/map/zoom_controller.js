import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
	static targets = ['map'];

	initialize() {
		super.initialize();

		this.isLoading = true;
		this.minZoom = 0.6;
		this.maxZoom = 2;
		this.zoom = 1;
		this.zoomStep = 0.2;
		this.sectorOnlyZoomLevel = 0.8;
		this.scrollTick = false;
	}

	connect()
	{
		setTimeout(() => {
			this.isLoading = false;
		}, 1000);
	}


	onWheel(event)
	{
		if (this.isLoading) {
			return;
		}
		if (!this.scrollTick) {
			window.requestAnimationFrame(() => {
				let zoom = this.zoom * Math.pow(1 + this.zoomStep, -event.deltaY / 125);
				const previousZoom = this.zoom;

				this.zoom = Math.max(Math.min(zoom, this.maxZoom), this.minZoom);

				if (this.zoom === previousZoom) {
					this.scrollTick = false;

					return;
				}

				this.element.style.transform = `scale(${zoom})`;


				if (this.zoom < this.sectorOnlyZoomLevel) {
					this.mapTarget.classList.add('sector-only');
				} else {
					this.mapTarget.classList.remove('sector-only');
				}

				this.scrollTick = false;
			});

			this.scrollTick = true;
		}
	}
}
