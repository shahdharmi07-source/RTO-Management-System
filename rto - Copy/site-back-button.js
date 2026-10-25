document.addEventListener("DOMContentLoaded", () => {
    if (window.top !== window.self) {
        return;
    }

    const currentPage = window.location.pathname.split("/").pop().toLowerCase();

    if (currentPage === "home.php" || document.getElementById("site-back-button")) {
        return;
    }

    const hasExistingBackControl = Array.from(document.querySelectorAll("a, button"))
        .some((control) => {
            const label = `${control.textContent || ""} ${control.getAttribute("aria-label") || ""} ${control.className || ""}`;
            return control.getClientRects().length > 0 && /\b(back|cancel|previous)\b/i.test(label);
        });
    if (hasExistingBackControl) {
        return;
    }

    const stylesheet = document.createElement("link");
    stylesheet.rel = "stylesheet";
    stylesheet.href = "site-back-button.css?v=3";
    document.head.appendChild(stylesheet);

    const button = document.createElement("button");
    button.id = "site-back-button";
    button.type = "button";
    button.className = "site-back-button";
    button.setAttribute("aria-label", "Go to the home page");
    button.textContent = "Back to Home";
    button.addEventListener("click", () => window.location.assign("home.php"));
    document.body.appendChild(button);
});
