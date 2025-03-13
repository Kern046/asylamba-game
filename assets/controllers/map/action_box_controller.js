import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
	static targets = ['place', 'action'];

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

	applyCommander() {
		if (actionbox.opened) {
			if (mapController.commanders.active) {
				actionbox.obj.find('.commander-tile .item').hide();

				if (actionbox.obj.find('.header').data('distance') > mapController.commanders.maxJump && actionbox.obj.find('.header').data('sector-color') != mapController.commanders.color) {
					actionbox.obj.find('.commander-tile .item.too-far').show();
				} else {
					var items = actionbox.obj.find('.commander-tile .item.move');

					items.find('a').each(function() {
						var url = $(this).data('url');
						$(this).attr('href', url.replace(encodeURI('{id}'), mapController.commanders.id));
					});

					items.show();
					items.find('.name').text(mapController.commanders.name);
					items.find('.wedge').text(mapController.commanders.wedge);
				}
			} else {
				actionbox.obj.find('.commander-tile .item').hide();
				actionbox.obj.find('.commander-tile .item.no-commander').show();
			}
		}
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
	}

	chooseAction(event) {
		this.actionTargets.forEach(element => {
			if (element.dataset.id === event.currentTarget.dataset.actionId) {
				element.classList.remove('hidden');
			} else {
				element.classList.add('hidden');
			}
		});

		event.currentTarget.dataset.active = '';
	}
}
