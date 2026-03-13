/* OZI Showcase — slider + sections full-screen + deep-link par PATH
   - URL propres: /showcase/<slug> (sans hash)
   - "Infos": sections plein écran, image de fond, bouton "Voir plus"
   - "Technique": 2 colonnes plein écran (Caractéristiques / Équipement)
   - "Avis": carrousel horizontal plein écran (sans rating)
   - "FAQ": plein écran
*/
(function () {
  'use strict';
  const init = () => {
    const slidesEl  = document.getElementById("slides");
    const sliderEl  = document.getElementById("slider");
    const detailsEl = document.getElementById("details-card");
    const prev      = document.getElementById("prev");
    const next      = document.getElementById("next");
    const nav       = document.getElementById("nav-arrows");
    const discover  = document.getElementById("discover");

    if (!slidesEl || !detailsEl || !sliderEl) return;

    const products = Array.isArray(window.OZI_DATA?.products) ? window.OZI_DATA.products : [];
    const slides   = document.querySelectorAll(".slide");
    if (!products.length || !slides.length) {
      detailsEl.innerHTML = '<p style="padding:16px">Aucune remorque trouvée.</p>';
      return;
    }

 

    /* ================= Path-based deep-link ================= */
    function normalizeBase(str) {
      try {
        // Accepte valeur absolue ou relative et en extrait le PATH
        const u = new URL(str, window.location.origin);
        let p = u.pathname || "/";
        if (!p.endsWith("/")) p += "/";
        return p;
      } catch {
        let p = String(str || "/");
        if (!p.startsWith("/")) p = "/" + p;
        if (!p.endsWith("/")) p += "/";
        return p;
      }
    }
    const basePath = normalizeBase(sliderEl.dataset.base || "/showcase/");
    const startSlug = (sliderEl.dataset.start || "").replace(/^\/+|\/+$/g, "");

    function slugFromPath() {
      // pathname attendu: /showcase/<slug> ou juste /showcase/
      const path = window.location.pathname;
      if (!path.startsWith(basePath)) return startSlug || products[0]?.slug || "";
      const rest = path.slice(basePath.length).replace(/^\/+|\/+$/g, "");
      return rest;
    }
    function indexFromURL() {
      const slug = slugFromPath();
      if (!slug) return 0;
      const i = products.findIndex(p => p.slug === slug);
      return i >= 0 ? i : 0;
    }
    function setURL(i, push=false) {
      const p = products[i]; if (!p) return;
      const href = basePath + encodeURIComponent(p.slug || String(i));
      if (push) history.pushState({ i }, '', href);
      else      history.replaceState({ i }, '', href);
    }


      // Technique full-screen (2 colonnes)
      /* ================= Modal Infos ================= */
function ensureInfosModal() {
  let m = document.getElementById("ozi-infos-modal");
  if (m) return m;

  m = document.createElement("div");
  m.id = "ozi-infos-modal";
  m.className = "ozi-modal";
  m.innerHTML = `
    <div class="ozi-modal__backdrop" data-ozi-close></div>
    <div class="ozi-modal__dialog" role="dialog" aria-modal="true" aria-label="Infos">
      <button type="button" class="ozi-modal__close" aria-label="Fermer" data-ozi-close>×</button>
      <div class="ozi-modal__content"></div>
    </div>
  `;
  document.body.appendChild(m);

  // fermer clic backdrop / croix
  m.addEventListener("click", (e) => {
    if (e.target.closest("[data-ozi-close]")) closeInfosModal();
  });

  // fermer ESC
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeInfosModal();
  });

  return m;
}

function openInfosModalFromCard() {
  const card = document.getElementById("details-card");
  if (!card) return;

  const infosFull = card.querySelector(".infos-full");
  if (!infosFull) return;

  const modal = ensureInfosModal();
  const content = modal.querySelector(".ozi-modal__content");

  // clone pour ne pas déplacer l'original
  const clone = infosFull.cloneNode(true);

  // reset éventuel (sécurité)
  const track = clone.querySelector(".infos-track");
  if (track) track.style.transform = "";

  content.innerHTML = "";
  content.appendChild(clone);

  modal.classList.add("is-open");
  document.body.classList.add("ozi-modal-open");

  // Ré-initialise le slider DANS la modal (bouton ❯)
  initInfosSlider(modal);
}

function closeInfosModal() {
  const modal = document.getElementById("ozi-infos-modal");
  if (!modal) return;
  modal.classList.remove("is-open");
  document.body.classList.remove("ozi-modal-open");
  const c = modal.querySelector(".ozi-modal__content");
  if (c) c.innerHTML = "";
}

// Click sur ton bouton CTA (créé dans renderDetails)
document.addEventListener("click", (e) => {
  const btn = e.target.closest("[data-ozi-open-infos]");
  if (!btn) return;
  e.preventDefault();
  openInfosModalFromCard();
});


    
    /* ================= Rendering helpers ================= */
   
        function initInfosSlider(scope){
      const wrap  = scope.querySelector('.infos-full');
      if (!wrap) return;
      const track = wrap.querySelector('.infos-track');
      const slides = [...wrap.querySelectorAll('.info-slide')];
      const next  = wrap.querySelector('.info-next');
      if (!slides.length) return;

      let idx = 0;
      function setActive(){
        slides.forEach((s,i)=>s.classList.toggle('is-active', i===idx));
        track.style.transform = `translateX(-${idx * 100}%)`;
        
        
      }
      setActive();

      // Navigation au bouton uniquement
      next?.addEventListener('click', ()=>{
        idx = (idx + 1) % slides.length;
        setActive();
      });

      // Bloque le swipe horizontal / drag souris sur la piste
      const block = e => { 
        // on laisse le scroll VERTICAL passer (touch-action: pan-y gère déjà le tactile)
        // ici on neutralise un éventuel drag start
        if (e.type === 'mousedown') e.preventDefault();
      };
      track.addEventListener('mousedown', block);
      track.addEventListener('dragstart', e => e.preventDefault());

      
    }

    /* ================= Render details ================= */
      function renderDetails(i) {
      const p = products[i]; if (!p) return;
    
      //Intro + bouton 
    const introsText  = (p.intros && p.intros.trim()) ? p.intros : "Tu veux voir toutes les infos détaillées ?";
    const introsLabel = (p.intros_label && p.intros_label.trim()) ? p.intros_label : "Voir les infos";
    const introsHTML = (introsText.length || introsLabel.length) ? `
     <section class="intros-full">
        <div class="intros-box">
          <p class="intros-text">${introsText}</p>
        </div>
      <button type="button" class="intros-btn" data-ozi-open-infos>${introsLabel}</button>
      </section>
    ` : "";


      // Infos — slider contrôlé par bouton
    const infos = Array.isArray(p.infos) ? p.infos : [];
    const infosHTML = infos.length ? `
      <section class="infos-full ozi-hidden" hidden aria-hidden="true">
        <div class="infos-track">
          ${infos.map(s => `
            <article class="info-slide">
              <div class="info-left">
                <div class="info-left-content">
                ${s.title ? `<h3>${s.title}</h3>` : ''}
                ${s.text ? `<p>${s.text}</p>` : ''}
                </div>
              </div>
              <div class="info-right">
                ${s.image ? `<img src="${s.image}" alt="">` : ''}
              </div>
            </article>
          `).join('')}
        </div>
        <button class="info-next" aria-label="Suivant" title="Suivant">❯</button>
      </section>
    ` : "";

      const safeSlug = String(p.slug || i).replace(/[^a-z0-9\-_]/gi, "-");
const car = Array.isArray(p.tech?.caracteristiques) ? p.tech.caracteristiques : [];
const eqp = Array.isArray(p.tech?.equipements) ? p.tech.equipements : [];

const techHTML = (car.length || eqp.length) ? `
<section class="tech-full tech-tabs-container" data-tabs="${safeSlug}">

  <div class="tab">
    ${car.length ? `<button class="tablinks" onclick="openCity(event, 'London-${safeSlug}')">Caractéristiques</button>` : ''}
    ${eqp.length ? `<button class="tablinks" onclick="openCity(event, 'Paris-${safeSlug}')">Équipement</button>` : ''}
  </div>

  ${car.length ? `
  <div id="London-${safeSlug}" class="tabcontent">
    <table class="tech-table">
      ${car.map(r=>`<tr colspan="3"><th>${r.label||''}</th><td>${r.value||''}</td></tr>`).join('')}
    </table>
  </div>` : ''}

  ${eqp.length ? `
  <div id="Paris-${safeSlug}" class="tabcontent">
    <table class="tech-table">
      ${eqp.map(r=>`<tr><th>${r.label||''}</th><td>${r.value||''}</td></tr>`).join('')}
    </table>
  </div>` : ''}

</section>
` : "";


      // --- Avis (auto-slide + fade) ---
      const reviews = Array.isArray(p.reviews) ? p.reviews : [];
      const reviewsHTML = reviews.length ? `
        <section class="reviews-full">
          <h3>Some Reviews</h3>
          <div class="reviews-track">
            ${reviews.map(r => `
              <article class="review">
                <div class="quote-mark">❝</div>
                <blockquote>${r.text || ''}</blockquote>
                <div class="author">— ${r.author || ''}</div>
              </article>
            `).join('')}
          </div>
        </section>
      ` : "";


      // --- FAQ full-screen
      const faq = Array.isArray(p.faq) ? p.faq : [];
      const faqHTML = faq.length ? `
        <section class="faq-full">
          <div class="faq-mark">FAQ</div>
          <div class="faq-box">
            <h3>Frequently Asked Questions</h3>
            <div class="faq-list">
            ${faq.map(f=>`
              <details>
                <summary>${f.q||''}</summary>
                <p style="margin-top:6px">${f.a||''}</p>
              </details>
            `).join('')}
            </div>
          </div>
        </section>
      ` : "";

      const accessories = Array.isArray(p.accessories) ? p.accessories : [];
      const accessoriesHTML = accessories.length ? `
        <section class="accessories-full">
          <div class="accessories-shell">
            <div class="accessories-head">
              <p>${window.OZI_DATA?.i18n?.accessories || 'Accessoires compatibles'}</p>
            </div>
            <div class="accessories-grid">
              ${accessories.map((item) => `
                <article class="accessory-card">
                  ${item.image ? `<div class="accessory-card__media"><img src="${item.image}" alt="${item.title || ''}"></div>` : ''}
                  <div class="accessory-card__body">
                    <h3>${item.title || ''}</h3>
                    ${item.excerpt ? `<p>${item.excerpt}</p>` : ''}
                    ${item.url ? `<a class="accessory-card__link" href="${item.url}">${window.OZI_DATA?.i18n?.discoverAccessory || 'Voir l accessoire'}</a>` : ''}
                  </div>
                </article>
              `).join('')}
            </div>
          </div>
        </section>
      ` : "";

      // --- CTA
      const ctaHTML = p.buyLink ? `
        <p style="text-align:center">
          <a class="btn-buy" href="${p.buyLink}" target="_blank" rel="noopener">
            ${p.buyLabel || 'Acheter maintenant'}
          </a>
        </p>` : "";

      detailsEl.innerHTML = `
        ${introsHTML}
        ${infosHTML}
        ${techHTML}
        ${reviewsHTML}
        ${faqHTML}
        ${accessoriesHTML}
        ${ctaHTML}
      `;
  // Apply Tech Full background (from remorque post)
const bg = p.bgTechFull || "";
if (bg) {
  const tech = detailsEl.querySelector(".tech-full");
  if (tech) {
    tech.style.backgroundImage = `url("${bg}")`;
    tech.style.backgroundSize = "cover";
    tech.style.backgroundPosition = "center";
    tech.style.backgroundRepeat = "no-repeat";
  }
}
              initAutoReviews(detailsEl);
              initInfosSlider(detailsEl);
detailsEl.querySelectorAll(".tech-tabs-container .tabcontent").forEach(el => el.style.display = "none");
detailsEl.querySelectorAll(".tech-tabs-container .tablinks").forEach(btn => btn.classList.remove("active"));




        } 

      window.openCity = function openCity(evt, cityName) {
  const container = evt?.currentTarget?.closest(".tech-tabs-container");
  if (!container) return;

  const btn = evt.currentTarget;

  // Est-ce que ce bouton est déjà actif ?
  const isAlreadyActive = btn.classList.contains("active");

  // Ferme tout dans CE container
  const tabcontent = container.getElementsByClassName("tabcontent");
  for (let i = 0; i < tabcontent.length; i++) tabcontent[i].style.display = "none";

  const tablinks = container.getElementsByClassName("tablinks");
  for (let i = 0; i < tablinks.length; i++) tablinks[i].className = tablinks[i].className.replace(" active", "");

  // Si c'était déjà actif => on laisse tout fermé (toggle off)
  if (isAlreadyActive) return;

  // Sinon on ouvre celui demandé
  const el = container.querySelector(`#${(window.CSS && CSS.escape) ? CSS.escape(cityName) : cityName}`);
  if (el) el.style.display = "block";
  btn.className += " active";
};

    /* ========= Avis : autoplay + centrage permanent ========= */
function initAutoReviews(scope){
  const track = scope.querySelector('.reviews-track');
  if (!track) return;
  const cards = [...track.querySelectorAll('.review')];
  if (cards.length <= 1) return;

  // Mesure la largeur réelle d'une carte et centre le premier/dernier via padding
  function setPad(){
    const w = cards[0].getBoundingClientRect().width;
    track.style.setProperty('--review-w', w + 'px');
  }
  setPad();
  window.addEventListener('resize', () => { setPad(); center(idx, false); });

  // Met en avant la carte centrée
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(e=>{
      e.target.classList.toggle('is-active', e.isIntersecting);
    });
  }, { root: track, threshold: 0.6 });
  cards.forEach(c => io.observe(c));

  let idx = 0, timer = null, busy = false, scrollDebounce = null;

  function center(i, smooth = true){
    i = (i + cards.length) % cards.length;
    const card = cards[i];
    const left = card.offsetLeft - (track.clientWidth - card.offsetWidth)/2;
    track.scrollTo({ left, behavior: smooth ? 'smooth' : 'auto' });
    idx = i;
  }

  function go(next){
    if (busy) return;
    busy = true;
    track.classList.add('is-fading');
    center(next, true);
    setTimeout(()=>{ track.classList.remove('is-fading'); busy = false; }, 650);
  }
  function start(){ if (!timer) timer = setInterval(()=>go(idx+1), 4500); }
  function stop(){  if (timer) { clearInterval(timer); timer = null; } }

  // pause sur interaction
  track.addEventListener('pointerenter', stop, {passive:true});
  track.addEventListener('pointerleave', start, {passive:true});
  track.addEventListener('touchstart', stop, {passive:true});
  track.addEventListener('touchend', start, {passive:true});
   track.addEventListener('scroll', () => {
    stop();
    if (scrollDebounce) clearTimeout(scrollDebounce);
    scrollDebounce = setTimeout(() => {
      const viewCenter = track.scrollLeft + track.clientWidth / 2;
      let best = 0, bestDist = Infinity;
      cards.forEach((c, i) => {
        const centerX = c.offsetLeft + c.offsetWidth / 2;
        const d = Math.abs(centerX - viewCenter);
        if (d < bestDist) { bestDist = d; best = i; }
      });
      center(best);
      start();
    }, 120);
  }, { passive:true });

  // Premier affichage centré + autoplay
  center(0, false);
  // premier run
  start();
}

    /* ================= Media sync ================= */
    function syncHeroMedia(activeIndex) {
      document.querySelectorAll('.slide').forEach((s, i) => {
        const vid = s.querySelector('video.hero-media');
        const ifr = s.querySelector('iframe.hero-media');

        if (i !== activeIndex) {
          if (vid) {
            try {
              vid.pause();
            } catch(e){}
          }
          if (ifr && ifr.src) { ifr.src = ''; }
          return;
        }

        if (vid) {
          vid.muted = true;
          vid.defaultMuted = true;
          vid.loop = true;
          vid.playsInline = true;

          try {
            vid.setAttribute('muted', '');
            vid.setAttribute('autoplay', '');
            vid.setAttribute('playsinline', '');
            vid.setAttribute('webkit-playsinline', '');
            if (!vid.dataset.prepared) {
              vid.load();
              vid.dataset.prepared = '1';
            }
          } catch (e) {}

          const tryPlay = () => {
            const playPromise = vid.play();
            if (playPromise && typeof playPromise.catch === 'function') {
              playPromise.catch(() => {
                vid.setAttribute('controls', '');
                vid.classList.add('hero-media--manual');
              });
            }
          };

          if (vid.readyState >= 2) {
            tryPlay();
          } else {
            vid.addEventListener('loadeddata', tryPlay, { once: true });
          }
        }

        if (ifr && !ifr.src && ifr.dataset.src) { ifr.src = ifr.dataset.src; }
      });
    }

    /* ================= Navigation / swipe ================= */
    let index = 0, hideTimer;
    function showArrows(){ if(!nav) return; nav.classList.add('visible'); clearTimeout(hideTimer); hideTimer=setTimeout(()=>nav.classList.remove('visible'), 2000); }
    function go(n, push=false){
      const max = Math.min(slides.length, products.length);
      index = (n + max) % max;
      slidesEl.style.transform = `translateX(-${index*100}%)`;
      renderDetails(index);
      syncHeroMedia(index);
      showArrows();
      setURL(index, push);
    }

    prev?.addEventListener('click', ()=>go(index-1, true));
    next?.addEventListener('click', ()=>go(index+1, true));
    sliderEl.addEventListener('mousemove', showArrows);
    sliderEl.addEventListener('touchstart', showArrows, {passive:true});
    discover?.addEventListener('click', ()=>document.getElementById('content')?.scrollIntoView({behavior:'smooth'}));

    // Swipe
    let sx=null, sy=null, sliding=false;
    sliderEl.addEventListener('touchstart', e=>{ const t=e.touches[0]; sx=t.clientX; sy=t.clientY; sliding=true; }, {passive:true});
    sliderEl.addEventListener('touchmove', e=>{
      if(!sliding) return;
      const t=e.touches[0], dx=t.clientX-sx, dy=t.clientY-sy;
      if(Math.abs(dx)>24 && Math.abs(dx)>Math.abs(dy)){
        e.preventDefault();
        go(dx>0 ? index-1 : index+1, true);
        sliding=false;
      }
    }, {passive:false});
    sliderEl.addEventListener('touchend', ()=>sliding=false);

    // Historique
    window.addEventListener('popstate', ()=>{ const i=indexFromURL(); if(i!==index) go(i,false); });

    // Première vue
    go(indexFromURL(), false);
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
