const SCROLL_THRESHOLD = 64;
const HEADER_SELECTOR = 'header.wp-block-group, .wp-block-group';
const STICKY_WRAPPER_SELECTOR = 'header.wp-block-template-part.is-style-sticky-header';

function initHeaderSticky() {
	const stickyWrapper = document.querySelector(STICKY_WRAPPER_SELECTOR);
	const innerHeader = stickyWrapper
		? stickyWrapper.querySelector(HEADER_SELECTOR)
		: null;

	if (!stickyWrapper) {
		return;
	}

	const applyHeaderHeight = () => {
		const heightTarget = innerHeader || stickyWrapper;
		const rect = heightTarget.getBoundingClientRect();
		document.documentElement.style.setProperty(
			'--site-header-height',
			`${Math.ceil(rect.height)}px`
		);
	};

	let lastIsScrolled = null;

	const updateState = () => {
		const isScrolled = window.scrollY >= SCROLL_THRESHOLD;

		if (isScrolled === lastIsScrolled) {
			return;
		}

		lastIsScrolled = isScrolled;
		stickyWrapper.classList.toggle('is-scrolled', isScrolled);
		stickyWrapper.classList.toggle('is-top', !isScrolled);
	};

	applyHeaderHeight();
	updateState();

	window.addEventListener('scroll', updateState, { passive: true });
	window.addEventListener('load', updateState);
	window.addEventListener('resize', applyHeaderHeight);
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initHeaderSticky);
} else {
	initHeaderSticky();
}

export default initHeaderSticky;
