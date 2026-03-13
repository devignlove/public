(function (wp) {
  if (!wp || !wp.blocks) return;
  if (wp.blocks.getBlockType('ozi/showcase')) return; // already registered (via metadata)

  const el = wp.element.createElement;
  wp.blocks.registerBlockType('ozi/showcase', {
    title: 'Ozi Showcase Slider',
    icon: 'slides',
    category: 'design',
    description: 'Slider dynamique basé sur le CPT Remorques.',
    edit: function () {
      return el('p', { style: { padding: '8px', opacity: .8 } },
        'Ozi Showcase – l’aperçu est visible côté front (publiez/aperçu).');
    },
    save: function () { return null; } // dynamic
  });
})(window.wp);
