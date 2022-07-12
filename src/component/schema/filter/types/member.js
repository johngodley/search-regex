/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { useSelector, useDispatch } from 'react-redux';

/**
 * Internal dependencies
 */

import Logic from '../logic';
import { DropdownText, MultiOptionDropdown } from '@wp-plugin-components';
import SearchFlags from '../../../search-flags';
import { setLabel } from '../../../../state/search/action';
import { getLabel } from '../../../../state/search/selector';

function setValues( values, changed, all ) {
	if ( changed === '' ) {
		return values.indexOf( '' ) === -1 ? [] : all;
	}

	return values.filter( ( item ) => item !== '' );
}

function getValues( values, allLength ) {
	if ( values.length === allLength ) {
		return [ '' ].concat( values );
	}

	return values;
}

function getOptions( options ) {
	return [
		{
			value: '',
			label: __( 'All', 'search-regex' ),
		},
	].concat( options );
}

function FilterMember( props ) {
	const { disabled, item, onChange, schema, fetchData } = props;
	const { logic = 'include', values = [], flags = [ 'case' ] } = item;
	const remote = schema.options === 'api' ? fetchData : false;
	const { labels } = useSelector( ( state ) => state.search );
	const dispatch = useDispatch();

	const logicComponent = (
		<Logic
			type="member"
			value={ logic }
			disabled={ disabled }
			onChange={ ( value ) => onChange( { logic: value, values: [] } ) }
		/>
	);

	if ( logic === 'contains' || logic === 'notcontains' ) {
		return (
			<>
				{ logicComponent }
				<DropdownText
					value={ values.length === 0 ? '' : values[ 0 ] }
					disabled={ disabled }
					onChange={ ( newValue ) => onChange( { values: [ newValue ] } ) }
				/>
				<SearchFlags
					flags={ flags }
					disabled={ disabled }
					onChange={ ( value ) => onChange( { flags: value } ) }
					allowRegex={ false }
					allowMultiline={ false }
				/>
			</>
		);
	}

	if ( remote ) {
		return (
			<>
				{ logicComponent }
				<DropdownText
					value={ values }
					disabled={ disabled }
					onChange={ ( newValue ) => onChange( { values: newValue } ) }
					fetchData={ remote }
					loadOnFocus={ schema.preload }
					maxChoices={ 20 }
					onlyChoices
					setLabel={ ( labelId, labelValue ) =>
						dispatch( setLabel( schema.column + '_' + labelId, labelValue ) )
					}
					getLabel={ ( labelId ) => getLabel( labels, schema.column + '_' + labelId ) }
				/>
			</>
		);
	}

	return (
		<>
			{ logicComponent }
			{ ! remote && (
				<MultiOptionDropdown
					options={ getOptions( schema.options ) }
					selected={ getValues( values, schema.options.length ) }
					onApply={ ( newValue, changed ) =>
						onChange( {
							values: setValues( newValue, changed, schema.options.map( ( item ) => item.value ) ),
						} )
					}
					multiple={ schema.multiple ?? true }
					disabled={ disabled }
					hideTitle
					title={ schema.title }
					badges
					customBadge={ ( selected ) => ( selected.length >= schema.options.length ? [ '' ] : selected ) }
				/>
			) }
		</>
	);
}

export default FilterMember;
