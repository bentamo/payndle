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
    if (calendar) { try{ calendar.destroy(); }catch(e){} calendar = null; }
    // ensure element is visible for proper rendering
    if (el.style) el.style.display = 'block';
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
    // sometimes FullCalendar calculates sizes before layout is final
    // force a size recalculation immediately and after a short delay
    try { if (typeof calendar.updateSize === 'function') calendar.updateSize(); } catch (e) { /* ignore */ }
    setTimeout(function(){ try { if (calendar && typeof calendar.updateSize === 'function') calendar.updateSize(); } catch(e){} }, 120);
    return calendar;
  }
  

  // If a calendar container exists on initial load and has a staffId, instantiate it
  if (calendarContainer && staffId) {
    var _initial = createCalendar(calendarContainer, staffId);
    // ensure layout is correct after page paint
    setTimeout(function(){ try{ if (_initial && typeof _initial.updateSize === 'function') _initial.updateSize(); }catch(e){} }, 200);
  }

  // Spinner controls
  function showSpinner(show) {
    var el = document.querySelector('.staff-calendar');
    if (!el) return;
    var spinner = el.querySelector('.timetable-loading');
    if (!spinner) return;
    spinner.style.display = show ? 'block' : 'none';
  }

  // Attach click handlers to staff cards to load the timetable into the right column
  var staffCards = document.querySelectorAll('.staff-card[data-staff-id]');
  if (staffCards && staffCards.length) {
    // Dynamic delegation: handle clicks on staff cards, including ones added later
    document.addEventListener('click', function(e) {
        var card = e.target.closest && e.target.closest('.staff-card[data-staff-id]');
        if (!card) return;
        // If user holds ctrl/meta or middle-click, allow opening in new tab
        if (e.ctrlKey || e.metaKey || e.button === 1) return;
        // Prevent full navigation
        e.preventDefault();
        var staffId = card.getAttribute('data-staff-id');

        // mark selection
        document.querySelectorAll('.staff-card.selected').forEach(function(c){ c.classList.remove('selected'); });
        card.classList.add('selected');

        // Prepare right column
        var calendarColumn = document.querySelector('.staff-calendar-column');
        if (!calendarColumn) return;
        var placeholder = calendarColumn.querySelector('.staff-calendar-placeholder');
        var calEl = calendarColumn.querySelector('.staff-calendar');
        if (placeholder) placeholder.style.display = 'none';
        if (calEl) {
          calEl.style.display = 'block';
          calEl.setAttribute('data-staff-id', staffId);
        }
        var closeBtn = calendarColumn.querySelector('.staff-calendar-close');
        if (closeBtn) closeBtn.style.display = 'inline-block';

        // create or re-create calendar in right column
        if (calEl) {
          if (calEl._calendar) { try{ calEl._calendar.destroy(); }catch(e){} calEl._calendar = null; }
          calEl._calendar = createCalendar(calEl, staffId);
        }

        // push state for deep linking
        if (window.history && window.history.pushState) {
          var url = new URL(window.location.href);
          url.searchParams.set('staff_id', staffId);
          window.history.pushState({staff_id: staffId}, '', url.toString());
        }
    });

    // close button behavior and Esc key handling for right column
    (function(){
      var closeBtn = document.querySelector('.staff-calendar-close');
      if (closeBtn) {
        closeBtn.addEventListener('click', function(){
          var calendarColumn = document.querySelector('.staff-calendar-column');
          if (!calendarColumn) return;
          var calEl = calendarColumn.querySelector('.staff-calendar');
          var placeholder = calendarColumn.querySelector('.staff-calendar-placeholder');
          if (calEl && calEl._calendar) { try{ calEl._calendar.destroy(); }catch(e){} calEl._calendar = null; }
          if (calEl) { calEl.style.display = 'none'; calEl.removeAttribute('data-staff-id'); }
          if (placeholder) { placeholder.style.display = 'block'; }
          closeBtn.style.display = 'none';
          // remove staff_id param from url
          if (window.history && window.history.pushState) {
            var url = new URL(window.location.href);
            url.searchParams.delete('staff_id');
            window.history.pushState({}, '', url.toString());
          }
          // unselect any card
          document.querySelectorAll('.staff-card.selected').forEach(function(c){ c.classList.remove('selected'); });
        });
      }
      document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' || e.key === 'Esc') {
          var closeBtn = document.querySelector('.staff-calendar-close');
          if (closeBtn && closeBtn.style.display !== 'none') closeBtn.click();
        }
      });
    })();

    // Auto-open calendar if ?staff_id=... on initial load
    (function(){
      var params = new URL(window.location.href).searchParams;
      var sid = params.get('staff_id');
      if (sid) {
        // try to find the matching card and simulate a click
        var card = document.querySelector('.staff-card[data-staff-id="' + sid + '"]');
        var calendarColumn = document.querySelector('.staff-calendar-column');
        var placeholder = calendarColumn ? calendarColumn.querySelector('.staff-calendar-placeholder') : null;
        var calEl = calendarColumn ? calendarColumn.querySelector('.staff-calendar') : null;
        if (card) { card.click(); }
        else if (calEl && calendarColumn) {
          if (placeholder) placeholder.style.display = 'none';
          calEl.style.display = 'block';
          calEl.setAttribute('data-staff-id', sid);
          calEl._calendar = createCalendar(calEl, sid);
          var close = document.querySelector('.staff-calendar-close'); if (close) close.style.display = 'inline-block';
        }
      }
    })();

  }
    });
