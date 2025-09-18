document.addEventListener('DOMContentLoaded', function() {
  var el = document.querySelector('.staff-calendar');
  if (!el) return;

  var staffId = el.getAttribute('data-staff-id');

  if (typeof FullCalendar === 'undefined' || typeof FullCalendar.Calendar === 'undefined') {
    console.error('FullCalendar not loaded.');
    el.innerHTML = '<div class="fc-error">Unable to load calendar. <a href="' + window.location.href.split('?')[0] + '">Back</a></div>';
    return;
  }

  // Create FullCalendar instance
  var calendar = new FullCalendar.Calendar(el, {
    initialView: 'timeGridWeek',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'timeGridWeek,dayGridWeek'
    },
    slotMinTime: '09:00:00',
    slotMaxTime: '19:00:00',
    nowIndicator: true,
    allDaySlot: false,
    events: function(fetchInfo, successCallback, failureCallback) {
      var start = fetchInfo.startStr.split('T')[0];
      var end = fetchInfo.endStr.split('T')[0];
        if (!staffId) {
          console.warn('staff-timetable: missing staffId on calendar element');
          successCallback([]);
          return;
        }

        var url = payndleStaffTimetable.rest_url + '?staff_id=' + encodeURIComponent(staffId) + '&start=' + start + '&end=' + end;

        fetch(url, { headers: { 'X-WP-Nonce': payndleStaffTimetable.nonce } }).then(function(res){
          return res.json();
        }).then(function(json){
          var events = Array.isArray(json) ? json : (json && Array.isArray(json.events) ? json.events : []);
          successCallback(events);
        }).catch(function(err){
          console.error('Failed to load timetable events', err);
          failureCallback(err);
        });
    }
  });

  calendar.render();

  // cleaned up: debug fetch removed

  // Attach 'Previous' control to go back to staff list (if present)
  var prevLink = document.querySelector('.timetable-prev');
  if (prevLink) {
    prevLink.addEventListener('click', function(e){
      e.preventDefault();
      window.location = window.location.href.split('?')[0];
    });
  }
});
