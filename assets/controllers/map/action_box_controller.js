import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
	static targets = ['place', 'action'];
	static outlets = ['map--commander'];

	connect()
	{
		this.application.element.addEventListener('map:commander-selection', () => this.applyCommander());
	}

	disconnect()
	{
		this.application.element.removeEventListener('map:commander-selection');
	}

	changeSystem(systemId) {
		this.close();

		fetch('/systems/' + systemId)
			.then(response => response.text())
			.then(html => {
				this.element.innerHTML = html;
				this.open();
				// this.actionBoxOutlet.applyCommander();

			});
	}

	applyCommander()
	{
		this.actionTargets.forEach(element => {
			if (!('active' in element.dataset)) {
				return;
			}

			const commander = this.mapCommanderOutlet.commander;
			const commanderDetails = element.querySelector('.commander-details');

			if (commander === null) {
				element.querySelector('.no-commander').classList.remove('hidden');
				element.querySelector('.commander-too-far').classList.add('hidden');
				element.querySelector('.confirm-button').disabled = true;

				commanderDetails.classList.add('hidden');

			} else {
				element.querySelector('.no-commander').classList.add('hidden');

				const { distance, sectorColor } = this.element.querySelector('header').dataset;

				if (distance > commander.maxDistance && sectorColor !== commander.factionIdentifier) {
					element.querySelector('.commander-too-far').classList.remove('hidden');

					return;
				}
				element.querySelector('.commander-too-far').classList.add('hidden');
				element.querySelector('.confirm-button').disabled = false;

				commanderDetails.querySelector('.name').innerText = commander.name;
				commanderDetails.querySelector('.rank').innerText = commander.rank;
				const capacityValue = commanderDetails.querySelector('.capacity');

				if (capacityValue !== null) {
					capacityValue.innerText = commander.capacity;
				}

				commanderDetails.classList.remove('hidden')
			}
		});
	}

	open() {
		this.element.show();
	}

	close() {
		this.element.close();
	}

	deployPlacePanel(event) {
		this.placeTargets.forEach(element => {
			delete element.dataset['active'];
		});

		event.currentTarget.dataset['active'] = '';

		this.applyCommander();
	}

	chooseAction(event) {
		this.actionTargets.forEach(element => {
			if (element.dataset.id === event.currentTarget.dataset.actionId) {
				element.classList.remove('hidden');
				element.dataset.active = '';
			} else {
				element.classList.add('hidden');
				delete element.dataset['active'];
			}
		});

		event.currentTarget.dataset.active = '';
	}

	launchAction(event) {
		let actionUrl = decodeURI(event.currentTarget.dataset.url);

		if (actionUrl.includes('{id}')) {
			actionUrl = actionUrl.replace('{id}', this.mapCommanderOutlet.commander.id);
		}

		fetch(actionUrl, {
			headers: {
				Accept: 'application/json',
			},
		})
			.then(response => console.debug(response))
			.catch(error => console.error(error));
	}
}
