import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
	static targets = ['squadron'];
	static values = {
		shipPevs: Array,
	};

	initialize()
	{
		super.initialize();

		this.squadron = null;
	}

	selectSquadron(event)
	{
		this.squadronTargets.forEach(element => {
			if (element.dataset.squadronId === event.currentTarget.dataset.squadronId) {
				element.dataset.selected = '';

				this.squadron = {
					id: element.dataset.squadronId,
					ships: JSON.parse(element.dataset.squadronShips),
					pev: element.dataset.squadronPev,
				};

				this.application.element.dispatchEvent(new Event('fleet:squadron-selection'));
			} else {
				delete element.dataset['selected'];
			}
		})
	}

	updateSquadron(shipIdentifier, quantity)
	{
		this.squadron.ships[shipIdentifier] = quantity;
		this.squadron.pev = this.squadron.ships.reduce(
			(acc, quantity, shipId) => acc + quantity * this.shipPevsValue[shipId],
			0,
		);

		const squadronElement = this.squadronTargets.find(element => element.dataset.squadronId === this.squadron.id);

		squadronElement.dataset.squadronShips = JSON.stringify(this.squadron.ships);
		squadronElement.dataset.squadronPev = this.squadron.pev;
		squadronElement.querySelector('.pevs').innerText = this.squadron.pev + '/100';
	}
}
