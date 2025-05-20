import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
	static targets = ['part', 'rest', 'investment'];
	static values = {
		investment: Number,
	};

	increase(event)
	{
		const type = event.currentTarget.dataset.type;
		const requiredQuantity = event.ctrlKey ? 10 : 1;
		const quantity = Math.min(this._getRest(), requiredQuantity);

		if (0 === quantity) {
			return;
		}

		this._update(type, 'increase', quantity);
	}

	decrease(event)
	{
		const type = event.currentTarget.dataset.type;
		const requiredQuantity = event.ctrlKey ? 10 : 1;
		const partQuantity = parseInt(this._getPart(type).innerText);
		const quantity = Math.min(partQuantity, requiredQuantity);

		if (0 === quantity) {
			return;
		}

		this._update(type, 'decrease', quantity);
	}

	_update(type, operation, quantity)
	{
		fetch('api/university/' + type + '/' + operation, {
			method: 'PATCH',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({ quantity }),
		}).then(response => {
			const rest = this._getRest();

			const newRest = (operation === 'increase') ? rest - quantity : rest + quantity;
			this.restTarget.querySelector('#rest').innerText = newRest;
			const part = this._getPart(type);
			const partQuantity = parseInt(part.innerText);
			const newPartQuantity = (operation === 'increase') ? partQuantity + quantity : partQuantity - quantity;
			part.innerText = newPartQuantity;
			part.parentElement.parentElement.parentElement.querySelector('.progress-bar > div').style.width = newPartQuantity + '%';

			this.restTarget.querySelector('.progress-bar > div').style.width = newRest + '%';
			if (newRest > 0) {
				this.restTarget.classList.remove('hidden');
			} else {
				this.restTarget.classList.add('hidden');
			}

			this._getInvestment(type).innerText = new Intl.NumberFormat('fr-FR').format(Math.round(this.investmentValue * newPartQuantity / 100));
		});
	}

	_getRest()
	{
		return parseInt(this.restTarget.querySelector('#rest').innerText);
	}

	_getPart(type)
	{
		return this.partTargets.find(part => part.dataset.type === type);
	}

	_getInvestment(type)
	{
		return this.investmentTargets.find(part => part.dataset.type === type);
	}
}
