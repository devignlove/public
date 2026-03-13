document.addEventListener('DOMContentLoaded', () => {
  if (!document.body.classList.contains('post-type-remorques')) {
    return;
  }

  const postStuff = document.getElementById('poststuff');
  const postBody = document.getElementById('post-body');

  if (!postStuff || !postBody) {
    return;
  }

  const sections = [
    { id: 'titlediv', label: 'Base' },
    { id: 'ozi_featured_media', label: 'Media' },
    { id: 'ozi_meta_basic', label: 'Essentiel' },
    { id: 'ozi_meta_adv', label: 'Contenu' },
    { id: 'submitdiv', label: 'Publier' },
  ].map((item) => ({ ...item, el: document.getElementById(item.id) }))
    .filter((item) => item.el);

  if (sections.length) {
    const nav = document.createElement('div');
    nav.className = 'ozi-admin-tabs';
    nav.setAttribute('aria-label', 'Navigation remorque');

    sections.forEach((section) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'ozi-admin-tab';
      button.textContent = section.label;
      button.dataset.target = section.id;

      button.addEventListener('click', () => {
        section.el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        setActiveTab(section.id);
        flashSection(section.el);
      });

      nav.appendChild(button);
    });

    postStuff.insertBefore(nav, postBody);
    syncStickyOffsets(nav);

    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((entry) => entry.isIntersecting)
          .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

        if (visible?.target?.id) {
          setActiveTab(visible.target.id);
        }
      },
      {
        rootMargin: '-20% 0px -60% 0px',
        threshold: [0.15, 0.35, 0.6],
      }
    );

    sections.forEach((section) => observer.observe(section.el));
    setActiveTab(sections[0].id);

    window.addEventListener('resize', () => syncStickyOffsets(nav), { passive: true });
  }

  const advancedInside = document.querySelector('#ozi_meta_adv .inside');
  if (advancedInside) {
    const titles = Array.from(advancedInside.querySelectorAll(':scope > .ozi-title'));

    titles.forEach((title, index) => {
      const box = title.nextElementSibling;
      if (!box || !box.classList.contains('ozi-box')) {
        return;
      }

      const wrapper = document.createElement('section');
      wrapper.className = 'ozi-admin-accordion';
      if (index === 0) {
        wrapper.classList.add('is-open');
      }

      const toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'ozi-admin-accordion__toggle';
      toggle.setAttribute('aria-expanded', index === 0 ? 'true' : 'false');
      toggle.innerHTML = `<span>${title.textContent.trim()}</span><span class="ozi-admin-accordion__icon" aria-hidden="true"></span>`;

      const content = document.createElement('div');
      content.className = 'ozi-admin-accordion__content';

      title.parentNode.insertBefore(wrapper, title);
      wrapper.appendChild(toggle);
      wrapper.appendChild(content);
      content.appendChild(box);
      title.remove();

      if (index !== 0) {
        content.hidden = true;
      }

      toggle.addEventListener('click', () => {
        const isOpen = wrapper.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        content.hidden = !isOpen;
      });
    });
  }

  function setActiveTab(targetId) {
    document.querySelectorAll('.ozi-admin-tab').forEach((button) => {
      button.classList.toggle('is-active', button.dataset.target === targetId);
    });
  }

  function flashSection(section) {
    section.classList.remove('ozi-admin-flash');
    window.requestAnimationFrame(() => {
      section.classList.add('ozi-admin-flash');
      window.setTimeout(() => section.classList.remove('ozi-admin-flash'), 900);
    });
  }

  function syncStickyOffsets(nav) {
    const height = Math.ceil(nav.getBoundingClientRect().height);
    document.body.style.setProperty('--ozi-admin-tabs-height', `${height}px`);
  }
});
