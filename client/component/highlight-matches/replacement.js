/**
 * External dependencies
 */

import React from 'react';
import classnames from 'classnames';
import { translate as __ } from 'wp-plugin-library/lib/locale';

/**
 * Internal dependencies
 */

import { Dropdown } from 'wp-plugin-library';
import Replace from 'component/replace';

function Replacement( props ) {
	const { typeOfReplacement, isReplacing, onUpdate, onSave, children, match } = props;
	const save = ( replacedPhrase, toggle ) => {
		onSave( replacedPhrase );
		toggle();
	};
	const reset = ( toggle ) => {
		onUpdate( '' );
		toggle && toggle();
	};

	return (
		<Dropdown
			className={ classnames( {
				'searchregex-result__replaced': typeOfReplacement === 'replace',
				'searchregex-result__highlight': typeOfReplacement === 'match',
				'searchregex-result__deleted': typeOfReplacement === 'delete',
			} ) }
			renderToggle={ ( isOpen, toggle ) => (
				<span onClick={ () => ! isReplacing && toggle() } title={ match }>
					{ children }
				</span>
			) }
			onHide={ reset }
			hasArrow
			align="centre"
			offset={ 25 }
			renderContent={ ( toggle ) => (
				<Replace
					className="searchregex-replace__modal"
					canReplace
					setReplace={ ( replace ) => onUpdate( replace ) }
					autoFocus
					onSave={ ( value ) => save( value, toggle ) }
					onCancel={ () => reset( toggle ) }
					placeholder={ __( 'Replacement for this match' ) }
					description={ __( 'Replace single phrase.' ) }
				/>
			) }
		/>
	);
}

export default Replacement;
