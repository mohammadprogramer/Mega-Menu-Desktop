(() => {
  'use strict';

  const previewFor = (input) => document.querySelector(`[data-preview-for="${CSS.escape(input.id)}"]`);

  document.addEventListener('click', (event) => {
    const choose = event.target.closest('.ramka-mega-media');
    if (choose) {
      if (!window.wp || !wp.media) return;
      event.preventDefault();
      const input = document.getElementById(choose.dataset.target || '');
      if (!input) return;
      const frame = wp.media({ title: choose.textContent || 'Choose image', library: { type: 'image' }, multiple: false });
      frame.on('select', () => {
        const selected = frame.state().get('selection').first();
        if (!selected) return;
        const attachment = selected.toJSON();
        input.value = attachment.id || '';
        const preview = previewFor(input);
        if (!preview) return;
        preview.replaceChildren();
        const url = attachment.sizes?.thumbnail?.url || attachment.sizes?.medium?.url || attachment.url || '';
        if (url) {
          const img = document.createElement('img');
          img.src = url;
          img.alt = '';
          img.width = 88;
          img.height = 88;
          preview.appendChild(img);
        }
      });
      frame.open();
      return;
    }

    const remove = event.target.closest('.ramka-mega-media-remove');
    if (!remove) return;
    event.preventDefault();
    const input = document.getElementById(remove.dataset.target || '');
    if (!input) return;
    input.value = '';
    const preview = previewFor(input);
    if (preview) preview.replaceChildren();
  });
})();
