import { useState } from 'react';
import { fireEvent, render, screen } from '@testing-library/react';
import Replace from '../../../../component/replace';
import { getReplacementInputValue, getSimpleReplacement } from '../utils';
import type { ResultColumn, SchemaColumn } from '../../../../types/search';

function RemoveReplacement() {
	const [ replacement, setReplacement ] = useState< string | null >( '' );

	return (
		<>
			<Replace
				disabled={ false }
				setReplace={ ( value ) => setReplacement( getSimpleReplacement( value ) ) }
				replacement={ getReplacementInputValue( replacement ) }
				schema={ { type: 'string' } as SchemaColumn }
				column={ { column_id: 'global', contexts: [] } as unknown as ResultColumn }
			/>
			<button disabled={ replacement !== null }>Replace All</button>
		</>
	);
}

describe( 'remove replacement', () => {
	it( 'enables Replace All when Remove is selected from an empty replacement', () => {
		render( <RemoveReplacement /> );

		const replaceAll = screen.getByRole< HTMLButtonElement >( 'button', { name: 'Replace All' } );
		expect( replaceAll.disabled ).toBe( true );

		fireEvent.change( screen.getByRole( 'combobox' ), { target: { value: 'remove' } } );

		expect( replaceAll.disabled ).toBe( false );
	} );
} );
