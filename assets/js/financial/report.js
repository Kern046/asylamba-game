import Chart from 'chart.js/auto';

const chart = new Chart(
	document.getElementById('financial-report-chart'),
	{
		type: 'line',
		data: {
			labels: window.reports_labels.slice(),
			datasets: [
				{
					label: 'Taxes planétaires',
					data: window.reports_data['populationTaxes'].slice(),
					backgroundColor: '#89C43A',
					borderColor: '#74C136',
					order: 1,
				},
				{
					label: 'Revenus des routes commerciales',
					data: window.reports_data['commercialRoutesIncome'].slice(),
					backgroundColor: '#89C43A',
					borderColor: '#74C136',
					order: 2,
				},
				{
					label: 'Gain/Perte',
					data: window.reports_data['diff'].slice(),
					borderColor: 'white',
					stack: 'ownCustomStack',
					order: -1,
				},
				{
					label: 'Vente de vaisseaux',
					data: window.reports_data['shipsSales'].slice(),
					backgroundColor: '#89C43A',
					borderColor: '#74C136',
				},
				{
					label: 'Crédits recyclés',
					data: window.reports_data['recycledCredits'].slice(),
					backgroundColor: '#89C43A',
					borderColor: '#74C136',
				},
				{
					label: 'Vente de ressources',
					data: window.reports_data['resourcesSales'].slice(),
					backgroundColor: '#89C43A',
					borderColor: '#74C136',
				},
				{
					label: 'Dotations de la faction',
					data: window.reports_data['receivedFactionsCreditTransactions'].slice(),
					backgroundColor: '#89C43A',
					borderColor: '#74C136',
				},
				{
					label: 'Transferts de fonds personnels',
					data: window.reports_data['receivedPlayersCreditTransactions'].slice(),
					backgroundColor: '#89C43A',
					borderColor: '#74C136',
				},
				{
					label: 'Vente de commandants',
					data: window.reports_data['commandersSales'].slice(),
					backgroundColor: '#89C43A',
					borderColor: '#74C136',
				},
				{
					label: 'Investissements universitaires',
					data: window.reports_data['universityInvestments'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Investissements école',
					data: window.reports_data['schoolInvestments'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Contre-espionnage',
					data: window.reports_data['antiSpyInvestments'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Redevances de faction',
					data: window.reports_data['factionTaxes'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Salaires des commandants',
					data: window.reports_data['commandersWages'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Entretien des vaisseaux',
					data: window.reports_data['shipsCost'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Construction de routes commerciales',
					data: window.reports_data['commercialRoutesConstructions'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Achat de vaisseaux',
					data: window.reports_data['shipsPurchases'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Achat de commandants',
					data: window.reports_data['commandersPurchases'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Achat de ressources',
					data: window.reports_data['resourcesPurchases'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Dons à la faction',
					data: window.reports_data['sentFactionsCreditTransactions'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Dons personnels',
					data: window.reports_data['sentPlayersCreditTransactions'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Recherches technologiques',
					data: window.reports_data['technologiesInvestments'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Redevances de faction',
					data: window.reports_data['factionTaxes'].slice(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
			]
		},
		options: {
			responsive: true,
			fill: 'origin',
			fillOpacity: 1,
			interaction: {
				intersect: false,
				mode: 'index',
			},
			plugins: {
				legend: {
					display: false,
				}
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

new Chart(
	document.getElementById('commercial-report-chart'),
	{
		type: 'bar',
		data: {
			labels: window.reports_labels.reverse(),
			datasets: [
				{
					label: 'Revenus des routes commerciales',
					data: window.reports_data['commercialRoutesIncome'].reverse(),
					backgroundColor: '#89C43A',
					borderColor: '#74C136',
				},
			{
				label: 'Vente de vaisseaux',
				data: window.reports_data['shipsSales'].reverse(),
				backgroundColor: '#89C43A',
				borderColor: '#74C136',
			},
			{
				label: 'Vente de ressources',
				data: window.reports_data['resourcesSales'].reverse(),
				backgroundColor: '#89C43A',
				borderColor: '#74C136',
			},
			{
				label: 'Vente de commandants',
				data: window.reports_data['commandersSales'].reverse(),
				backgroundColor: '#89C43A',
				borderColor: '#74C136',
			},
			{
				label: 'Construction de routes commerciales',
				data: window.reports_data['commercialRoutesConstructions'].reverse(),
				borderColor: '#9D1F0E',
				backgroundColor: '#8F1C0D',
			},
			{
				label: 'Achat de vaisseaux',
				data: window.reports_data['shipsPurchases'].reverse(),
				borderColor: '#9D1F0E',
				backgroundColor: '#8F1C0D',
			},
			{
				label: 'Achat de commandants',
				data: window.reports_data['commandersPurchases'].reverse(),
				borderColor: '#9D1F0E',
				backgroundColor: '#8F1C0D',
			},
			{
				label: 'Achat de ressources',
				data: window.reports_data['resourcesPurchases'].reverse(),
				borderColor: '#9D1F0E',
				backgroundColor: '#8F1C0D',
			},
		]
	},
		options: {
			responsive: false,
			aspectRatio: 0.5,
			interaction: {
				intersect: false,
				mode: 'index',
			},
			plugins: {
				legend: {
					display: false,
				},
			},
			indexAxis: 'y',
			barThickness: 10,
		}
	}
)


new Chart(
	document.getElementById('investments-report-chart'),
	{
		type: 'bar',
		data: {
			labels: window.reports_labels.reverse(),
			datasets: [
				{
					label: 'Crédits recyclés',
					data: window.reports_data['recycledCredits'].reverse(),
					backgroundColor: '#89C43A',
					borderColor: '#74C136',
				},
				{
					label: 'Taxes planétaires',
					data: window.reports_data['populationTaxes'].reverse(),
					backgroundColor: '#89C43A',
					borderColor: '#74C136',
				},
				{
					label: 'Redevances de faction',
					data: window.reports_data['factionTaxes'].reverse(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Contre-espionnage',
					data: window.reports_data['antiSpyInvestments'].reverse(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Investissements universitaires',
					data: window.reports_data['universityInvestments'].reverse(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Recherches technologiques',
					data: window.reports_data['technologiesInvestments'].reverse(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Ecole de commandement',
					data: window.reports_data['schoolInvestments'].reverse(),
					borderColor: '#9D1F0E',
					backgroundColor: '#8F1C0D',
				},
				{
					label: 'Salaires des commandants',
					data: window.reports_data['commandersWages'],
					backgroundColor: '#9D1F0E',
				},
				{
					label: 'Entretien des vaisseaux',
					data: window.reports_data['shipsCost'],
					backgroundColor: '#8F1C0D',
				},
		]
	},
		options: {
			responsive: false,
			aspectRatio: 0.5,
			interaction: {
				intersect: false,
				mode: 'index',
			},
			plugins: {
				legend: {
					display: false,
				},
			},
			indexAxis: 'y',
			barThickness: 10,
		}
	}
)



new Chart(
	document.getElementById('transfer-report-chart'),
	{
		type: 'bar',
		data: {
			labels: window.reports_labels.reverse(),
			datasets: [
			{
				label: 'Transferts de fonds personnels',
				data: window.reports_data['receivedPlayersCreditTransactions'].reverse(),
				backgroundColor: '#89C43A',
				borderColor: '#74C136',
			},
			{
				label: 'Dotations de la faction',
				data: window.reports_data['receivedFactionsCreditTransactions'].reverse(),
				backgroundColor: '#89C43A',
				borderColor: '#74C136',
			},
			{
				label: 'Dons personnels',
				data: window.reports_data['sentPlayersCreditTransactions'].reverse(),
				borderColor: '#9D1F0E',
				backgroundColor: '#8F1C0D',
			},
			{
				label: 'Dons à la faction',
				data: window.reports_data['sentFactionsCreditTransactions'].reverse(),
				borderColor: '#9D1F0E',
				backgroundColor: '#8F1C0D',
			},
		]
	},
		options: {
			responsive: false,
			aspectRatio: 0.5,
			interaction: {
				intersect: false,
				mode: 'index',
			},
			plugins: {
				legend: {
					display: false,
				},
			},
			indexAxis: 'y',
			barThickness: 10,
		}
	}
)
