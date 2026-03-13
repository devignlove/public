(() => {
  const btn = document.getElementById('menu-toggle');
  const nav = document.getElementById('site-nav');
  const backdrop = document.getElementById('nav-backdrop');
  const desktopQuery = window.matchMedia('(min-width: 901px)');
  if (!btn || !nav) return;

  function setState(open) {
    const isDesktop = desktopQuery.matches;
    const isOpen = !isDesktop && open;

    nav.classList.toggle('is-open', isOpen);
    btn.classList.toggle('is-open', isOpen);
    nav.setAttribute('aria-hidden', isDesktop || isOpen ? 'false' : 'true');
    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

    const sr = btn.querySelector('.screen-reader-text');
    const text = isOpen ? btn.getAttribute('data-close-text') : btn.getAttribute('data-open-text');
    if (sr) {
      if (text) sr.textContent = text;
    }
    if (text) {
      btn.setAttribute('aria-label', text);
    }

    document.body.classList.toggle('nav-open', isOpen);
  }

  btn.addEventListener('click', () => {
    const open = !nav.classList.contains('is-open');
    setState(open);
  });

  // close when clicking a link inside the nav
  nav.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => setState(false));
  });

  // clicking the backdrop should close the menu
  if (backdrop) {
    backdrop.addEventListener('click', () => setState(false));
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && nav.classList.contains('is-open')) {
      setState(false);
      btn.focus();
    }
  });

  if (typeof desktopQuery.addEventListener === 'function') {
    desktopQuery.addEventListener('change', () => setState(false));
  } else {
    window.addEventListener('resize', () => {
      if (window.innerWidth >= 900 && nav.classList.contains('is-open')) {
        setState(false);
      }
    });
  }

  setState(false);
})();
