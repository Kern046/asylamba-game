import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
	static targets = ['input'];

	select(event) {
		this.inputTarget.value = event.currentTarget.dataset.sectorId;
	}
};
