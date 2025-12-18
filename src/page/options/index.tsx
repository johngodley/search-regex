import { connect } from 'react-redux';
import OptionsForm from './options-form';

interface RootState {
	settings: {
		values: Record< string, unknown >;
	};
}

function Options() {
	return (
		<div>
			<OptionsForm />
		</div>
	);
}

function mapStateToProps( state: RootState ) {
	const { values } = state.settings;

	return {
		values,
	};
}

export default connect( mapStateToProps, null )( Options );
