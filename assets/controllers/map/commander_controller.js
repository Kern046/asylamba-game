import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
	static targets = ['commander'];

	connect()
	{
		this.commander = null;
	}

	selectCommander(event)
	{
		this.commanderTargets.forEach(element => {
			delete element.dataset['selected'];
		});

		this.commander = {
			id: event.currentTarget.dataset.id,
			maxDistance: event.currentTarget.dataset.maxJump,
			isAvailable: event.currentTarget.dataset.available,
			name: event.currentTarget.dataset.name,
			rank: event.currentTarget.dataset.rank,
			capacity: event.currentTarget.dataset.capacity,
			factionIdentifier: event.currentTarget.dataset.factionIdentifier,
		};

		this.application.element.dispatchEvent(new Event('map:commander-selection'));

		event.currentTarget.dataset.selected = '';
	}
}
