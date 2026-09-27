console.log("MAIN JS LOADED");

document.addEventListener("DOMContentLoaded", function () {

    const passwordToggle =
        document.querySelector("[data-password-toggle]");

    const passwordInput =
        document.querySelector("#password");

    if (!passwordToggle || !passwordInput) {
        return;
    }

    passwordToggle.addEventListener("click", function () {

        const isPassword =
            passwordInput.type === "password";

        passwordInput.type =
            isPassword ? "text" : "password";

        passwordToggle.setAttribute(
            "aria-label",
            isPassword
                ? "مخفی کردن رمز عبور"
                : "نمایش رمز عبور"
        );

    });

});