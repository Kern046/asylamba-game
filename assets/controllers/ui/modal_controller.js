import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
	static targets = ['dialog'];

	toggle()
	{
		if (!this.dialogTarget.open) {
			this.dialogTarget.showModal();
		} else {
			this.dialogTarget.close();
		}
	}
}
