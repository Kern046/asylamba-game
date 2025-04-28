import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
	markAsRead(event) {
		const currentTarget = event.currentTarget;

		if (currentTarget.dataset.read) {
			return;
		}

		fetch('/notifications/' + currentTarget.dataset.id + '/read', {
			method: 'PATCH',
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
			}
		}).then(() => {
			currentTarget.dataset.read = '';
		}).catch(error => console.error(error));
	}
}
