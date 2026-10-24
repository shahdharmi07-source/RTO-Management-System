<?php

session_start();

/* =========================================
   STEP 3 COMPLETE CHECK
========================================= */

if (!isset($_SESSION['payment_completed'])) {

    header("Location: payment.php");
    exit();

}

$message = "";
$success = false;


/* =========================================
   BOOK SLOT
========================================= */

if (isset($_POST['book_slot'])) {

    $rto_center = $_POST['rto_center'] ?? "";
    $appointment_date = $_POST['appointment_date'] ?? "";
    $appointment_time = $_POST['appointment_time'] ?? "";

    if (
        $rto_center == "" ||
        $appointment_date == "" ||
        $appointment_time == ""
    ) {

        $message = "Please select RTO centre, date and time.";

    } else {

        $dayName = date("l", strtotime($appointment_date));

        if ($dayName == "Sunday") {

            $message = "Sunday appointment is not available.";

        } else {

            $_SESSION['slot_completed'] = true;

            $_SESSION['rto_center'] = $rto_center;
            $_SESSION['appointment_date'] = $appointment_date;
            $_SESSION['appointment_time'] = $appointment_time;

            $success = true;

            $message = "Slot booked successfully!";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Slot Booking - Step 4</title>

    <link
        rel="stylesheet"
        href="slot_booking.css">

</head>

<body>

<div class="page-container">


<!-- ==================================================
     4 STEP PROCESS
================================================== -->

<div class="steps-card">

    <h1>Easy 4-Step Process</h1>

    <p>
        Complete your driving licence application in simple steps.
    </p>

    <div class="steps">


        <!-- STEP 1 -->

        <div class="step completed">

            <div class="step-circle">
                ✓
            </div>

            <div>
                <small>Step 01</small>
                <strong>Application</strong>
            </div>

        </div>


        <div class="line completed-line"></div>


        <!-- STEP 2 -->

        <div class="step completed">

            <div class="step-circle">
                ✓
            </div>

            <div>
                <small>Step 02</small>
                <strong>Documents</strong>
            </div>

        </div>


        <div class="line completed-line"></div>


        <!-- STEP 3 -->

        <div class="step completed">

            <div class="step-circle">
                ✓
            </div>

            <div>
                <small>Step 03</small>
                <strong>Payment</strong>
            </div>

        </div>


        <div class="line completed-line"></div>


        <!-- STEP 4 -->

        <div class="step active">

            <div class="step-circle">
                🗓️
            </div>

            <div>
                <small>Step 04</small>
                <strong>Slot Booking</strong>
            </div>

        </div>

    </div>

</div>



<!-- ==================================================
     HEADER
================================================== -->

<div class="page-header">

    <div>

        <h2>
            Driving Licence Slot Booking
        </h2>

        <p>
            Step 04 - Select your RTO centre and appointment slot
        </p>

    </div>

    <div class="application-icon">
        🗓️
    </div>

</div>



<!-- ==================================================
     MESSAGE
================================================== -->

<?php if ($message != "") { ?>

<div class="<?php echo $success ? 'success' : 'error'; ?>">

    <?php echo htmlspecialchars($message); ?>

</div>

<?php } ?>



<?php if (!$success) { ?>


<form method="POST" id="slotForm">


<!-- ==================================================
     RTO CENTRE
================================================== -->

<div class="form-card">

    <div class="section-title">

        <span>04</span>

        <div>

            <h2>
                Select Appointment Slot
            </h2>

            <p>
                Select your RTO centre, date and available time.
            </p>

        </div>

    </div>


    <div class="form-grid">

        <div class="form-group full">

            <label>
                Select RTO Centre *
            </label>

            <select
                name="rto_center"
                id="rto_center"
                required>

                <option value="">
                    Select RTO Centre
                </option>

                <option value="Surat RTO">
                    Surat RTO
                </option>

                <option value="Ahmedabad RTO">
                    Ahmedabad RTO
                </option>

                <option value="Vadodara RTO">
                    Vadodara RTO
                </option>

                <option value="Rajkot RTO">
                    Rajkot RTO
                </option>

            </select>

        </div>

    </div>

</div>



<!-- ==================================================
     CALENDAR
================================================== -->

<div class="form-card">

    <div class="calendar-title">

        <h2>
            📅 Select Appointment Date
        </h2>

        <p>
            Green = Available &nbsp;&nbsp;
            Yellow = Limited &nbsp;&nbsp;
            Red = Full / Unavailable
        </p>

    </div>


    <!-- CALENDAR HEADER -->

    <div class="calendar-header">

        <button
            type="button"
            id="prevMonth">

            ←

        </button>


        <h2 id="monthYear"></h2>


        <button
            type="button"
            id="nextMonth">

            →

        </button>

    </div>



    <!-- WEEK DAYS -->

    <div class="calendar-week">

        <div>Sun</div>
        <div>Mon</div>
        <div>Tue</div>
        <div>Wed</div>
        <div>Thu</div>
        <div>Fri</div>
        <div>Sat</div>

    </div>



    <!-- CALENDAR -->

    <div
        id="calendar"
        class="calendar-grid">

    </div>



    <!-- LEGEND -->

    <div class="calendar-legend">

        <span>

            <i class="available-dot"></i>

            Available

        </span>


        <span>

            <i class="limited-dot"></i>

            Limited

        </span>


        <span>

            <i class="full-dot"></i>

            Full / Unavailable

        </span>


        <span>

            <i class="sunday-dot"></i>

            Sunday

        </span>

    </div>



    <!-- SELECTED DATE -->

    <div
        id="selectedSlot"
        class="selected-slot">

        Please select an available date.

    </div>


    <input
        type="hidden"
        name="appointment_date"
        id="appointment_date"
        required>

</div>



<!-- ==================================================
     TIME
================================================== -->

<div class="form-card">

    <div class="section-title">

        <span>⏰</span>

        <div>

            <h2>
                Select Appointment Time
            </h2>

            <p>
                Select an available time slot.
            </p>

        </div>

    </div>


    <div class="form-group">

        <label>
            Appointment Time *
        </label>


        <select
            name="appointment_time"
            id="appointment_time"
            required>

            <option value="">
                Select date first
            </option>

        </select>

    </div>

</div>



<!-- ==================================================
     DECLARATION
================================================== -->

<div class="declaration">

    <input
        type="checkbox"
        name="declaration"
        required>

    <span>

        I confirm that I have selected the correct
        RTO centre, appointment date and time.

    </span>

</div>



<!-- ==================================================
     BUTTONS
================================================== -->

<div class="form-buttons">

    <a
        href="payment.php"
        class="back-btn">

        ← Back

    </a>


    <button
        type="submit"
        name="book_slot"
        class="submit-btn">

        Book Slot ✓

    </button>

</div>


</form>


<?php } else { ?>


<!-- ==================================================
     SUCCESS
================================================== -->

<div class="success-card">

    <div class="success-icon">
        ✓
    </div>


    <h2>
        Application Completed Successfully!
    </h2>


    <p>
        Your driving licence application process
        has been completed successfully.
    </p>


    <div class="booking-details">

        <p>

            <strong>Status:</strong>

            Application Completed

        </p>


        <p>

            <strong>RTO Centre:</strong>

            <?php
            echo htmlspecialchars($rto_center);
            ?>

        </p>


        <p>

            <strong>Appointment Date:</strong>

            <?php
            echo htmlspecialchars($appointment_date);
            ?>

        </p>


        <p>

            <strong>Appointment Time:</strong>

            <?php
            echo htmlspecialchars($appointment_time);
            ?>

        </p>

    </div>


    <a
        href="Dashboard.php"
        class="dashboard-btn">

        Go to Dashboard →

    </a>

</div>


<?php } ?>


</div>



<script>


/* ==================================================
   ELEMENTS
================================================== */

const calendar =
    document.getElementById("calendar");

const monthYear =
    document.getElementById("monthYear");

const appointmentDate =
    document.getElementById("appointment_date");

const selectedSlot =
    document.getElementById("selectedSlot");

const appointmentTime =
    document.getElementById("appointment_time");

const prevMonth =
    document.getElementById("prevMonth");

const nextMonth =
    document.getElementById("nextMonth");



/* ==================================================
   CURRENT DATE
================================================== */

let today = new Date();

let currentDate = new Date();



/* ==================================================
   FORMAT DATE
================================================== */

function formatDate(date) {

    let year =
        date.getFullYear();

    let month =
        String(
            date.getMonth() + 1
        ).padStart(2, "0");

    let day =
        String(
            date.getDate()
        ).padStart(2, "0");

    return year + "-" + month + "-" + day;

}



function getToday() {

    return new Date(
        today.getFullYear(),
        today.getMonth(),
        today.getDate()
    );

}



/* ==================================================
   SLOT DATA
================================================== */

/*
   અહીં specific dates ને manually
   Full / Unavailable કરી શકાય છે.

   status:

   available = Green
   limited   = Yellow
   full      = Red
*/


const slotData = {};


/* ==================================================
   AUTOMATIC DEMO DATA
================================================== */

for (let i = 1; i <= 120; i++) {


    let date =
        new Date();


    date.setDate(
        date.getDate() + i
    );


    let key =
        formatDate(date);


    /* Sunday */

    if (date.getDay() === 0) {

        continue;

    }


    /* Every 7th working date = FULL */

    if (i % 7 === 0) {

        slotData[key] = {

            status: "full",

            count: 0

        };

    }


    /* Every 5th date = LIMITED */

    else if (i % 5 === 0) {

        slotData[key] = {

            status: "limited",

            count: 2

        };

    }


    /* Other dates = AVAILABLE */

    else {

        slotData[key] = {

            status: "available",

            count: 10

        };

    }

}



/* ==================================================
   IMPORTANT:
   SPECIFIC FULL / UNAVAILABLE DATES
================================================== */

/*
   અહીં તમે તમારી પસંદની dates
   Red કરી શકો છો.

   Example:

   2026-08-25
   2026-08-28
   2026-09-02

   આ dates પર "Full" આવશે.
*/


const unavailableDates = [

    "2026-08-25",
    "2026-08-28",
    "2026-09-02",
    "2026-09-05",
    "2026-09-11",
    "2026-09-16",
    "2026-09-21",
    "2026-09-26",
    "2026-10-03",
    "2026-10-09"

];


/* Make selected dates FULL */

unavailableDates.forEach(
    function(date) {

        slotData[date] = {

            status: "full",

            count: 0

        };

    }
);



/* ==================================================
   CALENDAR
================================================== */

function generateCalendar() {


    calendar.innerHTML = "";


    let year =
        currentDate.getFullYear();

    let month =
        currentDate.getMonth();


    monthYear.innerText =

        currentDate.toLocaleString(
            "en-US",
            {
                month: "long",
                year: "numeric"
            }
        );


    let firstDay =

        new Date(
            year,
            month,
            1
        ).getDay();


    let totalDays =

        new Date(
            year,
            month + 1,
            0
        ).getDate();



    /* EMPTY BOXES */

    for (
        let i = 0;
        i < firstDay;
        i++
    ) {

        let empty =
            document.createElement("div");

        empty.className =
            "calendar-empty";

        calendar.appendChild(
            empty
        );

    }



    /* DATES */

    for (
        let day = 1;
        day <= totalDays;
        day++
    ) {


        let date =

            new Date(
                year,
                month,
                day
            );


        let key =
            formatDate(date);


        let box =
            document.createElement("div");


        box.className =
            "calendar-date";


        let number =
            document.createElement("div");


        number.className =
            "date-number";


        number.innerText =
            day;


        let status =
            document.createElement("div");


        status.className =
            "slot-status";



        /* ======================================
           SUNDAY
        ====================================== */

        if (
            date.getDay() === 0
        ) {

            box.classList.add(
                "sunday"
            );

            status.innerText =
                "Sunday";

        }


        /* ======================================
           PAST
        ====================================== */

        else if (
            date < getToday()
        ) {

            box.classList.add(
                "past"
            );

            status.innerText =
                "Unavailable";

        }


        /* ======================================
           FUTURE
        ====================================== */

        else {


            let data =
                slotData[key];


            /* NO DATA = FULL */

            if (!data) {

                box.classList.add(
                    "full"
                );

                status.innerText =
                    "Full";

            }


            /* AVAILABLE */

            else if (
                data.status ===
                "available"
            ) {

                box.classList.add(
                    "available"
                );

                status.innerText =
                    "Available";

            }


            /* LIMITED */

            else if (
                data.status ===
                "limited"
            ) {

                box.classList.add(
                    "limited"
                );

                status.innerText =
                    "Limited";

            }


            /* FULL */

            else if (
                data.status ===
                "full"
            ) {

                box.classList.add(
                    "full"
                );

                status.innerText =
                    "Full";

            }

        }



        /* TODAY */

        if (
            key ===
            formatDate(today)
        ) {

            box.classList.add(
                "today"
            );

        }



        box.appendChild(
            number
        );

        box.appendChild(
            status
        );



        /* ======================================
           CLICK
        ====================================== */

        box.addEventListener(
            "click",
            function() {


                /* SUNDAY */

                if (
                    box.classList.contains(
                        "sunday"
                    )
                ) {

                    return;

                }


                /* PAST */

                if (
                    box.classList.contains(
                        "past"
                    )
                ) {

                    return;

                }


                /* FULL */

                if (
                    box.classList.contains(
                        "full"
                    )
                ) {

                    selectedSlot.innerHTML =

                        "❌ This date is <strong>Full</strong>. Please select another date.";

                    appointmentDate.value =
                        "";

                    appointmentTime.innerHTML =

                        `<option value="">
                            Select date first
                        </option>`;

                    return;

                }



                /* REMOVE OLD */

                document
                    .querySelectorAll(
                        ".calendar-date.selected"
                    )
                    .forEach(
                        function(el) {

                            el.classList.remove(
                                "selected"
                            );

                        }
                    );



                /* SELECT */

                box.classList.add(
                    "selected"
                );


                appointmentDate.value =
                    key;


                let data =
                    slotData[key];


                let displayDate =

                    date.toLocaleDateString(
                        "en-IN",
                        {
                            day: "2-digit",
                            month: "long",
                            year: "numeric"
                        }
                    );


                selectedSlot.innerHTML =

                    "✓ Selected Date: <strong>" +
                    displayDate +
                    "</strong> &nbsp; | &nbsp; " +
                    data.count +
                    " slots available";



                /* TIME */

                appointmentTime.innerHTML =

                    `<option value="">
                        Select Time
                    </option>`;


                let times = [];


                if (
                    data.status ===
                    "available"
                ) {

                    times = [

                        "09:00 AM",
                        "10:00 AM",
                        "11:00 AM",
                        "12:00 PM",
                        "02:00 PM",
                        "03:00 PM",
                        "04:00 PM"

                    ];

                }


                else if (
                    data.status ===
                    "limited"
                ) {

                    times = [

                        "10:00 AM",
                        "02:00 PM"

                    ];

                }


                times.forEach(
                    function(time) {

                        let option =
                            document.createElement(
                                "option"
                            );

                        option.value =
                            time;

                        option.innerText =
                            time;

                        appointmentTime.appendChild(
                            option
                        );

                    }
                );

            }
        );



        calendar.appendChild(
            box
        );

    }

}



/* ==================================================
   PREVIOUS MONTH
================================================== */

prevMonth.addEventListener(
    "click",
    function() {

        currentDate.setMonth(
            currentDate.getMonth() - 1
        );

        generateCalendar();

    }
);



/* ==================================================
   NEXT MONTH
================================================== */

nextMonth.addEventListener(
    "click",
    function() {

        currentDate.setMonth(
            currentDate.getMonth() + 1
        );

        generateCalendar();

    }
);



/* ==================================================
   START CALENDAR
================================================== */

generateCalendar();

</script>


<script src="site-back-button.js?v=2" defer></script>
</body>

</html>