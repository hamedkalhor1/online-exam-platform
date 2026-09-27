document.addEventListener(
    "DOMContentLoaded",
    function () {

        const tabs =
            document.querySelectorAll(
                "[data-resource-tab]"
            );

        const panels =
            document.querySelectorAll(
                "[data-resource-panel]"
            );


        if (
            !tabs.length ||
            !panels.length
        ) {
            return;
        }


        tabs.forEach(
            function (tab) {

                tab.addEventListener(
                    "click",
                    function () {

                        const target =
                            tab.dataset.resourceTab;


                        tabs.forEach(
                            function (item) {

                                item.classList.remove(
                                    "active"
                                );
                            }
                        );


                        panels.forEach(
                            function (panel) {

                                panel.classList.remove(
                                    "active"
                                );
                            }
                        );


                        tab.classList.add(
                            "active"
                        );


                        const activePanel =
                            document.querySelector(
                                '[data-resource-panel="' +
                                target +
                                '"]'
                            );


                        if (activePanel) {

                            activePanel.classList.add(
                                "active"
                            );
                        }

                    }
                );

            }
        );

    }
);