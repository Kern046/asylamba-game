import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
	close() {
		this.element.classList.add('opacity-0');

		setTimeout(() => {
			this.element.classList.add('hidden');
		}, 1000);
	}
}
