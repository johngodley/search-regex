import { MouseEvent } from 'react';

interface NavProps {
	title: string;
	button: string;
	className: string;
	enabled: boolean;
	onClick: () => void;
}

const Nav = ( props: NavProps ) => {
	const { title, button, className, enabled, onClick } = props;
	const click = ( ev: MouseEvent< HTMLAnchorElement > ) => {
		ev.preventDefault();
		onClick();
	};

	if ( enabled ) {
		return (
			/* eslint-disable-next-line jsx-a11y/anchor-is-valid */
			<a className={ className + ' button' } href="#" onClick={ click }>
				<span className="screen-reader-text">{ title }</span>
				<span aria-hidden="true">{ button }</span>
			</a>
		);
	}

	return (
		<span className="tablenav-pages-navspan button disabled" aria-hidden="true">
			{ button }
		</span>
	);
};

export default Nav;
