document.addEventListener('DOMContentLoaded', function() {
  // For inline selection we may not have .staff-calendar initially; find container or create one when needed
  var calendarContainer = document.querySelector('.staff-calendar');
  var staffId = calendarContainer ? calendarContainer.getAttribute('data-staff-id') : null;

  if (typeof FullCalendar === 'undefined' || typeof FullCalendar.Calendar === 'undefined') {
    console.error('FullCalendar not loaded.');
    if (calendarContainer) calendarContainer.innerHTML = '<div class="fc-error">Unable to load calendar. <a href="' + window.location.href.split('?')[0] + '">Back</a></div>';
    return;
  }

  // helper to create calendar in a given element for a particular staffId
  var calendar = null;
  function createCalendar(el, staffId) {
    if (calendar) { calendar.destroy(); calendar = null; }
    calendar = new FullCalendar.Calendar(el, {
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
        if (!staffId) { successCallback([]); return; }
        var url = payndleStaffTimetable.rest_url + '?staff_id=' + encodeURIComponent(staffId) + '&start=' + start + '&end=' + end;
        showSpinner(true);
        fetch(url, { headers: { 'X-WP-Nonce': payndleStaffTimetable.nonce } }).then(function(res){ return res.json(); }).then(function(json){
          var events = Array.isArray(json) ? json : (json && Array.isArray(json.events) ? json.events : []);
          successCallback(events);
        }).catch(function(err){ console.error('Failed to load timetable events', err); failureCallback(err); }).finally(function(){ showSpinner(false); });
      }
    });
    calendar.render();
    return calendar;
  }
  

  // If a calendar container exists on initial load and has a staffId, instantiate it
  if (calendarContainer && staffId) {
    createCalendar(calendarContainer, staffId);
  }

  // Spinner controls
  function showSpinner(show) {
    var el = document.querySelector('.staff-calendar');
    if (!el) return;
    var spinner = el.querySelector('.timetable-loading');
    if (!spinner) return;
    spinner.style.display = show ? 'block' : 'none';
  }

  // Attach click handlers to staff cards for inline selection
  var staffCards = document.querySelectorAll('.staff-card[data-staff-id]');
  if (staffCards && staffCards.length) {
    // ensure an inline calendar container exists below the selector
    var selector = document.querySelector('.staff-selector');
    if (selector) {
      // Dynamic delegation: handle clicks on staff cards, including ones added later
      document.addEventListener('click', function(e) {
          var card = e.target.closest && e.target.closest('.staff-card[data-staff-id]');
          if (!card) return;
          // If user holds ctrl/meta or middle-click, allow opening in new tab
          if (e.ctrlKey || e.metaKey || e.button === 1) return;
          e.preventDefault();
          var staffId = card.getAttribute('data-staff-id');
          // create an inline calendar below the selector
          var selector = document.querySelector('.staff-selector');
          if (!selector) return;
          var existing = document.querySelector('.staff-calendar-inline');
          if (existing) existing.remove();
          var inline = document.createElement('div');
          inline.className = 'staff-calendar-inline';
          // add a close control
          var close = document.createElement('button');
          close.className = 'staff-calendar-close';
          close.textContent = 'Close';
          close.addEventListener('click', function() {
              if (inline._calendar) {
                  inline._calendar.destroy();
                  inline._calendar = null;
              }
              inline.remove();
              // update history to remove staff query
              if (window.history && window.history.pushState) {
                  var url = new URL(window.location.href);
                  url.searchParams.delete('staff_id');
                  window.history.pushState({}, '', url.toString());
              }
          });
          inline.appendChild(close);
          selector.parentNode.insertBefore(inline, selector.nextSibling);
          createCalendar(inline, staffId);
          inline.scrollIntoView({behavior: 'smooth'});

          // push state for deep linking
          if (window.history && window.history.pushState) {
              var url = new URL(window.location.href);
              url.searchParams.set('staff_id', staffId);
              window.history.pushState({staff_id: staffId}, '', url.toString());
          }
      });

      // handle browser back/forward to open/close inline calendar based on staff_id param
      window.addEventListener('popstate', function(e) {
          var params = new URL(window.location.href).searchParams;
          var sid = params.get('staff_id');
          var existing = document.querySelector('.staff-calendar-inline');
          if (sid) {
              // open if not open or different
              if (!existing || (existing && existing.getAttribute('data-staff-id') !== sid)) {
                  if (existing) existing.remove();
                  var selector = document.querySelector('.staff-selector');
                  if (!selector) return;
                  var inline = document.createElement('div');
                  inline.className = 'staff-calendar-inline';
                  inline.setAttribute('data-staff-id', sid);
                  var close = document.createElement('button');
                  close.className = 'staff-calendar-close';
                  close.textContent = 'Close';
                  close.addEventListener('click', function() {
                      if (inline._calendar) {
                          inline._calendar.destroy();
                          inline._calendar = null;
                      }
                      inline.remove();
                      var url = new URL(window.location.href);
                      url.searchParams.delete('staff_id');
                      window.history.pushState({}, '', url.toString());
                  });
                  inline.appendChild(close);
                  selector.parentNode.insertBefore(inline, selector.nextSibling);
                  createCalendar(inline, sid);
              }
          } else {
              // no staff_id -> close existing
              if (existing) existing.remove();
          }
      });

        }
      }

    });
