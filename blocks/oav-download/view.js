function tm21InitOavDownloadBlock(rootElement) {
    if (!rootElement || rootElement.dataset.oavDownloadReady === '1') {
        return;
    }

    var checkboxes = rootElement.querySelectorAll('.oav-engage-checkbox');
    var buttons = rootElement.querySelectorAll('.oav-btn');

    if (!checkboxes.length || !buttons.length) {
        return;
    }

    var areAllChecked = function () {
        return Array.prototype.every.call(checkboxes, function (checkbox) {
            return checkbox.checked;
        });
    };

    var updateButtons = function (isEnabled) {
        buttons.forEach(function (button) {
            button.classList.toggle('active', isEnabled);
            button.setAttribute('aria-disabled', String(!isEnabled));

            if (isEnabled) {
                button.removeAttribute('tabindex');
            } else {
                button.setAttribute('tabindex', '-1');
            }
        });
    };

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            updateButtons(areAllChecked());
        });
    });

    rootElement.addEventListener('click', function (event) {
        var target = event.target.closest('.oav-btn');
        if (target && target.getAttribute('aria-disabled') === 'true') {
            event.preventDefault();
        }
    });

    updateButtons(areAllChecked());
    rootElement.dataset.oavDownloadReady = '1';
}

function tm21InitAllOavDownloadBlocks() {
    document.querySelectorAll('.oav-download-block').forEach(function (rootElement) {
        tm21InitOavDownloadBlock(rootElement);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', tm21InitAllOavDownloadBlocks);
} else {
    tm21InitAllOavDownloadBlocks();
}

if (window.acf && typeof window.acf.addAction === 'function') {
    window.acf.addAction('render_block_preview/type=dc26/oav-download', function ($block) {
        if ($block && $block[0]) {
            tm21InitOavDownloadBlock($block[0].querySelector('.oav-download-block'));
        }
    });
}
