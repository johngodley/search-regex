// eslint-disable-next-line camelcase
export function has_capability( cap: string ): boolean {
	return SearchRegexi10n.caps.capabilities.indexOf( cap ) !== -1;
}

// eslint-disable-next-line camelcase
export function has_page_access( page: string ): boolean {
	return SearchRegexi10n.caps.pages.indexOf( page ) !== -1;
}

export const CAP_SEARCHREGEX_SEARCH = 'searchregex_cap_manage';
export const CAP_SEARCHREGEX_OPTIONS = 'searchregex_cap_options';
export const CAP_SEARCHREGEX_SUPPORT = 'searchregex_cap_support';
