const EDITOR_BODY_CLASSES = [
  'block-editor-page',
  'block-editor-iframe__body'
];

const isEditorContext = () =>
  EDITOR_BODY_CLASSES.some((className) =>
    document.body.classList.contains(className)
  );

const setActiveSide = (block, side, store) => {
  const buttons = block.querySelectorAll('.dc26-toggle-panel__button');
  const panels = block.querySelectorAll('.dc26-toggle-panel__panel');

  buttons.forEach((button) => {
    const isActive = button.dataset.side === side;
    button.classList.toggle('is-active', isActive);
    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
  });

  panels.forEach((panel) => {
    panel.classList.toggle('is-active', panel.dataset.side === side);
  });

  const panelsWrapper = block.querySelector('.dc26-toggle-panel__panels');
  if (panelsWrapper) {
    const activePanel = block.querySelector('.dc26-toggle-panel__panel.is-active');
    if (activePanel) {
      panelsWrapper.style.minHeight = `${activePanel.offsetHeight}px`;
    }
  }

  if (store) {
    store(side);
  }
};

const initTogglePanel = (block) => {
  if (!block) {
    return;
  }

  const defaultSide = block.dataset.defaultSide === 'right' ? 'right' : 'left';
  const rememberChoice = block.dataset.rememberChoice === '1';
  const storageKey = block.dataset.storageKey || '';

  const getStoredSide = () => {
    if (!rememberChoice || !storageKey) {
      return null;
    }
    try {
      return window.localStorage.getItem(storageKey);
    } catch (error) {
      return null;
    }
  };

  const storeSide = (side) => {
    if (!rememberChoice || !storageKey) {
      return;
    }
    try {
      window.localStorage.setItem(storageKey, side);
    } catch (error) {
      // noop
    }
  };

  const initialSide = getStoredSide() || defaultSide;
  setActiveSide(block, initialSide, null);

  const buttons = block.querySelectorAll('.dc26-toggle-panel__button');
  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      const side = button.dataset.side === 'right' ? 'right' : 'left';
      setActiveSide(block, side, storeSide);
    });
  });
};

const initAllTogglePanels = () => {
  if (isEditorContext()) {
    return;
  }
  document
    .querySelectorAll('.dc26-toggle-panel')
    .forEach((block) => initTogglePanel(block));
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAllTogglePanels);
} else {
  initAllTogglePanels();
}
