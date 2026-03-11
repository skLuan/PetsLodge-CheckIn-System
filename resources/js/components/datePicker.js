export default function datePicker() {
    const today = new Date();

    return {
        open: false,
        year: today.getFullYear(),
        month: today.getMonth(),
        selectedYear: null,
        selectedMonth: null,
        selectedDay: null,

        monthNames: [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ],
        dayNames: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],

        daysInMonth() {
            const count = new Date(this.year, this.month + 1, 0).getDate();
            return Array.from({ length: count }, (_, i) => i + 1);
        },

        firstDayOfWeek() {
            const day = new Date(this.year, this.month, 1).getDay();
            return Array.from({ length: day }, (_, i) => i);
        },

        prevMonth() {
            if (this.month === 0) {
                this.month = 11;
                this.year--;
            } else {
                this.month--;
            }
        },

        nextMonth() {
            const now = new Date();
            if (this.year >= now.getFullYear() && this.month >= now.getMonth()) return;

            if (this.month === 11) {
                this.month = 0;
                this.year++;
            } else {
                this.month++;
            }
        },

        selectDay(day) {
            this.selectedYear = this.year;
            this.selectedMonth = this.month;
            this.selectedDay = day;
            this.open = false;
        },

        isSelected(day) {
            return this.selectedDay === day &&
                   this.selectedMonth === this.month &&
                   this.selectedYear === this.year;
        },

        isFuture(day) {
            const date = new Date(this.year, this.month, day);
            const now = new Date();
            now.setHours(0, 0, 0, 0);
            return date > now;
        },

        formatSelected() {
            if (!this.selectedDay) return '';
            const m = String(this.selectedMonth + 1).padStart(2, '0');
            const d = String(this.selectedDay).padStart(2, '0');
            return `${this.selectedYear}-${m}-${d}`;
        },

        clearDate() {
            this.selectedYear = null;
            this.selectedMonth = null;
            this.selectedDay = null;
        }
    };
}
