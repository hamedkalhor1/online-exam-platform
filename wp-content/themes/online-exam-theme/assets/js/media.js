document.addEventListener(
    "DOMContentLoaded",
    function () {

        const filters =
            document.querySelectorAll(
                "[data-media-filter]"
            );

        const cards =
            document.querySelectorAll(
                "[data-media-type]"
            );


        if (
            !filters.length ||
            !cards.length
        ) {
            return;
        }


        filters.forEach(
            function (filter) {

                filter.addEventListener(
                    "click",
                    function () {

                        const selected =
                            filter.dataset.mediaFilter;


                        filters.forEach(
                            function (item) {

                                item.classList.remove(
                                    "active"
                                );
                            }
                        );


                        filter.classList.add(
                            "active"
                        );


                        cards.forEach(
                            function (card) {

                                const type =
                                    card.dataset.mediaType;


                                if (
                                    selected === "all" ||
                                    type === selected
                                ) {

                                    card.classList.remove(
                                        "media-hidden"
                                    );

                                } else {

                                    card.classList.add(
                                        "media-hidden"
                                    );
                                }

                            }
                        );

                    }
                );

            }
        );

    }
);