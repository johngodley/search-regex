import { __ } from '@wordpress/i18n';
import { getAvailableSearchFlags } from '../../lib/search-utils';
import { getSource } from '../../lib/sources';
import { useSearchStore } from '../../stores/search-store';
import type { PresetValue } from '../../types/preset';

interface PresetFlagsProps {
	preset: PresetValue;
}

function PresetFlags( props: PresetFlagsProps ) {
	const sources = useSearchStore( ( state ) => state.sources );
	const { preset } = props;
	const { search, locked } = preset;
	const flags: string[] = [];
	const { searchFlags = [], source = [] } = search;

	// Add sources
	for ( let index = 0; index < source.length; index++ ) {
		const sourceName = source[ index ];
		if ( ! sourceName ) {
			continue;
		}

		const flag = getSource( sources, sourceName );

		if ( flag ) {
			flags.push( flag.label );
		}
	}

	// Add non-default search flags
	for ( let index = 0; index < searchFlags.length; index++ ) {
		const searchFlag = getAvailableSearchFlags().find( ( flag ) => flag.value === searchFlags[ index ] );

		if ( searchFlag && searchFlags[ index ] !== 'case' ) {
			flags.push( searchFlag.label );
		}
	}

	if ( locked.length > 0 ) {
		flags.push( __( 'Locked fields', 'search-regex' ) );
	}

	return <p>{ flags.join( ', ' ) }</p>;
}

export default PresetFlags;
