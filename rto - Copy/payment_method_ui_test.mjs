export default async function run(page) {
    const report = {};
    const form = page.locator("#registrationPaymentForm");

    for (const method of ["UPI", "Debit Card", "Credit Card", "Net Banking"]) {
        await form.locator(`input[name="payment_method"][value="${method}"]`).check();
        const section = method === "UPI"
            ? form.locator('[data-registration-fields="upi"]')
            : method === "Net Banking"
                ? form.locator('[data-registration-fields="bank"]')
                : form.locator('[data-registration-fields="card"]');

        report[method] = {
            visible: await section.isVisible(),
            requiredFields: await section.locator("input, select").evaluateAll((fields) =>
                fields.filter((field) => field.required).map((field) => field.name)
            )
        };
    }

    await form.locator('input[name="payment_method"][value="Net Banking"]').check();
    await form.locator("#registration_bank").selectOption("SBI");
    report.bankDetails = {
        visible: await form.locator("#registrationBankDetails").isVisible(),
        account: await form.locator("[data-bank-account]").innerText(),
        referenceRequired: await form.locator("#registration_bank_reference").evaluate((field) => field.required)
    };

    return report;
}