import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
	static outlets = ['fleet--squadron'];
	static targets = ['base', 'squadron'];
	static values = {
		squadronUpdateUrl: String,
		base: String,
		manufactureMaxStorableShipPoints: Number,
		shipyardMaxStorableShipPoints: Number,
		shipPevs: Array,
	};

	initialize() {
		super.initialize();

		this.timer = null;
	}

	connect()
	{
		this.application.element.addEventListener('fleet:squadron-selection', () => this._updateAllTiles());
	}

	disconnect()
	{
		this.application.element.removeEventListener('fleet:squadron-selection');
	}

	transfer(event) {
		const squadron = this.fleetSquadronOutlet.squadron;
		if (squadron === null) {
			console.warn('No squadron selected');

			return;
		}

		const originTile = event.currentTarget;
		const originTileQuantityElement = originTile.querySelector('.quantity');
		let originTileQuantity = parseInt(originTileQuantityElement.innerText);

		if (originTileQuantity === 0) {
			console.warn('The desired tile has no ships left to transfer');

			return;
		}

		const shipIdentifier = originTile.dataset.shipId;
		const targetSide = (originTile.dataset.side === 'base') ? 'squadron' : 'base';
		const targetTile = this._getTileByIdentifierAndSide(targetSide, shipIdentifier);
		const targetTileQuantityElement = targetTile.querySelector('.quantity');
		let targetTileQuantity = parseInt(targetTileQuantityElement.innerText);

		const desiredQuantity = this._getDesiredQuantity(event, originTileQuantity);
		const desiredQuantityPevs = desiredQuantity * this.shipPevsValue[shipIdentifier];
		const availableSpace = targetSide === 'base' ? this._getHangarAvailableSpace(shipIdentifier) : this._getSquadronAvailableSpace();

		if (desiredQuantityPevs > availableSpace) {
			console.warn('Not enough space to transfer the ships. Remaining space : %d', availableSpace);

			return;
		}

		originTileQuantity -= desiredQuantity;
		targetTileQuantity += desiredQuantity;

		this.fleetSquadronOutlet.updateSquadron(shipIdentifier, targetSide === 'squadron' ? targetTileQuantity : originTileQuantity);

		this._updateTile(originTile, originTileQuantityElement, originTileQuantity);
		this._updateTile(targetTile, targetTileQuantityElement, targetTileQuantity);

		const squadronSnapshot = Object.assign({}, this.fleetSquadronOutlet.squadron);

		clearTimeout(this.timer);
		this.timer = setTimeout(() => {
			this._sendRequest(squadronSnapshot);
		}, 1000);
	}

	_getDesiredQuantity(event, maxQuantity)
	{
		let desiredQuantity = 1;

		if (event.ctrlKey) {
			desiredQuantity = maxQuantity;
		}

		if (event.shiftKey) {
			desiredQuantity = 5;
		}

		return Math.min(desiredQuantity, maxQuantity);
	}

	_getSquadronAvailableSpace()
	{
		return 100 - this.fleetSquadronOutlet.squadron.pev;
	}
	
	_getHangarAvailableSpace(shipIdentifier)
	{
		return (shipIdentifier < 6)
			? this._getManufactureAvailableSpace()
			: this._getShipyardAvailableSpace();
	}

	_getManufactureAvailableSpace()
	{
		return this.manufactureMaxStorableShipPointsValue - this.baseTargets.reduce((acc, element) => {
			const shipId = element.dataset.shipId;
			if (shipId < 6) {
				acc += parseInt(element.querySelector('.quantity').innerText) * this.shipPevsValue[shipId];

				return acc;
			}
			return acc;
		}, 0);
	}

	_getShipyardAvailableSpace()
	{
		return this.shipyardMaxStorableShipPointsValue - this.baseTargets.reduce((acc, element) => {
			const shipId = element.dataset.shipId;
			if (shipId >= 6) {
				acc += parseInt(element.querySelector('.quantity').innerText) * this.shipPevsValue[shipId];

				return acc;
			}
			return acc;
		}, 0);
	}

	_updateTile(tile, quantityElement, quantity)
	{
		quantityElement.innerText = quantity;

		if (quantity === 0) {
			tile.dataset.empty = '';
		} else {
			delete tile.dataset['empty'];
		}
	}

	_updateAllTiles()
	{
		this.squadronTargets.forEach(element => {
			this._updateTile(
				element,
				element.querySelector('.quantity'),
				this.fleetSquadronOutlet.squadron.ships[element.dataset.shipId],
			);
		});
	}

	_getTileByIdentifierAndSide(side, identifier)
	{
		return this[side + 'Targets'].find(element => element.dataset.shipId === identifier);
	}

	_sendRequest(squadron)
	{
		fetch(decodeURI(this.squadronUpdateUrlValue).replace('{squadronId}', squadron.id), {
			method: 'PATCH',
			headers: {
				'Content-Type': 'application/json',
			},
			credentials: 'include',
			body: JSON.stringify({
				base_id: this.baseValue,
				army: squadron.ships,
			}),
		});
	}
}
