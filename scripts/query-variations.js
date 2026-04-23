wp.domReady( function () {
	wp.blocks.registerBlockVariation( 'core/query', {
		name: 'dc26/conferences-a-venir',
		title: 'Conférences à venir',
		description: 'Conférences dont la date ACF est >= aujourd\'hui, triées par date croissante.',
		icon: 'calendar',
		attributes: {
			namespace: 'dc26/conferences-a-venir',
			query: {
				perPage: 10,
				pages: 0,
				offset: 0,
				postType: 'conference-du-stage',
				order: 'asc',
				orderBy: 'date',
				author: '',
				search: '',
				exclude: [],
				sticky: '',
				inherit: false,
			},
		},
		isActive: [ 'namespace' ],
		scope: [ 'inserter' ],
	} );

	wp.blocks.registerBlockVariation( 'core/query', {
		name: 'dc26/conferences-passees',
		title: 'Conférences passées',
		description: 'Conférences dont la date ACF est < aujourd\'hui, triées par date décroissante.',
		icon: 'backup',
		attributes: {
			namespace: 'dc26/conferences-passees',
			query: {
				perPage: 10,
				pages: 0,
				offset: 0,
				postType: 'conference-du-stage',
				order: 'desc',
				orderBy: 'date',
				author: '',
				search: '',
				exclude: [],
				sticky: '',
				inherit: false,
			},
		},
		isActive: [ 'namespace' ],
		scope: [ 'inserter' ],
	} );
} );
