import { connect } from 'react-redux';
import { __ } from '@wordpress/i18n';
import { getAvailableSearchFlags } from '../../state/search/selector';
import { getSource } from '../../lib/sources';
import type { PresetValue } from '../../types/preset';
import type { SearchSourceGroup } from '../../types/search';

interface RootState {
	search: {
		sources: SearchSourceGroup[];
	};
}

interface PresetFlagsProps {
	preset: PresetValue;
	sources?: SearchSourceGroup[];
}

function PresetFlags( props: PresetFlagsProps ) {
	const { sources = [], preset } = props;
	const { search, locked } = preset;
	const flags: string[] = [];
	const { searchFlags = [], source = [] } = search;

	// Add sources
	for ( let index = 0; index < source.length; index++ ) {
		const flag = getSource( sources, source[ index ] );

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

function mapStateToProps( state: RootState ) {
	const { sources } = state.search;

	return {
		sources,
	};
}

export default connect( mapStateToProps, null )( PresetFlags );
