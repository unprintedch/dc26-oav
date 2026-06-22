document.addEventListener("click", (e) => {
  const link = e.target.closest('a[href="#"]');
  if (link && link.closest(".wp-block-navigation")) {
    e.preventDefault();
    e.stopImmediatePropagation();
    link.blur();
  }
}, true);
