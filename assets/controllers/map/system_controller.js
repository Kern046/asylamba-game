import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
	static targets = ['system-details'];
	static outlets = ['map--action-box'];
	static values = {
		id: String,
	};

	select(event)
	{
		this.mapActionBoxOutlet.changeSystem(this.idValue);
	}
};
