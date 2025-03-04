import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
	static outlets = ['map--loader'];
	static targets = ['coordBox', 'loader'];
	static values = {
		beginXPosition: Number,
		beginYPosition: Number,
		mapSize: Number,
		scale: Number,
	};

	initialize() {
		super.initialize();

		this.isLoading = true;
		this.minZoom = -20;
		this.maxZoom = 10;
		this.zoom = 1;
		this.zoomStep = 0.5;
		this.sectorOnlyZoomLevel = 0.8;
		this.locked = false;
		this.scrollTick = false;
		this.lastMovement = {
			x: 0,
			y: 0,
		};
		this.lastPosition = {
			x: 0,
			y: 0,
		};
	}

	connect() {
		this.moveTo(
			this.beginXPositionValue,
			this.beginYPositionValue,
		);

		setTimeout(() => {
			this.mapLoaderOutlet.close();

			this.isLoading = false;
		}, 1000);
	}

	move(left, top) {
		this.updateViewport(
			this.lastPosition.x + left,
			this.lastPosition.y + top,
			this.zoom,
		);
	}

	// affiche les coordonnées actuelle
	showCoord() {
		this.coordBoxTarget.innerHTML =
			Math.ceil(-(parseInt(this.element.style.left) - Math.ceil(window.screen.width / 2)) / this.scaleValue)
			+ ':' +
			(Math.ceil(-(parseInt(this.element.style.top)  - Math.ceil(window.screen.height / 2)) / this.scaleValue) - 2)
		;
	}

	// résoud un couple de coordonnée et bouge vers celle-ci
	moveTo(x, y) {
		const halfScreenLength = this.getHalfScreenLength();

		const xDiff = this.lastPosition.x - (x * this.scaleValue) + 12;
		const yDiff = this.lastPosition.y - (y * this.scaleValue) + 12;

		// console.debug(halfScreenLength, x, y, xDiff, yDiff, this.scaleValue);

		const toLeft = halfScreenLength.x + xDiff;
		const toTop  = halfScreenLength.y + yDiff;

		// console.log('Moving to (%f;%f)', toLeft, toTop);

		this.move(toLeft, toTop);
	}

	getHalfScreenLength() {
		return {
			x: Math.ceil(this.application.element.clientWidth / 2),
			y: Math.ceil(this.application.element.clientHeight / 2),
		};
	}

	onMouseDown(event) {
		if (this.isLoading) {
			return;
		}
		if (event.button !== 0) {
			return;
		}

		this.locked = true;
		this.lastMovement.x = event.pageX;
		this.lastMovement.y = event.pageY;
	}

	onMouseUp(event) {
		if (this.isLoading) {
			return;
		}
		if (event.button !== 0) {
			return;
		}

		this.locked = false;
	}

	onMouseMove(event) {
		if (this.isLoading) {
			return;
		}
		if (this.locked !== true) {
			return;
		}

		const newX = event.pageX - this.lastMovement.x;
		const newY = event.pageY - this.lastMovement.y;

		this.move(newX, newY);

		this.lastMovement.x = event.pageX;
		this.lastMovement.y = event.pageY;
	}

	onWheel(event) {
		if (this.isLoading) {
			return;
		}
		if (!this.scrollTick) {
			window.requestAnimationFrame(() => {
				let zoom = this.zoom * Math.pow(1 + this.zoomStep, -event.deltaY / 125);
				const previousZoom = this.zoom;

				console.log('New zoom : %f; previous zoom : %f; Min zoom : %f; Max Zoom : %f', zoom, previousZoom, this.minZoom, this.maxZoom);

				this.zoom = Math.max(Math.min(zoom, this.maxZoom), this.minZoom);

				if (this.zoom === previousZoom) {
					this.scrollTick = false;

					return;
				}

				this.updateViewport(this.lastPosition.x, this.lastPosition.y, this.zoom);

				this.scrollTick = false;
			});

			this.scrollTick = true;
		}
	}

	translateWithNewScale(currentTranslation, previousScale)
	{
		const result = currentTranslation * (1 - this.zoom / previousScale);

		console.log('New translation : %d * (1 - %f / %f) = %f', currentTranslation, this.zoom, previousScale, result);

		return result;
	}

	updateViewport(x, y, zoom) {
		this.element.style.transform = `perspective(200px) translate3d(${x}px, ${y}px, ${zoom * this.scaleValue}px)`;

		this.lastPosition.x = x;
		this.lastPosition.y = y;
		this.zoom = zoom;

		console.log('New position : (%d;%d;%f)', x, y, zoom);
/*
		if (this.zoom < this.sectorOnlyZoomLevel) {
			this.element.classList.add('sector-only');
		} else {
			this.element.classList.remove('sector-only');
		}*/
	}

	getOverflow() {
		return 200;
	}
};
