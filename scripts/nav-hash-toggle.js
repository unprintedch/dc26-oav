/**
 * Navigation items with href="#" act as submenu toggles:
 * click opens/closes the submenu instead of navigating.
 */
document.addEventListener("click", (e) => {
  const link = e.target.closest(
    ".wp-block-navigation-item.has-child > .wp-block-navigation-item__content"
  );
  if (!link) return;

  const href = link.getAttribute("href");
  if (href !== "#") return;

  e.preventDefault();
  const item = link.closest(".has-child");
  item.classList.toggle("is-menu-open");
});

document.addEventListener("click", (e) => {
  if (e.target.closest(".has-child.is-menu-open")) return;
  document
    .querySelectorAll(".has-child.is-menu-open")
    .forEach((el) => el.classList.remove("is-menu-open"));
});
