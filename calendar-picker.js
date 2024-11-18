
class CalendarPicker {
  constructor() {
   this.days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
this.months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

  }
render () {
    const date = new Date();
    const month = date.getMonth();
    const year = date.getFullYear();
    const firstDay = new Date(year, month, 1);
    const startingDay = firstDay.getDay();
    const monthLength = this.months[month];
    const endDate = new Date(year, month + 1, 0);
}

}
function calendar(){
    const calendar = document.createElement('div');
    calendar.classList.add('calendar');
    calendar.appendChild(this.renderHeader());
    calendar.appendChild(this.renderBody());
    return calendar;
}
return calendar;

function renderHeader(){
    const header = document.createElement('div');
    header.classList.add('header');
    header.appendChild(this.renderPreviousButton());
    header.appendChild(this.renderMonth());
    header.appendChild(this.renderNextButton());
    return header;
}

return renderHeader;