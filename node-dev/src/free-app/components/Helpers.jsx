export const generatShortkey = ( value ) => {
	return value.substr( value.length - 4 );
};

export const shortenKey = ( key, length ) => {
	if ( key.length <= length ) {
		return key;
	}
	const prefix = key.slice( 0, length - 3 ); // Extract characters up to length - 3
	return `${ prefix }...`; // Add ellipsis
};
