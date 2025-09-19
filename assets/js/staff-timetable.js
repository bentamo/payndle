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
        // In-block mode: insert an expander panel after the clicked card and mount calendar there
        // Remove any existing expander
        document.querySelectorAll('.staff-card-expander').forEach(function(el){ if (el._calendar) { el._calendar.destroy(); } el.remove(); });
        // unselect other cards
        document.querySelectorAll('.staff-card.selected').forEach(function(c){ c.classList.remove('selected'); });
        card.classList.add('selected');

        // hide the right-column calendar (we're using in-block now)
        var calendarColumn = document.querySelector('.staff-calendar-column');
        if (calendarColumn) {
          var rc = calendarColumn.querySelector('.staff-calendar');
          var placeholder = calendarColumn.querySelector('.staff-calendar-placeholder');
          if (rc && rc._calendar) { rc._calendar.destroy(); rc._calendar = null; }
          if (rc) rc.style.display = 'none';
          if (placeholder) placeholder.style.display = 'none';
          var rightClose = calendarColumn.querySelector('.staff-calendar-close'); if (rightClose) rightClose.style.display = 'none';
        }

        var exp = document.createElement('div');
        exp.className = 'staff-card-expander';
        exp.setAttribute('data-staff-id', staffId);
        var expInner = document.createElement('div');
        expInner.className = 'staff-card-expander-inner';
        // close button
        var closeBtn = document.createElement('button');
        closeBtn.className = 'staff-card-expander-close';
        closeBtn.textContent = 'Close';
        closeBtn.addEventListener('click', function(){
          if (exp._calendar) { exp._calendar.destroy(); exp._calendar = null; }
          exp.remove();
          card.classList.remove('selected');
          // restore right-column placeholder
          if (calendarColumn && placeholder) placeholder.style.display = 'block';
          if (window.history && window.history.pushState) {
            var url = new URL(window.location.href);
            url.searchParams.delete('staff_id');
            window.history.pushState({}, '', url.toString());
          }
        });
        expInner.appendChild(closeBtn);
        var calWrap = document.createElement('div'); calWrap.className = 'staff-card-calendar';
        expInner.appendChild(calWrap);
        exp.appendChild(expInner);

        // insert expander after the card
        card.parentNode.insertBefore(exp, card.nextSibling);
        // mount calendar
        var inst = createCalendar(calWrap, staffId);
        exp._calendar = inst;
        calWrap._calendar = inst;

      // push state for deep linking
      if (window.history && window.history.pushState) {
        var url = new URL(window.location.href);
        url.searchParams.set('staff_id', staffId);
        window.history.pushState({staff_id: staffId}, '', url.toString());
      }

      // show right-column close control (if visible) and focus for accessibility
      var rightCloseBtn = document.querySelector('.staff-calendar-close');
      if (rightCloseBtn && rightCloseBtn.style.display !== 'inline-block') {
        rightCloseBtn.style.display = 'none';
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
              var closeBtn = document.querySelector('.staff-calendar-close');
              if (closeBtn) closeBtn.style.display = 'none';
          }
      });

      // close button behavior and Esc key handling
      (function(){
        var closeBtn = document.querySelector('.staff-calendar-close');
        if (closeBtn) {
          closeBtn.addEventListener('click', function(){
            var calendarColumn = document.querySelector('.staff-calendar-column');
            if (!calendarColumn) return;
            var calEl = calendarColumn.querySelector('.staff-calendar');
            var placeholder = calendarColumn.querySelector('.staff-calendar-placeholder');
            if (calEl && calEl._calendar) { calEl._calendar.destroy(); calEl._calendar = null; }
            if (calEl) { calEl.style.display = 'none'; calEl.removeAttribute('data-staff-id'); }
            if (placeholder) { placeholder.style.display = 'block'; }
            closeBtn.style.display = 'none';
            // remove staff_id param from url
            if (window.history && window.history.pushState) {
              var url = new URL(window.location.href);
              url.searchParams.delete('staff_id');
              window.history.pushState({}, '', url.toString());
            }
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
          if (card) { card.click(); }
          else {
            // if card not present (e.g., rendered later), still mount calendar directly
            var calendarColumn = document.querySelector('.staff-calendar-column');
            if (!calendarColumn) return;
            var placeholder = calendarColumn.querySelector('.staff-calendar-placeholder');
            var calEl = calendarColumn.querySelector('.staff-calendar');
            if (placeholder) placeholder.style.display = 'none';
            if (calEl) { calEl.style.display = 'block'; calEl.setAttribute('data-staff-id', sid); calEl._calendar = createCalendar(calEl, sid); }
            var closeBtn = document.querySelector('.staff-calendar-close');
            if (closeBtn) closeBtn.style.display = 'inline-block';
          }
        }
      })();

        }
      }

    });
