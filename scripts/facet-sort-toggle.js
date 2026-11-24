const SORT_FACET_SELECTOR = '.facetwp-facet-sort_firm';
const TOGGLE_CLASS = 'dc26-sort-toggle';
const BUTTON_CLASS = 'dc26-sort-toggle__button';
const ACTIVE_CLASS = 'is-active';
const READY_CLASS = 'has-sort-toggle';

const buildToggle = (facet, select) => {
    const toggle = document.createElement('div');
    toggle.className = TOGGLE_CLASS;
    toggle.setAttribute(
        'aria-label',
        select.getAttribute('aria-label') || 'Trier par'
    );
    toggle.setAttribute('role', 'group');

    const options = Array.from(select.options).filter((option) => option.value);
    options.forEach((option) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = BUTTON_CLASS;
        button.dataset.value = option.value;
        button.textContent = option.text;

        button.addEventListener('click', () => {
            if (select.value === option.value) {
                return;
            }

            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });

        toggle.appendChild(button);
    });

    facet.appendChild(toggle);
    facet.classList.add(READY_CLASS);
    return toggle;
};

const syncToggleState = (toggle, select) => {
    const value = select.value;
    toggle.querySelectorAll(`.${BUTTON_CLASS}`).forEach((button) => {
        const isActive = button.dataset.value === value;
        button.classList.toggle(ACTIVE_CLASS, isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
};

const initFacetSortToggle = () => {
    document.querySelectorAll(SORT_FACET_SELECTOR).forEach((facet) => {
        const select = facet.querySelector('select');
        if (!select) {
            return;
        }

        let toggle = facet.querySelector(`.${TOGGLE_CLASS}`);
        if (!toggle) {
            toggle = buildToggle(facet, select);
        }

        facet.classList.add(READY_CLASS);
        syncToggleState(toggle, select);
    });
};

document.addEventListener('DOMContentLoaded', initFacetSortToggle);
document.addEventListener('facetwp-loaded', initFacetSortToggle);
