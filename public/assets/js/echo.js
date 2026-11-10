/* ==========================================================================
   Echo — interacciones (sin dependencias)
   ========================================================================== */
(() => {
  'use strict';

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const $ = (sel, ctx = document) => ctx.querySelector(sel);

  /** POST a la API con token CSRF; devuelve JSON. */
  async function api(url, data = {}) {
    const body = new URLSearchParams(data);
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrf, 'X-Requested-With': 'fetch', Accept: 'application/json' },
      body,
      credentials: 'same-origin',
    });
    const json = await res.json().catch(() => ({ ok: false, error: 'Respuesta inválida del servidor.' }));
    if (res.status === 401) location.href = '/login';
    if (!json.ok) throw new Error(json.error || 'No se pudo completar la acción.');
    return json;
  }

  function toast(msg) {
    const zone = $('.toast-zone');
    if (!zone) return;
    const t = document.createElement('div');
    t.className = 'echo-toast';
    t.textContent = msg;
    zone.appendChild(t);
    setTimeout(() => t.remove(), 2700);
  }

  const fmt = (n) => (n >= 10000 ? (n / 1000).toFixed(1).replace('.0', '').replace('.', ',') + ' mil' : n.toLocaleString('es-PE'));

  // ---------------------------------------------------------------- Diálogo de confirmación
  /**
   * Reemplaza a confirm() con un <dialog> nativo (top layer: queda por encima
   * incluso de los modales de Bootstrap). Devuelve una promesa con true/false.
   */
  function confirmDialog({ title = '¿Estás seguro?', text = '', ok = 'Aceptar', danger = true } = {}) {
    return new Promise((resolve) => {
      const dlg = document.createElement('dialog');
      dlg.className = 'echo-dialog';
      dlg.innerHTML =
        '<form method="dialog">' +
        '<div class="echo-dialog-head"><h2 class="echo-dialog-title"></h2><p class="echo-dialog-text"></p></div>' +
        // Alerta de dos acciones al estilo iOS: Cancelar a la izquierda, la acción a la derecha
        '<div class="echo-dialog-actions">' +
        '<button value="cancel" class="echo-dialog-btn js-cancel">Cancelar</button>' +
        '<button value="ok" class="echo-dialog-btn js-ok"></button>' +
        '</div>' +
        '</form>';
      $('.echo-dialog-title', dlg).textContent = title;
      $('.echo-dialog-text', dlg).textContent = text;
      const okBtn = $('.js-ok', dlg);
      okBtn.textContent = ok;
      okBtn.classList.toggle('danger', danger);
      // Dentro del modal abierto (si hay) para no pelear con su trampa de foco
      ($('.modal.show') || document.body).appendChild(dlg);
      dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close('cancel'); }); // clic en el fondo
      // Esc cierra solo el diálogo: sin esto, Bootstrap también cerraría el modal que está debajo
      dlg.addEventListener('keydown', (e) => { if (e.key === 'Escape') e.stopPropagation(); });
      dlg.addEventListener('close', () => { resolve(dlg.returnValue === 'ok'); dlg.remove(); });
      dlg.showModal();
      $('.js-cancel', dlg).focus();
    });
  }

  // ---------------------------------------------------------------- Modales de contenido
  /** Descarga una página del propio sitio y devuelve, por cada selector, el elemento encontrado. */
  async function fetchFragments(url, ...selectors) {
    const res = await fetch(url, { headers: { Accept: 'text/html' }, credentials: 'same-origin' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
    return selectors.map((sel) => {
      const el = doc.querySelector(sel);
      if (!el) throw new Error('Fragmento no encontrado: ' + sel);
      return document.importNode(el, true);
    });
  }

  const loadingHtml = '<div class="modal-loading"><div class="spinner"></div></div>';

  // Publicación en modal: la URL cambia a /p/{id} y el botón "atrás" lo cierra.
  const postModalEl = $('#postModal');
  const postModalBody = $('.js-post-modal-body');
  let postModalPushed = false;

  async function openPost(href) {
    const modal = bootstrap.Modal.getOrCreateInstance(postModalEl);
    postModalBody.innerHTML = loadingHtml;
    modal.show();
    if (postModalPushed) history.replaceState({ echoModal: true }, '', href);
    else { history.pushState({ echoModal: true }, '', href); postModalPushed = true; }
    try {
      postModalBody.replaceChildren(...(await fetchFragments(href, '.post-detail')));
    } catch {
      location.href = href; // sesión vencida, publicación borrada, etc.: navegación normal
    }
  }

  if (postModalEl) {
    postModalEl.addEventListener('hidden.bs.modal', () => {
      postModalBody.innerHTML = '';
      if (postModalPushed) { postModalPushed = false; history.back(); }
    });
    window.addEventListener('popstate', () => {
      if (!postModalPushed) return;
      postModalPushed = false; // el usuario ya volvió: no hacer otro history.back()
      bootstrap.Modal.getInstance(postModalEl)?.hide();
    });
  }

  // Seguidores / seguidos en modal (con pestañas que cargan dentro del mismo modal)
  const listModalEl = $('#listModal');
  async function openList(href) {
    const body = $('.js-list-modal-body', listModalEl);
    $('#listModalTitle').textContent = /\/following$/.test(href) ? 'Seguidos' : 'Seguidores';
    body.innerHTML = loadingHtml;
    // Si se pulsó una pestaña del propio modal, ese enlace acaba de desaparecer: el foco vuelve al modal
    if (listModalEl.contains(document.activeElement) || document.activeElement === document.body) listModalEl.focus();
    bootstrap.Modal.getOrCreateInstance(listModalEl).show();
    try {
      body.replaceChildren(...(await fetchFragments(href, '.seg-tabs', '.card-list')));
      // El enlace pulsado ya no existe: devolver el foco al modal para que Esc y Tab sigan funcionando
      ($('.seg-tabs a.active', body) || listModalEl).focus();
    } catch {
      location.href = href;
    }
  }

  /** Clic "normal" (sin Ctrl/Cmd/Shift ni botón central): esos abren en pestaña nueva como siempre. */
  const plainClick = (ev) => ev.button === 0 && !ev.metaKey && !ev.ctrlKey && !ev.shiftKey && !ev.altKey;

  // ---------------------------------------------------------------- Me gusta
  async function toggleLike(post, forceLike = false) {
    const btn = $('.js-like', post);
    if (!btn || btn.dataset.busy) return;
    if (forceLike && btn.classList.contains('liked')) return;
    btn.dataset.busy = '1';
    const icon = $('i', btn);
    const counter = $('.js-like-count', post);
    const wasLiked = btn.classList.contains('liked');
    // Actualización optimista: la interfaz responde al instante
    btn.classList.toggle('liked', !wasLiked);
    icon.className = 'bi ' + (!wasLiked ? 'bi-heart-fill' : 'bi-heart');
    btn.classList.remove('pop'); void btn.offsetWidth; btn.classList.add('pop');
    try {
      const r = await api('/api/like/' + post.dataset.postId);
      // La misma publicación puede estar dos veces en pantalla (tarjeta del feed + modal)
      document.querySelectorAll(`.post[data-post-id="${post.dataset.postId}"]`).forEach((p) => {
        const b = $('.js-like', p);
        if (b) { b.classList.toggle('liked', r.liked); $('i', b).className = 'bi ' + (r.liked ? 'bi-heart-fill' : 'bi-heart'); }
        const c = $('.js-like-count', p);
        if (c) c.textContent = fmt(r.count);
      });
    } catch (e) {
      btn.classList.toggle('liked', wasLiked);
      icon.className = 'bi ' + (wasLiked ? 'bi-heart-fill' : 'bi-heart');
      toast(e.message);
    } finally {
      delete btn.dataset.busy;
    }
  }

  function burst(media) {
    const b = $('.heart-burst', media);
    if (!b) return;
    b.classList.remove('go'); void b.offsetWidth; b.classList.add('go');
  }

  // Doble toque / doble clic sobre la foto = me gusta
  let lastTap = { el: null, t: 0 };
  document.addEventListener('pointerup', (ev) => {
    const media = ev.target.closest('.js-dbl-like');
    if (!media) return;
    const now = Date.now();
    if (lastTap.el === media && now - lastTap.t < 320) {
      burst(media);
      toggleLike(media.closest('.post'), true);
      lastTap = { el: null, t: 0 };
    } else {
      lastTap = { el: media, t: now };
    }
  });

  // ---------------------------------------------------------------- Clics delegados
  document.addEventListener('click', async (ev) => {
    // Publicación en modal: miniaturas de la cuadrícula, "comentar", "ver comentarios", notificaciones
    const postLink = ev.target.closest('.tile, .js-open-post');
    if (postLink && postModalEl && plainClick(ev)) {
      ev.preventDefault();
      openPost(postLink.getAttribute('href'));
      return;
    }

    // Seguidores / seguidos: desde el perfil o desde las pestañas dentro del propio modal
    const listLink = ev.target.closest('.js-open-list, #listModal .seg-tabs a');
    if (listLink && listModalEl && plainClick(ev)) {
      ev.preventDefault();
      openList(listLink.getAttribute('href'));
      return;
    }

    const like = ev.target.closest('.js-like');
    if (like) { toggleLike(like.closest('.post')); return; }

    const follow = ev.target.closest('.js-follow');
    if (follow) {
      if (follow.dataset.busy) return;
      follow.dataset.busy = '1';
      try {
        const r = await api('/api/follow/' + follow.dataset.userId);
        document.querySelectorAll(`.js-follow[data-user-id="${follow.dataset.userId}"]`).forEach((b) => {
          b.classList.toggle('is-following', r.following);
          b.textContent = r.following ? 'Siguiendo' : 'Seguir';
        });
        const fc = $('.js-followers-count');
        if (fc && !follow.closest('.person, .suggest, .person-card, .notif')) fc.textContent = fmt(r.followers);
      } catch (e) { toast(e.message); }
      finally { delete follow.dataset.busy; }
      return;
    }

    const share = ev.target.closest('.js-share');
    if (share) {
      const url = location.origin + share.dataset.url;
      try {
        if (navigator.share && matchMedia('(pointer: coarse)').matches) await navigator.share({ title: 'Echo', url });
        else { await navigator.clipboard.writeText(url); toast('Enlace copiado'); }
      } catch { /* cancelado */ }
      return;
    }

    const del = ev.target.closest('.js-del-comment');
    if (del) {
      const okDel = await confirmDialog({ title: '¿Eliminar comentario?', text: 'El comentario se borrará para todos.', ok: 'Eliminar' });
      if (!okDel) return;
      try {
        await api(`/api/comment/${del.dataset.id}/delete`);
        del.closest('.comment')?.remove();
        toast('Comentario eliminado');
      } catch (e) { toast(e.message); }
      return;
    }

    const clamp = ev.target.closest('.js-clamp');
    if (clamp && !ev.target.closest('a')) { clamp.classList.remove('clamp'); return; }

    const focusBtn = ev.target.closest('.js-focus-comment');
    if (focusBtn) { $('.js-comment-form input', focusBtn.closest('.post'))?.focus(); return; }

    const pw = ev.target.closest('.js-pw-toggle');
    if (pw) {
      const input = pw.parentElement.querySelector('input');
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      pw.innerHTML = `<i class="bi bi-eye${show ? '-slash' : ''}"></i>`;
    }
  });

  // Confirmaciones de formularios (eliminar publicación)
  document.addEventListener('submit', async (ev) => {
    const f = ev.target.closest('.js-confirm');
    if (!f) return;
    ev.preventDefault();
    const ok = await confirmDialog({
      title: f.dataset.confirmTitle || '¿Estás seguro?',
      text: f.dataset.confirm || '',
      ok: f.dataset.confirmOk || 'Aceptar',
    });
    if (ok) HTMLFormElement.prototype.submit.call(f); // submit() no vuelve a disparar este evento
  });

  // ---------------------------------------------------------------- Comentarios
  document.addEventListener('input', (ev) => {
    const input = ev.target.closest('.js-comment-form input');
    if (input) input.form.querySelector('button').disabled = input.value.trim() === '';
  });
  document.addEventListener('submit', async (ev) => {
    const form = ev.target.closest('.js-comment-form');
    if (!form) return;
    ev.preventDefault();
    const input = $('input', form);
    const btn = $('button', form);
    const body = input.value.trim();
    if (!body) return;
    btn.disabled = true;
    try {
      const r = await api('/api/comment/' + form.dataset.postId, { body, full: form.dataset.full || '0' });
      const post = form.closest('.post');
      $('.js-no-comments', post)?.remove();
      const list = $('.js-comments', post);
      list.insertAdjacentHTML('beforeend', r.html);
      if (form.dataset.full) list.scrollTop = list.scrollHeight;
      input.value = '';
    } catch (e) {
      toast(e.message);
      btn.disabled = false;
    }
  });

  // ---------------------------------------------------------------- Scroll infinito
  const sentinel = $('.js-sentinel');
  if (sentinel && 'IntersectionObserver' in window) {
    let loading = false;
    const io = new IntersectionObserver(async ([entry]) => {
      if (!entry.isIntersecting || loading) return;
      loading = true;
      try {
        const res = await fetch('/api/feed?before=' + sentinel.dataset.next, { headers: { 'X-Requested-With': 'fetch', Accept: 'application/json' } });
        const r = await res.json();
        $('.js-feed').insertAdjacentHTML('beforeend', r.html);
        if (r.next) sentinel.dataset.next = r.next;
        else {
          io.disconnect();
          sentinel.outerHTML = '<div class="feed-end"><i class="bi bi-check2-circle"></i><strong>Estás al día</strong><span>Ya viste todas las publicaciones recientes.</span></div>';
        }
      } catch { toast('No se pudo cargar más publicaciones'); }
      loading = false;
    }, { rootMargin: '600px' });
    io.observe(sentinel);
  }

  // ---------------------------------------------------------------- Crear publicación
  const createForm = $('.js-create');
  if (createForm) {
    const dz = $('.js-dropzone', createForm);
    const file = $('.js-file', createForm);
    const preview = $('.dz-preview', createForm);
    const submit = $('.js-submit', createForm);

    const show = (f) => {
      if (!f) return;
      if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { toast('Formato no permitido. Usa JPG, PNG o WEBP.'); return; }
      if (f.size > 8 * 1024 * 1024) { toast('La imagen supera los 8 MB.'); return; }
      preview.src = URL.createObjectURL(f);
      dz.classList.add('has-image');
      submit.disabled = false;
    };
    file.addEventListener('change', () => show(file.files[0]));
    ['dragenter', 'dragover'].forEach((t) => dz.addEventListener(t, (e) => { e.preventDefault(); dz.classList.add('drag'); }));
    ['dragleave', 'drop'].forEach((t) => dz.addEventListener(t, (e) => { e.preventDefault(); dz.classList.remove('drag'); }));
    dz.addEventListener('drop', (e) => {
      const f = e.dataTransfer.files[0];
      if (!f) return;
      const dt = new DataTransfer(); dt.items.add(f); file.files = dt.files;
      show(f);
    });
    createForm.addEventListener('submit', () => {
      submit.disabled = true;
      $('.js-submit-label', createForm).innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Subiendo a S3…';
    });
  }

  // Contadores de caracteres
  document.querySelectorAll('.js-caption').forEach((ta) => {
    const counter = ta.parentElement.querySelector('.js-count');
    const upd = () => { if (counter) counter.textContent = ta.value.length; };
    ta.addEventListener('input', upd); upd();
  });

  // Vista previa de avatar
  const avatarInput = $('.js-avatar-input');
  if (avatarInput) {
    avatarInput.addEventListener('change', () => {
      const f = avatarInput.files[0];
      if (!f) return;
      $('.js-avatar-preview').innerHTML = `<img class="avatar" src="${URL.createObjectURL(f)}" width="64" height="64" alt="">`;
    });
  }

  // Medidor de fuerza de contraseña (registro)
  const meter = $('.js-pw-meter');
  if (meter) {
    $('#password').addEventListener('input', (e) => {
      const v = e.target.value;
      let s = 0;
      if (v.length >= 8) s++;
      if (/\d/.test(v) && /[a-zA-Z]/.test(v)) s++;
      if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
      if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) s++;
      const colors = ['#ef4444', '#f59e0b', '#eab308', '#22c55e', '#16a34a'];
      meter.style.width = (s / 4) * 100 + '%';
      meter.style.background = colors[s];
    });
  }
})();
