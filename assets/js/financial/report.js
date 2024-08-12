import Chart from 'chart.js/auto';

const chart = new Chart(
	document.getElementById('financial-report-chart'),
	{
		type: 'line',
		data: {
			labels: window.reports_labels,
			datasets: [
				{
					label: 'Taxes planétaires',
					data: window.reports_data['populationTaxes'],
					backgroundColor: '#89C43A',
					fill: 'origin',
					fillOpacity: 1,
					order: 1,
				},
				{
					label: 'Revenus des routes commerciales',
					data: window.reports_data['commercialRoutesIncome'],
					backgroundColor: '#74C136',
					fill: 'origin',
					fillOpacity: 1,
					order: 2,
				},
				{
					label: 'Gain/Perte',
					data: window.reports_data['diff'],
					borderColor: 'white',
					stack: 'ownCustomStack',
					order: -1,
				},
				{
					label: 'Investissements universitaires',
					data: window.reports_data['universityInvestments'],
					backgroundColor: '#AE5A3D',
					fill: 'origin',
				},
				{
					label: 'Investissements école',
					data: window.reports_data['schoolInvestments'],
					backgroundColor: '#A6492A',
					fill: 'origin',
				},
				{
					label: 'Contre-espionnage',
					data: window.reports_data['antiSpyInvestments'],
					backgroundColor: '#A24020',
					fill: 'origin',
				},
				{
					label: 'Redevances de faction',
					data: window.reports_data['factionTaxes'],
					backgroundColor: '#A03017',
					fill: 'origin',
				},
				{
					label: 'Salaires des commandants',
					data: window.reports_data['commandersWages'],
					backgroundColor: '#9D1F0E',
					fill: 'origin',
				},
				{
					label: 'Entretien des vaisseaux',
					data: window.reports_data['shipsCost'],
					fill: 'origin',
					backgroundColor: '#8F1C0D',
				},
			]
		},
		options: {
			responsive: true,
			interaction: {
				intersect: false,
				mode: 'index',
			},
			scales: {
				y: {
					stacked: true,
					display: true,
				}
			}
		}
	}
);

console.debug(chart);
