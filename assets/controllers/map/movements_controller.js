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
		this.locked = false;
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
		}, 1200);
	}

	move(left, top) {
		this.updateViewport(
			this.lastPosition.x + left,
			this.lastPosition.y + top,
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

		const xDiff = this.lastPosition.x - (x * this.scaleValue) + 8;
		const yDiff = this.lastPosition.y - (y * this.scaleValue) + 8;

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

	updateViewport(x, y, zoom) {
		this.element.style.transform = `translate3d(${x}px, ${y}px, 0px)`;

		this.lastPosition.x = x;
		this.lastPosition.y = y;
	}

	getOverflow() {
		return 200;
	}
};
