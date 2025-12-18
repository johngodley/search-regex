interface MessageState {
	errors: unknown[];
	notices: string[];
	inProgress: number;
	saving: unknown[];
}

export function getInitialMessage(): MessageState {
	return {
		errors: [],
		notices: [],
		inProgress: 0,
		saving: [],
	};
}
