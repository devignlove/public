(function () {
  'use strict';

  const root = document.querySelector('[data-ozi-home]');
  if (!root) return;

  const hero = root.querySelector('[data-home-hero]');
  const copy = root.querySelector('[data-home-copy]');
  const revealEls = root.querySelectorAll('[data-reveal]');
  const videos = root.querySelectorAll('video');
  const maskFrame = root.querySelector('[data-home-mask-frame]');
  const maskCanvas = root.querySelector('[data-home-mask-canvas]');
  const maskVideo = root.querySelector('[data-home-mask-video]');
  const maskImage = root.querySelector('[data-home-mask-image]');

  if (!hero || !copy) return;

  const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
  const state = {
    progress: 0,
    pointerX: 0,
    pointerY: 0,
  };

  const applyHeroState = (progress = state.progress) => {
    root.style.setProperty('--ozi-home-progress', progress.toFixed(3));
    root.style.setProperty('--ozi-mask-scale', (2.08 - progress * 1.08).toFixed(3));
    root.style.setProperty('--ozi-mask-y', `${Math.round(progress * -92)}px`);
    root.style.setProperty('--ozi-mask-rotate', `${(progress * -3.6).toFixed(2)}deg`);
    root.style.setProperty('--ozi-mask-stroke-opacity', (0.96 - progress * 0.34).toFixed(3));
    root.style.setProperty('--ozi-mask-glow-opacity', (0.18 + progress * 0.12).toFixed(3));
    root.style.setProperty('--ozi-visual-y', `${(progress * -7).toFixed(2)}vh`);
    root.style.setProperty('--ozi-visual-rotate', `${(progress * -3.1).toFixed(2)}deg`);
    root.style.setProperty('--ozi-copy-y', `${Math.round(progress * -44)}px`);
  };

  const updatePointerOffset = (event) => {
    const rect = hero.getBoundingClientRect();
    const px = ((event.clientX - rect.left) / rect.width - 0.5);
    const py = ((event.clientY - rect.top) / rect.height - 0.5);

    state.pointerX = clamp(px, -1, 1);
    state.pointerY = clamp(py, -1, 1);

    root.style.setProperty('--ozi-pointer-x', `${(state.pointerX * 20).toFixed(2)}px`);
    root.style.setProperty('--ozi-pointer-y', `${(state.pointerY * 12).toFixed(2)}px`);
  };

  hero.addEventListener('pointermove', updatePointerOffset);
  hero.addEventListener('pointerleave', () => {
    state.pointerX = 0;
    state.pointerY = 0;
    root.style.setProperty('--ozi-pointer-x', '0px');
    root.style.setProperty('--ozi-pointer-y', '0px');
  });

  if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            revealObserver.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.18,
        rootMargin: '0px 0px -10% 0px',
      }
    );

    revealEls.forEach((element) => revealObserver.observe(element));
  } else {
    revealEls.forEach((element) => element.classList.add('is-visible'));
  }

  videos.forEach((video) => {
    video.muted = true;
    video.defaultMuted = true;
    video.loop = true;
    video.playsInline = true;

    try {
      video.setAttribute('muted', '');
      video.setAttribute('autoplay', '');
      video.setAttribute('playsinline', '');
      video.play().catch(() => {});
    } catch (error) {
      // No-op: the CSS still provides a usable visual fallback.
    }
  });

  const updateProgressFromScroll = () => {
    const rect = hero.getBoundingClientRect();
    const distance = Math.max(rect.height - window.innerHeight, window.innerHeight * 0.75);
    const progress = clamp((window.innerHeight - rect.top) / distance, 0, 1);
    state.progress = progress;
    applyHeroState(progress);
  };

  let detachScrollFallback = null;
  if (window.gsap && window.ScrollTrigger && maskFrame) {
    window.gsap.registerPlugin(window.ScrollTrigger);

    const scrollTween = window.gsap.to(state, {
      progress: 1,
      ease: 'none',
      scrollTrigger: {
        trigger: hero,
        start: 'top top',
        end: 'bottom top',
        scrub: true,
        invalidateOnRefresh: true,
        onUpdate: (trigger) => {
          state.progress = trigger.progress;
          applyHeroState(trigger.progress);
        },
      },
    });

    const copyTween = window.gsap.to(copy, {
      yPercent: -12,
      ease: 'none',
      scrollTrigger: {
        trigger: hero,
        start: 'top top',
        end: 'bottom top',
        scrub: true,
      },
    });

    detachScrollFallback = () => {
      if (copyTween.scrollTrigger) {
        copyTween.scrollTrigger.kill();
      }
      copyTween.kill();

      if (scrollTween.scrollTrigger) {
        scrollTween.scrollTrigger.kill();
      }
      scrollTween.kill();
    };

    applyHeroState(0);
  } else {
    let ticking = false;
    const onScroll = () => {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(() => {
        updateProgressFromScroll();
        ticking = false;
      });
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', updateProgressFromScroll);
    updateProgressFromScroll();

    detachScrollFallback = () => {
      window.removeEventListener('scroll', onScroll);
      window.removeEventListener('resize', updateProgressFromScroll);
    };
  }

  const initThreeMask = () => {
    if (!window.THREE || !maskCanvas || (!maskVideo && !maskImage)) {
      return null;
    }

    const THREE = window.THREE;
    const stage = maskCanvas.parentElement;
    if (!stage) {
      return null;
    }

    const renderer = new THREE.WebGLRenderer({
      canvas: maskCanvas,
      alpha: true,
      antialias: true,
      powerPreference: 'high-performance',
    });
    renderer.setClearColor(0x000000, 0);
    if ('outputColorSpace' in renderer && THREE.SRGBColorSpace) {
      renderer.outputColorSpace = THREE.SRGBColorSpace;
    }

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(24, 1, 0.1, 100);
    camera.position.z = 5.2;

    const geometry = new THREE.PlaneGeometry(7.2, 2.4, 48, 24);

    let texture = null;
    if (maskVideo) {
      texture = new THREE.VideoTexture(maskVideo);
      texture.minFilter = THREE.LinearFilter;
      texture.magFilter = THREE.LinearFilter;
      texture.generateMipmaps = false;
    } else if (maskImage) {
      texture = new THREE.TextureLoader().load(maskImage.currentSrc || maskImage.src);
      texture.minFilter = THREE.LinearFilter;
      texture.magFilter = THREE.LinearFilter;
    }

    if (!texture) {
      renderer.dispose();
      geometry.dispose();
      return null;
    }

    const material = new THREE.MeshBasicMaterial({
      map: texture,
      transparent: true,
    });

    const mesh = new THREE.Mesh(geometry, material);
    scene.add(mesh);
    root.classList.add('has-webgl');

    const resize = () => {
      const rect = stage.getBoundingClientRect();
      const width = Math.max(1, Math.round(rect.width));
      const height = Math.max(1, Math.round(rect.height));
      const dpr = Math.min(window.devicePixelRatio || 1, 1.75);

      renderer.setPixelRatio(dpr);
      renderer.setSize(width, height, false);
      camera.aspect = width / height;
      camera.updateProjectionMatrix();
    };

    let rafId = 0;
    let currentX = 0;
    let currentY = 0;

    const render = () => {
      rafId = window.requestAnimationFrame(render);

      currentX += (state.pointerX - currentX) * 0.07;
      currentY += (state.pointerY - currentY) * 0.07;

      mesh.rotation.y = currentX * 0.34 + state.progress * 0.24;
      mesh.rotation.x = currentY * -0.18 - state.progress * 0.08;
      mesh.position.z = state.progress * -0.35;

      const scale = 1.02 + state.progress * 0.08;
      mesh.scale.set(scale, scale, 1);

      renderer.render(scene, camera);
    };

    resize();
    render();
    window.addEventListener('resize', resize);

    return () => {
      window.cancelAnimationFrame(rafId);
      window.removeEventListener('resize', resize);
      root.classList.remove('has-webgl');

      geometry.dispose();
      material.dispose();
      if (texture && typeof texture.dispose === 'function') {
        texture.dispose();
      }
      renderer.dispose();
    };
  };

  const destroyThreeMask = initThreeMask();

  window.addEventListener('beforeunload', () => {
    if (typeof detachScrollFallback === 'function') {
      detachScrollFallback();
    }

    if (typeof destroyThreeMask === 'function') {
      destroyThreeMask();
    }
  });
})();
