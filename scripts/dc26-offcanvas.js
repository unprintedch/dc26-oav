// Load offcanvas scripts

document.addEventListener("DOMContentLoaded", function () {
    var burgerIcon = document.getElementById("burger-icon");
    var offCanvas = document.getElementById("offcanvas");
    var overlay = document.getElementById("overlay");
    var body = document.body;
    var menuContainer = document.getElementById("menu-container");

    // Check if all required elements exist before proceeding
    if (!burgerIcon || !offCanvas || !overlay || !menuContainer) {
        console.log("Offcanvas elements not found, skipping offcanvas initialization");
        return;
    }

    function toggleOffCanvas() {
        // Toggle classes for off-canvas and overlay
        menuContainer.classList.toggle("overflow-visible");
        offCanvas.classList.toggle("-right-[500px]");
        offCanvas.classList.toggle("right-0");
        overlay.classList.toggle("hidden");
        body.classList.toggle("overflow-hidden");

        // Toggle the aria-expanded state
        var expanded = burgerIcon.getAttribute("aria-expanded") === "true";
        burgerIcon.setAttribute("aria-expanded", !expanded);
        burgerIcon.classList.toggle("close-mode");
    }

    // Add click event listener to burger icon
    burgerIcon.addEventListener("click", toggleOffCanvas);

    // Add click event listener to overlay
    overlay.addEventListener("click", toggleOffCanvas);
});

// Close offcanvas pressing Escape
document.addEventListener("keyup", function (event) {
    if (event.code === "Escape") {
        var offcanvasMenu = document.getElementById("offcanvas");
        var overlayOffcanvas = document.getElementById("overlay");
        var body = document.body;
        
        // Check if elements exist before accessing them
        if (offcanvasMenu && overlayOffcanvas && offcanvasMenu.classList.contains("open")) {
            offcanvasMenu.classList.remove("open");
            overlayOffcanvas.classList.remove("open");
            body.classList.remove("overflow-hidden");
        }
    }
});

