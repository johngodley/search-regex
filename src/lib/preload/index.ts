export default function getPreload< T >( name: string, defaultValue: T ): T {
	if (
		typeof SearchRegexi10n !== 'undefined' &&
		SearchRegexi10n.preload &&
		name in SearchRegexi10n.preload &&
		SearchRegexi10n.preload[ name ] !== undefined
	) {
		return SearchRegexi10n.preload[ name ] as T;
	}

	return defaultValue;
}
