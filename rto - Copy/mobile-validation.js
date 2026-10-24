document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('input[name="mobile"], input[name="mobile_no"], input[name="Mobile"]').forEach((input) => {
        input.setAttribute("inputmode", "numeric");
        input.maxLength = 10;

        input.addEventListener("input", () => {
            const digitsOnly = input.value.replace(/[^0-9]/g, "").slice(0, 10);
            if (input.value !== digitsOnly) {
                input.value = digitsOnly;
            }
        });
    });
});
