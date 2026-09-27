document.addEventListener("DOMContentLoaded", function () {

    const questions = Array.from(
        document.querySelectorAll(".question-card")
    );

    if (!questions.length) {
        return;
    }

    const totalQuestions = questions.length;

    let currentQuestion = 1;


    const nextButton =
        document.querySelector("#nextQuestion");

    const questionCounter =
        document.querySelector("#questionCounter");

    const progressBar =
        document.querySelector("#examProgressBar");

    const gridButtons = Array.from(
        document.querySelectorAll(
            "[data-go-question]"
        )
    );

    const answeredCount =
        document.querySelector("#answeredCount");

    const unansweredCount =
        document.querySelector("#unansweredCount");

    const modalAnswered =
        document.querySelector("#modalAnswered");

    const modalUnanswered =
        document.querySelector("#modalUnanswered");

    const saveMessage =
        document.querySelector("#saveMessage");

    const saveContainer =
        document.querySelector(
            ".auto-save-message"
        );


    /*
    |--------------------------------------------------------------------------
    | Persian Number
    |--------------------------------------------------------------------------
    */

    function toPersianNumber(value) {

        const persianDigits =
            "۰۱۲۳۴۵۶۷۸۹";

        return String(value).replace(
            /\d/g,
            function (digit) {
                return persianDigits[digit];
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Check Answer
    |--------------------------------------------------------------------------
    */

    function isAnswered(question) {

        const type =
            question.dataset.type;


        if (type === "choice") {

            return Boolean(
                question.querySelector(
                    'input[type="radio"]:checked'
                )
            );
        }


        if (type === "textarea") {

            const textarea =
                question.querySelector(
                    "textarea"
                );

            return Boolean(
                textarea &&
                textarea.value.trim().length > 0
            );
        }


        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Current Question
    |--------------------------------------------------------------------------
    */

    function getCurrentQuestion() {

        return questions.find(
            function (question) {

                return (
                    Number(
                        question.dataset.question
                    ) === currentQuestion
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Next Button
    |--------------------------------------------------------------------------
    */

    function updateNextButton() {

        if (!nextButton) {
            return;
        }

        const current =
            getCurrentQuestion();


        /*
        | آخرین سوال
        */

        if (
            currentQuestion ===
            totalQuestions
        ) {

            nextButton.disabled = true;

            nextButton.innerHTML =
                "آخرین سوال";

            return;
        }


        /*
        | تا زمانی که پاسخ داده نشده
        | رفتن به سوال بعدی ممنوع است
        */

        nextButton.disabled =
            !isAnswered(current);


        nextButton.innerHTML =
            'سوال بعدی <span>←</span>';
    }


    /*
    |--------------------------------------------------------------------------
    | Update Answer Counts
    |--------------------------------------------------------------------------
    */

    function updateAnswerCounts() {

        let answered = 0;


        questions.forEach(
            function (question, index) {

                const button =
                    gridButtons[index];


                if (isAnswered(question)) {

                    answered++;


                    if (button) {

                        button.classList.add(
                            "answered"
                        );
                    }

                } else {

                    if (button) {

                        button.classList.remove(
                            "answered"
                        );
                    }
                }
            }
        );


        const unanswered =
            totalQuestions - answered;


        if (answeredCount) {

            answeredCount.textContent =
                toPersianNumber(
                    answered
                );
        }


        if (unansweredCount) {

            unansweredCount.textContent =
                toPersianNumber(
                    unanswered
                );
        }


        if (modalAnswered) {

            modalAnswered.textContent =
                toPersianNumber(
                    answered
                );
        }


        if (modalUnanswered) {

            modalUnanswered.textContent =
                toPersianNumber(
                    unanswered
                );
        }


        updateNextButton();
    }


    /*
    |--------------------------------------------------------------------------
    | Show Question
    |--------------------------------------------------------------------------
    */

    function showQuestion(number) {

        /*
        |--------------------------------------------------------------------------
        | Security Rule On Frontend
        |--------------------------------------------------------------------------
        |
        | فقط اجازه حرکت رو به جلو و ترتیبی داریم.
        |
        */

        if (
            number < currentQuestion
        ) {
            return;
        }


        if (
            number >
            currentQuestion + 1
        ) {
            return;
        }


        currentQuestion =
            number;


        questions.forEach(
            function (question) {

                question.classList.toggle(
                    "active",
                    Number(
                        question.dataset.question
                    ) === number
                );
            }
        );


        gridButtons.forEach(
            function (button) {

                button.classList.toggle(
                    "current",
                    Number(
                        button.dataset.goQuestion
                    ) === number
                );

                /*
                |--------------------------------------------------------------------------
                | Question Grid Is Display Only
                |--------------------------------------------------------------------------
                */

                button.disabled = true;

            }
        );


        if (questionCounter) {

            questionCounter.textContent =
                "سوال " +
                toPersianNumber(number) +
                " از " +
                toPersianNumber(
                    totalQuestions
                );
        }


        if (progressBar) {

            const progress =
                (
                    number /
                    totalQuestions
                ) * 100;

            progressBar.style.width =
                progress + "%";
        }


        updateNextButton();


        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Next Question
    |--------------------------------------------------------------------------
    */

    if (nextButton) {

        nextButton.addEventListener(
            "click",
            function () {

                const current =
                    getCurrentQuestion();


                /*
                |--------------------------------------------------------------------------
                | Answer Is Required
                |--------------------------------------------------------------------------
                */

                if (
                    !isAnswered(current)
                ) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Sequential Navigation Only
                |--------------------------------------------------------------------------
                */

                if (
                    currentQuestion <
                    totalQuestions
                ) {

                    showQuestion(
                        currentQuestion + 1
                    );
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Question Grid
    |--------------------------------------------------------------------------
    |
    | فقط برای نمایش وضعیت سوالات است.
    | کاربر اجازه کلیک و جابه‌جایی ندارد.
    |
    */

    gridButtons.forEach(
        function (button) {

            button.disabled = true;

            button.setAttribute(
                "aria-disabled",
                "true"
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Fake Auto Save
    |--------------------------------------------------------------------------
    */

    function showSavedMessage() {

        if (
            !saveMessage ||
            !saveContainer
        ) {
            return;
        }


        saveMessage.textContent =
            "پاسخ ذخیره شد";


        saveContainer.classList.add(
            "saved"
        );


        clearTimeout(
            showSavedMessage.timeout
        );


        showSavedMessage.timeout =
            setTimeout(
                function () {

                    saveMessage.textContent =
                        "پاسخ‌ها به‌صورت خودکار ذخیره می‌شوند";

                    saveContainer.classList.remove(
                        "saved"
                    );

                },
                1800
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Choice Questions
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll(
        '.question-card input[type="radio"]'
    ).forEach(
        function (input) {

            input.addEventListener(
                "change",
                function () {

                    updateAnswerCounts();

                    showSavedMessage();
                }
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Essay Questions
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll(
        ".essay-answer textarea"
    ).forEach(
        function (textarea) {

            const essay =
                textarea.closest(
                    ".essay-answer"
                );

            const counter =
                essay
                    ? essay.querySelector(
                        ".character-count span"
                    )
                    : null;


            textarea.addEventListener(
                "input",
                function () {

                    if (counter) {

                        counter.textContent =
                            toPersianNumber(
                                textarea.value.length
                            );
                    }


                    updateAnswerCounts();

                    showSavedMessage();
                }
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Submit Modal
    |--------------------------------------------------------------------------
    */

    const modal =
        document.querySelector(
            "#submitExamModal"
        );

    const openModal =
        document.querySelector(
            "#openSubmitModal"
        );

    const closeModal =
        document.querySelector(
            "#closeSubmitModal"
        );

    const cancelSubmit =
        document.querySelector(
            "#cancelSubmit"
        );


    function showModal() {

        if (!modal) {
            return;
        }


        updateAnswerCounts();


        modal.classList.add(
            "open"
        );


        modal.setAttribute(
            "aria-hidden",
            "false"
        );


        document.body.classList.add(
            "exam-modal-open"
        );
    }


    function hideModal() {

        if (!modal) {
            return;
        }


        modal.classList.remove(
            "open"
        );


        modal.setAttribute(
            "aria-hidden",
            "true"
        );


        document.body.classList.remove(
            "exam-modal-open"
        );
    }


    if (openModal) {

        openModal.addEventListener(
            "click",
            showModal
        );
    }


    if (closeModal) {

        closeModal.addEventListener(
            "click",
            hideModal
        );
    }


    if (cancelSubmit) {

        cancelSubmit.addEventListener(
            "click",
            hideModal
        );
    }


    if (modal) {

        const overlay =
            modal.querySelector(
                ".exam-modal-overlay"
            );


        if (overlay) {

            overlay.addEventListener(
                "click",
                hideModal
            );
        }
    }


    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                modal &&
                modal.classList.contains(
                    "open"
                )
            ) {

                hideModal();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Demo Timer
    |--------------------------------------------------------------------------
    */

    let remainingSeconds =
        45 * 60;


    const timerElement =
        document.querySelector(
            "#examTimer"
        );

    const timerBox =
        document.querySelector(
            ".exam-timer"
        );


    function updateTimer() {

        if (!timerElement) {
            return;
        }


        const minutes =
            Math.floor(
                remainingSeconds / 60
            );

        const seconds =
            remainingSeconds % 60;


        const display =
            String(minutes)
                .padStart(2, "0") +
            ":" +
            String(seconds)
                .padStart(2, "0");


        timerElement.textContent =
            toPersianNumber(
                display
            );


        if (
            remainingSeconds <= 300 &&
            timerBox
        ) {

            timerBox.classList.add(
                "timer-warning"
            );
        }


        if (
            remainingSeconds > 0
        ) {

            remainingSeconds--;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Initialize
    |--------------------------------------------------------------------------
    */

    updateAnswerCounts();

    showQuestion(1);

    updateTimer();

    setInterval(
        updateTimer,
        1000
    );

});