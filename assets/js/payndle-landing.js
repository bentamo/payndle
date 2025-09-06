(function(){
  // Demo dataset (no DB)
  const businesses = [
    { name: 'Barber Bros', category: 'barber', location: 'Quezon City', payments: ['GCash','Maya','Cards'] },
    { name: 'Glow Salon', category: 'salon', location: 'Makati', payments: ['GCash','PayPal','Cards'] },
    { name: 'QuickClinic', category: 'clinic', location: 'Pasig', payments: ['GCash','Maya'] },
    { name: 'StyleLab', category: 'stylist', location: 'Taguig', payments: ['Maya','Cards'] },
    { name: 'Spa Haven', category: 'spa', location: 'San Juan', payments: ['GCash','Cards'] },
    { name: 'NailCraft', category: 'salon', location: 'Mandaluyong', payments: ['GCash','Maya','PayPal'] },
    { name: 'Cut & Shave Co.', category: 'barber', location: 'Cebu City', payments: ['GCash'] },
    { name: 'DermCare Now', category: 'clinic', location: 'Davao', payments: ['Maya','Cards'] }
  ];

  // Utilities
  function qs(s, el=document){ return el.querySelector(s); }
  function qsa(s, el=document){ return Array.from(el.querySelectorAll(s)); }

  // Carousel autoplay
  function initCarousel(){
    const carousel = qs('.payndle-landing .carousel');
    if(!carousel) return;
    const track = qs('.carousel-track', carousel);
    if(!track) return;

    // Clone items for seamless loop
    const items = qsa('.carousel-item', track);
    items.forEach(i=> track.appendChild(i.cloneNode(true)));

    let offset = 0;
    function tick(){
      offset += 1; // px per frame
      track.style.transform = `translateX(${-offset}px)`;
      const first = track.firstElementChild;
      if(first && offset >= first.getBoundingClientRect().width + 16){
        // move first to end
        track.appendChild(first);
        offset = 0;
        track.style.transform = 'translateX(0)';
      }
      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  // Search rendering
  function renderResults(list){
    const el = qs('#pl-search-results');
    if(!el) return;
    el.innerHTML = '';
    if(!list.length){
      el.innerHTML = '<p>No results found. Try a different keyword or category.</p>';
      return;
    }
    list.forEach(b => {
      const card = document.createElement('div');
      card.className = 'result-card';
      card.innerHTML = `
        <h4>${b.name}</h4>
        <div class="meta">${b.location} • ${b.category.charAt(0).toUpperCase()+b.category.slice(1)}</div>
        <div class="actions">
          <a href="#" class="btn btn-primary">Book<br>Now</a>
          <a href="#" class="btn btn-ghost">View<br>Details</a>
        </div>`;
      el.appendChild(card);
    });
  }

  function performSearch(){
    const q = (qs('#pl-search-query')?.value || '').toLowerCase().trim();
    const cat = qs('#pl-search-category')?.value || '';
    const filtered = businesses.filter(b => {
      const matchesQuery = !q || b.name.toLowerCase().includes(q) || b.category.includes(q) || b.location.toLowerCase().includes(q);
      const matchesCat = !cat || b.category === cat;
      return matchesQuery && matchesCat;
    });
    renderResults(filtered);
  }

  function initSearch(){
    const btn = qs('#pl-search-btn');
    btn && btn.addEventListener('click', performSearch);
  }

  // Partner form demo
  function initForm(){
    const form = qs('#pl-partner-form');
    if(!form) return;
    form.addEventListener('submit', function(){
      const biz = qs('#pl-biz-name')?.value || '';
      const contact = qs('#pl-contact-person')?.value || '';
      const email = qs('#pl-email')?.value || '';
      const type = qs('#pl-service-type')?.value || '';
      alert(`Thanks, ${contact || 'there'}! Your ${type || 'service'} business "${biz}" has been submitted. We will contact you at ${email}. (Demo only)`);
      form.reset();
      qsa('.chip').forEach(c=>c.classList.remove('active'));
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    initCarousel();
    initSearch();
    initForm();
  });
})();
