document.addEventListener(
    "DOMContentLoaded",
    function () {

        const button =
            document.querySelector(
                "#publicMenuButton"
            );

        const menu =
            document.querySelector(
                "#publicMobileMenu"
            );


        if (!button || !menu) {
            return;
        }


        button.addEventListener(
            "click",
            function () {

                const isOpen =
                    menu.classList.toggle(
                        "open"
                    );


                button.setAttribute(
                    "aria-expanded",
                    isOpen
                        ? "true"
                        : "false"
                );
            }
        );

    }
);