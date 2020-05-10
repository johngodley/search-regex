export function getAllPostTypes( sources ) {
	const postTypes = sources.find( source => source.name === 'posttype' );

	if ( postTypes ) {
		return postTypes.sources.map( source => source.name );
	}

	return [];
}

export function removePostTypes( source, sources ) {
	if ( source.indexOf( 'posts' ) !== -1 ) {
		const allPostTypes = getAllPostTypes( sources );
		return source.filter( item => allPostTypes.indexOf( item ) === -1 );
	}

	return source;
}
