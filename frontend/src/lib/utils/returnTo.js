/**
 * Lee `?returnTo=` de la URL actual: la página a la que volver después de guardar.
 * Solo acepta rutas internas ("/algo"), para que un link no pueda mandarte a otro sitio.
 */
export function getReturnTo(fallback) {
	if (typeof window === 'undefined') return fallback;
	const value = new URLSearchParams(window.location.search).get('returnTo');
	const isInternal =
		value && value.startsWith('/') && !value.startsWith('//') && !value.startsWith('/\\');
	return isInternal ? value : fallback;
}
