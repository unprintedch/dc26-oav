document.addEventListener('click', function (e) {
    const btn = e.target.closest('.dc26-copy-btn');
    if (!btn) return;

    const email = btn.dataset.email;
    if (!email) return;

    const confirm = () => {
        btn.classList.add('is-copied');
        setTimeout(() => btn.classList.remove('is-copied'), 2000);
    };

    if (navigator.clipboard) {
        navigator.clipboard.writeText(email).then(confirm);
    } else {
        const ta = document.createElement('textarea');
        ta.value = email;
        ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); confirm(); } catch (_) {}
        document.body.removeChild(ta);
    }
});
