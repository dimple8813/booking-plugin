document.addEventListener("DOMContentLoaded", function () {
    const calendarDays = document.getElementById("calendar-days");
    const monthYear = document.getElementById("month-year");

    const today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();

    // Ensure variables exist
    window.bookedDates = window.bookedDates || [];
    window.notBookedDates = window.notBookedDates || [];

    function renderCalendar(month, year) {
        calendarDays.innerHTML = "";

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        monthYear.textContent = `${getMonthName(month)} ${year}`;

        // Add blank spaces for alignment
        for (let i = 0; i < firstDay; i++) {
            const blank = document.createElement("div");
            blank.classList.add("blank");
            calendarDays.appendChild(blank);
        }

        // Loop through days in month
        for (let day = 1; day <= daysInMonth; day++) {
            const cell = document.createElement("div");
            const dateStr = formatDate(year, month + 1, day); // yyyy-mm-dd

            cell.textContent = day;
            cell.classList.add("day");

            if (bookedDates.includes(dateStr)) {
                cell.classList.add("booked");
            } else if (notBookedDates.includes(dateStr)) {
                cell.classList.add("not-booked");
            }

            calendarDays.appendChild(cell);
        }
    }

    function getMonthName(monthIndex) {
        const months = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];
        return months[monthIndex];
    }

    function formatDate(year, month, day) {
        return (
            year +
            "-" +
            String(month).padStart(2, "0") +
            "-" +
            String(day).padStart(2, "0")
        );
    }

    // Navigation buttons
    window.changeMonth = function (delta) {
        currentMonth += delta;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear -= 1;
        } else if (currentMonth > 11) {
            currentMonth = 0;
            currentYear += 1;
        }
        renderCalendar(currentMonth, currentYear);
    };

    // Placeholder functions for future use
    window.showBookedDates = function () {

    const target = document.getElementById('tsck-submissions-table');
    if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

        //alert("Booked Dates:\n" + bookedDates.join("\n"));
    };

    window.updateCalendar = function () {
        renderCalendar(currentMonth, currentYear);
    };

    renderCalendar(currentMonth, currentYear);
});
