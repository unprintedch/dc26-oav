document.querySelectorAll('.dc26-back-link').forEach(link => {
    link.addEventListener('click', e => {
        if (history.length > 1) {
            e.preventDefault();
            history.back();
        }
    });
});
