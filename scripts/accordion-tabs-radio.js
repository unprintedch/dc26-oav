const ACCORDION_SELECTOR = '.wp-block-accordion.is-style-dc26-tabs-vertical';
const ITEM_SELECTOR = '.wp-block-accordion-item';
const TOGGLE_SELECTOR = '.wp-block-accordion-heading__toggle';
const TITLE_SELECTOR = '.wp-block-accordion-heading__toggle-title';

const slugify = (text) =>
    text
        .toLowerCase()
        .trim()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');

const updateActiveItems = (accordion) => {
    accordion.querySelectorAll(ITEM_SELECTOR).forEach((item) => {
        const toggle = item.querySelector(TOGGLE_SELECTOR);
        const isOpen = toggle && toggle.getAttribute('aria-expanded') === 'true';
        item.classList.toggle('is-active', !!isOpen);
    });
};

const updateMinHeight = (accordion) => {
    const headings = accordion.querySelectorAll('.wp-block-accordion-heading');
    const navHeight = Array.from(headings).reduce((total, heading) => {
        const styles = window.getComputedStyle(heading);
        const marginBottom = parseFloat(styles.marginBottom) || 0;
        return total + heading.getBoundingClientRect().height + marginBottom;
    }, 0);

    const activePanel = accordion.querySelector(
        `${ITEM_SELECTOR}.is-active .wp-block-accordion-panel`
    );
    const panelHeight = activePanel ? activePanel.getBoundingClientRect().height : 0;
    const targetHeight = Math.max(navHeight, panelHeight);

    accordion.style.minHeight = targetHeight ? `${targetHeight}px` : '';
};

const openByHash = (accordion, hash) => {
    if (!hash) {
        return;
    }
    const targetSlug = slugify(hash.replace(/^#/, ''));
    if (!targetSlug) {
        return;
    }

    const items = accordion.querySelectorAll(ITEM_SELECTOR);
    for (const item of items) {
        const title = item.querySelector(TITLE_SELECTOR);
        if (!title) {
            continue;
        }
        if (slugify(title.textContent) !== targetSlug) {
            continue;
        }
        const toggle = item.querySelector(TOGGLE_SELECTOR);
        if (toggle && toggle.getAttribute('aria-expanded') !== 'true') {
            toggle.click();
        }
        updateActiveItems(accordion);
        break;
    }
};

const initAccordion = (accordion) => {
    const toggles = accordion.querySelectorAll(TOGGLE_SELECTOR);
    if (!toggles.length) {
        return;
    }

    accordion.addEventListener('click', (event) => {
        const toggle = event.target.closest(TOGGLE_SELECTOR);
        if (!toggle || !accordion.contains(toggle)) {
            return;
        }
        window.requestAnimationFrame(() => {
            updateActiveItems(accordion);
            updateMinHeight(accordion);
        });
    });

    updateActiveItems(accordion);
    updateMinHeight(accordion);
    openByHash(accordion, window.location.hash);

    window.addEventListener('hashchange', () => {
        openByHash(accordion, window.location.hash);
        updateMinHeight(accordion);
    });

    window.addEventListener('resize', () => {
        updateMinHeight(accordion);
    });
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(ACCORDION_SELECTOR).forEach(initAccordion);
});
