(() => {
"use strict";

/* ==================== UTILIDADES ==================== */
const CFG = window.APP_CONFIG || {};
const CUR = CFG.MONEDA || "S/";
const DEMO = !(CFG.SUPABASE_URL && CFG.SUPABASE_ANON_KEY);
const $ = (s, el = document) => el.querySelector(s);
const $$ = (s, el = document) => [...el.querySelectorAll(s)];
const esc = s => String(s ?? "").replace(/[&<>"']/g, c => ({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" }[c]));
const pad = n => String(n).padStart(2, "0");
const ymd = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
const parse = s => { const [y,m,d] = s.split("-").map(Number); return new Date(y, m-1, d); };
const today = () => ymd(new Date());
const addDays = (s, n) => { const d = parse(s); d.setDate(d.getDate()+n); return ymd(d); };
const diffDays = (a, b) => Math.round((parse(a) - parse(b)) / 864e5);
const money = n => `${CUR} ${Number(n||0).toLocaleString("es-PE",{minimumFractionDigits:2,maximumFractionDigits:2})}`;
const uid = () => (crypto.randomUUID ? crypto.randomUUID() : Date.now().toString(36)+Math.random().toString(36).slice(2));
const cap = t => t.charAt(0).toUpperCase() + t.slice(1);
const fmtLong = s => cap(parse(s).toLocaleDateString("es-PE",{weekday:"long",day:"numeric",month:"long"}));
const fmtShort = s => parse(s).toLocaleDateString("es-PE",{day:"numeric",month:"short"}).replace(".","");
const reduceMotion = () => matchMedia("(prefers-reduced-motion: reduce)").matches;
function dueLabel(s){
  if(!s) return null;
  const d = diffDays(s, today());
  if(d < 0) return { t: d === -1 ? "Ayer" : `Hace ${-d} días`, c:"late" };
  if(d === 0) return { t:"Hoy", c:"today" };
  if(d === 1) return { t:"Mañana", c:"" };
  if(d < 7) return { t: cap(parse(s).toLocaleDateString("es-PE",{weekday:"long"})), c:"" };
  return { t: fmtShort(s), c:"" };
}
async function sha256(t){ const b = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(t)); return [...new Uint8Array(b)].map(x=>x.toString(16).padStart(2,"0")).join(""); }

/* ==================== ÍCONOS (SVG, trazo uniforme) ==================== */
const sv = (d, w = 2) => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="${w}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${d}</svg>`;
const I = {
  check: sv('<path d="M5 12.5l4.5 4.5L19 7.5"/>', 3),
  logo:  sv('<path d="M5 12.5l4.5 4.5L19 7.5"/>', 3),
  tasks: sv('<rect x="3.5" y="4.5" width="17" height="16" rx="3.5"/><path d="M8 2.5v4M16 2.5v4M8.5 14l2.5 2.5 4.5-5"/>', 1.8),
  money: sv('<rect x="2.5" y="6" width="19" height="13" rx="3"/><circle cx="12" cy="12.5" r="2.6"/><path d="M6 9.5h.01M18 15.5h.01"/>', 1.8),
  plus:  sv('<path d="M12 5v14M5 12h14"/>', 2.6),
  chev:  '<svg viewBox="0 0 8 14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 1.5L6.5 7l-5 5.5"/></svg>',
  left:  '<svg viewBox="0 0 10 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2L2 8l6 6"/></svg>',
  right: '<svg viewBox="0 0 10 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 2l6 6-6 6"/></svg>',
  up:    sv('<path d="M12 19V5M6 11l6-6 6 6"/>', 2.6),
  down:  sv('<path d="M12 5v14M6 13l6 6 6-6"/>', 2.6),
  alert: sv('<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5h.01"/>', 2),
  ok:    sv('<circle cx="12" cy="12" r="9"/><path d="M8 12.5l3 3 5-6"/>', 2),
  clock: sv('<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>', 2),
  redo:  sv('<path d="M20 7v5h-5"/><path d="M20 12a8 8 0 10-2.3 5.6"/>', 2),
  trash: sv('<path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/>', 2),
  party: sv('<path d="M4 20l5-14 9 9z"/><path d="M14 4l.01 0M19 9l.01 0M17 3l-1 2M21 7l-2 1"/>', 2),
  doc:   sv('<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M10 13h6M10 17h4"/>', 2),
  wallet:sv('<path d="M3 7a2 2 0 012-2h13v4"/><rect x="3" y="7" width="18" height="13" rx="3"/><path d="M17 13.5h.01"/>', 2),
};
const CATS = {
  "Comida":        { c:"var(--k3)", d:'<path d="M7 3v8M5 3v5a2 2 0 004 0V3M7 11v10M17 21V3c-2.2 1.2-3.5 3.6-3.5 6.5 0 2.4 1.4 3.5 3.5 3.5"/>' },
  "Transporte":    { c:"var(--k1)",   d:'<rect x="5" y="3" width="14" height="14" rx="3"/><path d="M5 10h14M8 17v3M16 17v3M8.5 13.5h.01M15.5 13.5h.01"/>' },
  "Universidad":   { c:"var(--k2)", d:'<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2.4 9 2.4 12 0v-5M22 9v5"/>' },
  "Servicios":     { c:"var(--k5)", d:'<path d="M13 2L4.5 13.5H11L10 22l8.5-11.5H12z"/>' },
  "Salud":         { c:"var(--k4)",   d:'<path d="M12 20.5s-7.5-4.6-7.5-10.3A4.3 4.3 0 0112 7.6a4.3 4.3 0 017.5 2.6c0 5.7-7.5 10.3-7.5 10.3z"/>' },
  "Ocio":          { c:"var(--k5)", d:'<circle cx="12" cy="12" r="9"/><path d="M10 8.5l5.5 3.5-5.5 3.5z"/>' },
  "Ropa":          { c:"var(--k5)",   d:'<path d="M8.5 3L3 6l2 4.5 2.5-1V21h9V9.5l2.5 1L21 6l-5.5-3c-.7 1.6-2 2.5-3.5 2.5S9.2 4.6 8.5 3z"/>' },
  "Hogar":         { c:"var(--k3)",  d:'<path d="M3 11l9-7.5 9 7.5"/><path d="M5.5 9.5V20h13V9.5M10 20v-6h4v6"/>' },
  "Otros":         { c:"var(--k5)",   d:'<path d="M5.5 12h.01M12 12h.01M18.5 12h.01" stroke-width="3.2"/>' },
  "Mesada":        { c:"var(--k2)",  d:'<rect x="2.5" y="6" width="19" height="12" rx="2.5"/><circle cx="12" cy="12" r="2.6"/>' },
  "Trabajo":       { c:"var(--k1)",   d:'<rect x="3" y="7" width="18" height="13" rx="2.5"/><path d="M8.5 7V5a2 2 0 012-2h3a2 2 0 012 2v2M3 13h18"/>' },
  "Freelance":     { c:"var(--k4)",   d:'<rect x="4" y="4.5" width="16" height="11" rx="2"/><path d="M2 19.5h20"/>' },
  "Venta":         { c:"var(--k3)", d:'<path d="M3 12.5V4h8.5L21 13.5 13.5 21z"/><path d="M7.5 8h.01"/>' },
  "Beca":          { c:"var(--k5)", d:'<circle cx="12" cy="9" r="6"/><path d="M8.5 14L7 21.5l5-3 5 3-1.5-7.5"/>' },
  "Ahorro general":{ c:"var(--k1)",  d:'<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6M5 12v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>' },
  "Emergencias":   { c:"var(--k3)",    d:'<path d="M12 3l8 3v6c0 4.8-3.4 8-8 9-4.6-1-8-4.2-8-9V6z"/><path d="M12 9v5M9.5 11.5h5"/>' },
  "Meta personal": { c:"var(--k5)", d:'<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2"/>' },
};
const tile = (cat, lg = false) => { const k = CATS[cat] || CATS.Otros; return `<span class="tile${lg?" lg":""}" style="--tc:${k.c}">${sv(k.d, 2)}</span>`; };
const iconTile = (svg, color, lg = false) => `<span class="tile${lg?" lg":""}" style="--tc:${color}">${svg}</span>`;

const PRIO = { baja:{t:"Baja",c:"var(--p6)"}, media:{t:"Media",c:"var(--orange)"}, alta:{t:"Alta",c:"var(--red)"} };
const TYPES = {
  gasto:   { t:"Gasto",   sign:-1, cls:"out",  cats:["Comida","Transporte","Universidad","Servicios","Salud","Ocio","Ropa","Hogar","Otros"] },
  ingreso: { t:"Ingreso", sign: 1, cls:"in",   cats:["Mesada","Trabajo","Freelance","Venta","Beca","Otros"] },
  ahorro:  { t:"Ahorro",  sign:-1, cls:"save", cats:["Ahorro general","Emergencias","Meta personal"] },
  retiro:  { t:"Retiro",  sign: 1, cls:"wd",   cats:["Ahorro general","Emergencias","Meta personal"] }
};

/* ==================== CAPA DE DATOS ==================== */
// Modo DEMO: usuarios y datos en localStorage (solo este navegador)
const LocalBackend = {
  async init(){},
  _users(){ try{ return JSON.parse(localStorage.getItem("pd_users")||"{}"); }catch{ return {}; } },
  session(){ const e = localStorage.getItem("pd_session"); return e ? { email:e } : null; },
  async signIn(email, pass){
    const u = this._users()[email];
    if(!u || u !== await sha256(email+":"+pass)) throw new Error("Correo o contraseña incorrectos.");
    localStorage.setItem("pd_session", email); return { email };
  },
  async signUp(email, pass){
    const users = this._users();
    if(users[email]) throw new Error("Ese correo ya está registrado.");
    users[email] = await sha256(email+":"+pass);
    localStorage.setItem("pd_users", JSON.stringify(users));
    localStorage.setItem("pd_session", email);
    return { email, logged:true };
  },
  async reset(){ throw new Error("En modo demo no se envían correos. Configura Supabase para recuperar contraseñas."); },
  async signOut(){ localStorage.removeItem("pd_session"); },
  _key(t){ return `pd_${t}_${this.session().email}`; },
  _read(t){ try{ return JSON.parse(localStorage.getItem(this._key(t))||"[]"); }catch{ return []; } },
  _write(t, rows){ localStorage.setItem(this._key(t), JSON.stringify(rows)); },
  async list(t){ return this._read(t); },
  async insert(t, row){ const r = { id:uid(), created_at:new Date().toISOString(), ...row }; const rows=this._read(t); rows.push(r); this._write(t,rows); return r; },
  async update(t, id, patch){ const rows=this._read(t); const i=rows.findIndex(r=>r.id===id); if(i<0) return; rows[i]={...rows[i],...patch}; this._write(t,rows); return rows[i]; },
  async remove(t, id){ this._write(t, this._read(t).filter(r=>r.id!==id)); }
};

// Modo real: Supabase (Auth + Postgres con RLS)
const SupaBackend = {
  sb:null, user:null,
  async init(){
    await new Promise((res, rej) => { const s=document.createElement("script"); s.src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"; s.onload=res; s.onerror=()=>rej(new Error("No se pudo cargar Supabase. Revisa tu conexión.")); document.head.appendChild(s); });
    this.sb = window.supabase.createClient(CFG.SUPABASE_URL, CFG.SUPABASE_ANON_KEY);
    const { data } = await this.sb.auth.getSession();
    this.user = data.session?.user || null;
    this.sb.auth.onAuthStateChange((ev, session) => {
      this.user = session?.user || null;
      if(ev === "PASSWORD_RECOVERY") UI.renderAuth("newpass");
    });
  },
  session(){ return this.user ? { email:this.user.email } : null; },
  async signIn(email, password){
    const { data, error } = await this.sb.auth.signInWithPassword({ email, password });
    if(error){
      if(/confirm/i.test(error.message)) throw new Error("Aún no confirmas tu correo. Revisa tu bandeja de entrada.");
      throw new Error(/invalid/i.test(error.message) ? "Correo o contraseña incorrectos." : error.message);
    }
    this.user = data.user; return { email };
  },
  async signUp(email, password){
    const { data, error } = await this.sb.auth.signUp({ email, password, options:{ emailRedirectTo: location.origin } });
    if(error) throw new Error(error.message);
    this.user = data.session ? data.user : null;
    return { email, logged: !!data.session };
  },
  async reset(email){
    const { error } = await this.sb.auth.resetPasswordForEmail(email, { redirectTo: location.origin });
    if(error) throw new Error(error.message);
  },
  async newPassword(password){
    const { error } = await this.sb.auth.updateUser({ password });
    if(error) throw new Error(error.message);
  },
  async signOut(){ await this.sb.auth.signOut(); this.user=null; },
  async list(t){ const { data, error } = await this.sb.from(t).select("*"); if(error) throw error; return data; },
  async insert(t, row){ const { data, error } = await this.sb.from(t).insert(row).select().single(); if(error) throw error; return data; },
  async update(t, id, patch){ const { data, error } = await this.sb.from(t).update(patch).eq("id", id).select().single(); if(error) throw error; return data; },
  async remove(t, id){ const { error } = await this.sb.from(t).delete().eq("id", id); if(error) throw error; }
};

const DB = DEMO ? LocalBackend : SupaBackend;

/* ==================== ESTADO ==================== */
const S = {
  view: localStorage.getItem("pd_view") || "tareas",
  filter: "hoy",
  month: today().slice(0,7),
  tasks: [], movs: []
};

/* ==================== HOJAS, ALERTAS Y AVISOS (iOS) ==================== */
const Sheet = {
  stack: [],
  open(html, { label = "Hoja", onClose } = {}){
    const layer = document.createElement("div");
    layer.className = "layer";
    layer.innerHTML = `<div class="scrim"></div><section class="sheet" role="dialog" aria-modal="true" aria-label="${esc(label)}"><div class="grab" aria-hidden="true"></div>${html}</section>`;
    document.body.appendChild(layer);
    document.body.classList.add("sheet-open");
    const entry = { layer, onClose, prevFocus: document.activeElement };
    this.stack.push(entry);
    requestAnimationFrame(() => requestAnimationFrame(() => layer.classList.add("in")));
    $(".scrim", layer).onclick = () => this.close();
    this._drag(layer);
    setTimeout(() => { const f = $("[autofocus]", layer) || $(".sheet", layer); f.focus({ preventScroll:true }); }, reduceMotion() ? 0 : 320);
    return layer;
  },
  close(){
    const e = this.stack.pop(); if(!e) return;
    e.layer.classList.remove("in");
    const sheet = $(".sheet", e.layer); sheet.style.transform = "";
    if(!this.stack.length) document.body.classList.remove("sheet-open");
    setTimeout(() => e.layer.remove(), reduceMotion() ? 0 : 480);
    e.onClose && e.onClose();
    e.prevFocus && e.prevFocus.focus && e.prevFocus.focus({ preventScroll:true });
  },
  closeAll(){ while(this.stack.length) this.close(); },
  _drag(layer){
    const sheet = $(".sheet", layer);
    const handles = [$(".grab", layer), $(".sheet-bar", layer)].filter(Boolean);
    let y0 = null, dy = 0, t0 = 0;
    const move = e => { if(y0 === null) return; dy = Math.max(0, e.clientY - y0); sheet.style.transform = `translateY(${dy}px)`; $(".scrim", layer).style.opacity = String(Math.max(0, 1 - dy / 400)); };
    const up = () => {
      if(y0 === null) return;
      sheet.classList.remove("dragging"); $(".scrim", layer).style.opacity = "";
      const fast = dy / Math.max(1, Date.now() - t0) > .5;
      if(dy > 120 || (fast && dy > 40)) this.close(); else sheet.style.transform = "";
      y0 = null; dy = 0;
      removeEventListener("pointermove", move); removeEventListener("pointerup", up); removeEventListener("pointercancel", up);
    };
    handles.forEach(h => h.addEventListener("pointerdown", e => {
      if(innerWidth >= 768 || e.target.closest("button")) return;
      y0 = e.clientY; t0 = Date.now(); sheet.classList.add("dragging");
      addEventListener("pointermove", move); addEventListener("pointerup", up); addEventListener("pointercancel", up);
    }));
  }
};

function bar(title, { left = "Cancelar", right = null, rightId = "save", rightDisabled = false } = {}){
  return `<header class="sheet-bar">
    <button type="button" class="bar-btn" data-close>${esc(left)}</button>
    <h2>${esc(title)}</h2>
    ${right ? `<button type="submit" class="bar-btn end" id="${rightId}" ${rightDisabled?"disabled":""}>${esc(right)}</button>` : "<span></span>"}
  </header>`;
}

function confirmAlert({ title, message = "", confirm = "Eliminar", destructive = true }){
  return new Promise(resolve => {
    const layer = document.createElement("div");
    layer.className = "layer alert-layer";
    layer.innerHTML = `<div class="scrim"></div><div class="alert" role="alertdialog" aria-modal="true" aria-labelledby="al-t">
      <div class="alert-body"><h2 id="al-t">${esc(title)}</h2>${message ? `<p>${esc(message)}</p>` : ""}</div>
      <div class="alert-actions"><button type="button" data-a="0">Cancelar</button><button type="button" data-a="1" class="${destructive?"destructive":""}">${esc(confirm)}</button></div></div>`;
    document.body.appendChild(layer);
    requestAnimationFrame(() => requestAnimationFrame(() => layer.classList.add("in")));
    const done = v => { layer.classList.remove("in"); setTimeout(() => layer.remove(), 250); document.removeEventListener("keydown", key, true); resolve(v); };
    const key = e => { if(e.key === "Escape"){ e.stopPropagation(); done(false); } };
    document.addEventListener("keydown", key, true);
    $$("[data-a]", layer).forEach(b => b.onclick = () => done(b.dataset.a === "1"));
    $("[data-a='0']", layer).focus();
  });
}

let toastTimer;
function toast(text, kind = "ok"){
  $(".toast")?.remove(); clearTimeout(toastTimer);
  const colors = { ok:"var(--green)", info:"var(--k2)", warn:"var(--orange)", err:"var(--red)" };
  const icons = { ok:I.check, info:I.check, warn:I.redo, err:I.alert };
  const el = document.createElement("div");
  el.className = "toast glass"; el.setAttribute("role","status");
  el.innerHTML = `${iconTile(icons[kind], colors[kind])}<span>${esc(text)}</span>`;
  document.body.appendChild(el);
  requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add("in")));
  toastTimer = setTimeout(() => { el.classList.remove("in"); setTimeout(() => el.remove(), 500); }, 2200);
}

/* ==================== UI ==================== */
const UI = {
  /* ---------- AUTH ---------- */
  renderAuth(mode = "login", msg = null){
    document.body.dataset.view = "tareas";
    const T = {
      login:   ["Echo", "Tus pendientes y tu dinero, en un solo lugar.", "Iniciar sesión"],
      register:["Crea tu cuenta", "Solo necesitas un correo y una contraseña.", "Crear cuenta"],
      reset:   ["Recuperar contraseña", "Te enviaremos un enlace a tu correo.", "Enviar enlace"],
      newpass: ["Nueva contraseña", "Escribe la contraseña que usarás desde ahora.", "Guardar contraseña"]
    }[mode];
    $("#root").innerHTML = `
    <main class="auth"><form class="auth-card" id="authForm" novalidate>
      <div class="auth-head">
        <div class="app-icon">${I.logo.replace('stroke="currentColor"','stroke="#eaffff"')}</div>
        <h1>${T[0]}</h1><p>${T[1]}</p>
        ${DEMO ? '<span class="demo-pill">Modo demo · datos solo en este navegador</span>' : ""}
      </div>
      ${msg ? `<div class="msg glass ${msg.ok?"ok":"err"}" role="${msg.ok?"status":"alert"}">${msg.ok?I.ok:I.alert}<span>${esc(msg.t)}</span></div>` : ""}
      <div class="group glass">
        ${mode !== "newpass" ? `<div class="field"><label for="f-email">Correo</label><input id="f-email" type="email" name="email" autocomplete="email" inputmode="email" placeholder="tu@correo.com" required autofocus></div>` : ""}
        ${mode !== "reset" ? `<div class="field"><label for="f-pass">Contraseña</label><input id="f-pass" type="password" name="pass" autocomplete="${mode==="login"?"current-password":"new-password"}" placeholder="${mode==="login"?"Obligatoria":"Mínimo 6 caracteres"}" required minlength="6" ${mode==="newpass"?"autofocus":""}></div>` : ""}
        ${mode === "register" ? `<div class="field"><label for="f-pass2">Repetir</label><input id="f-pass2" type="password" name="pass2" autocomplete="new-password" placeholder="Repite la contraseña" required></div>` : ""}
      </div>
      <button class="btn btn-tint" type="submit">${T[2]}</button>
      <div class="auth-links">
        ${mode==="login" ? `<a href="#" class="link" data-mode="register">Crear cuenta</a><a href="#" class="link" data-mode="reset">¿Olvidaste tu contraseña?</a>` : `<a href="#" class="link" data-mode="login">Ya tengo cuenta</a>`}
      </div>
    </form></main>`;
    $$("[data-mode]").forEach(a => a.onclick = e => { e.preventDefault(); UI.renderAuth(a.dataset.mode); });
    $("#authForm").onsubmit = async e => {
      e.preventDefault();
      const f = new FormData(e.target);
      const email = (f.get("email")||"").trim().toLowerCase(), pass = f.get("pass")||"";
      const btn = e.target.querySelector("button[type=submit]"); btn.disabled = true; btn.textContent = "Un momento…";
      try{
        if(mode !== "newpass" && !/^\S+@\S+\.\S+$/.test(email)) throw new Error("Escribe un correo válido, por ejemplo tu@correo.com.");
        if(mode !== "reset" && pass.length < 6) throw new Error("La contraseña necesita al menos 6 caracteres.");
        if(mode === "login"){ await DB.signIn(email, pass); return App.start(); }
        if(mode === "register"){
          if(pass !== f.get("pass2")) throw new Error("Las contraseñas no coinciden.");
          const r = await DB.signUp(email, pass);
          if(r.logged) return App.start();
          return UI.renderAuth("login", { ok:true, t:"Cuenta creada. Abre el enlace que te enviamos al correo y luego inicia sesión." });
        }
        if(mode === "reset"){ await DB.reset(email); return UI.renderAuth("login", { ok:true, t:"Listo. Revisa tu correo para cambiar la contraseña." }); }
        if(mode === "newpass"){ await DB.newPassword(pass); history.replaceState(null,"",location.pathname); await App.start(); toast("Contraseña actualizada"); }
      }catch(err){
        const m = /fetch/i.test(err.message) ? "No hay conexión con el servidor. Revisa tu internet e inténtalo de nuevo." : err.message;
        UI.renderAuth(mode, { t: m || "Algo salió mal. Inténtalo de nuevo." });
      }
    };
  },

  /* ---------- SHELL ---------- */
  renderShell(){
    const email = DB.session()?.email || "";
    document.body.dataset.view = S.view;
    $("#root").innerHTML = `
    <div class="shell" id="shell">
      <div class="page">
        <header class="topbar">
          <div class="brand"><span class="brand-mark">${I.logo.replace('stroke="currentColor"','stroke="#eaffff"')}</span>Echo</div>
          <button class="avatar glass" id="account" aria-label="Cuenta: ${esc(email)}">${esc((email[0]||"?").toUpperCase())}</button>
        </header>
        <main id="main"></main>
      </div>
    </div>
    <nav class="dock" aria-label="Secciones">
      <div class="tabbar glass" role="tablist">
        <button class="tab" role="tab" data-view="tareas" aria-selected="${S.view==="tareas"}">${I.tasks}<span>Tareas</span></button>
        <button class="tab" role="tab" data-view="finanzas" aria-selected="${S.view==="finanzas"}">${I.money}<span>Finanzas</span></button>
      </div>
      <button class="add" id="add" aria-label="${S.view==="tareas"?"Nueva tarea":"Nuevo movimiento"} (N)">${I.plus}</button>
    </nav>`;
    $$("[data-view]").forEach(b => b.onclick = () => {
      if(S.view === b.dataset.view) return;
      S.view = b.dataset.view; localStorage.setItem("pd_view", S.view);
      UI.renderShell(); scrollTo({ top:0 });
    });
    $("#add").onclick = () => S.view === "tareas" ? UI.taskForm() : UI.movForm();
    $("#account").onclick = () => UI.accountSheet(email);
    S.view === "tareas" ? UI.renderTasks() : UI.renderFinance();
  },

  accountSheet(email){
    const layer = Sheet.open(`${bar("Cuenta", { left:"Listo" })}
      <div class="sheet-body">
        <ul class="group"><li><div class="cell"><span class="avatar glass">${esc((email[0]||"?").toUpperCase())}</span><div class="cell-main"><div class="l1">${esc(email)}</div><div class="l2">${DEMO ? "Modo demo · datos en este navegador" : "Sesión iniciada"}</div></div></div></li></ul>
        <ul class="group" style="margin-top:22px"><li><button class="opt danger" id="logout">Cerrar sesión</button></li></ul>
      </div>`, { label:"Cuenta" });
    $("[data-close]", layer).onclick = () => Sheet.close();
    $("#logout", layer).onclick = async () => {
      const ok = await confirmAlert({ title:"¿Cerrar sesión?", message:"Podrás volver a entrar con tu correo y contraseña.", confirm:"Cerrar sesión" });
      if(!ok) return;
      Sheet.closeAll(); await DB.signOut(); UI.renderAuth("login");
    };
  },

  /* ---------- TAREAS ---------- */
  renderTasks(){
    const t = today();
    const tasks = S.tasks;
    const open = tasks.filter(x => x.status !== "hecha");
    const late = open.filter(x => x.due_date && x.due_date < t);
    const todayList = tasks.filter(x => x.due_date === t);
    const todayDone = todayList.filter(x => x.status === "hecha").length;
    const doneWeek = tasks.filter(x => x.status === "hecha" && (x.done_at||"").slice(0,10) >= addDays(t, -6)).length;
    const pct = todayList.length ? Math.round(todayDone / todayList.length * 100) : 0;

    const filters = {
      hoy:        { t:"Hoy",         list: open.filter(x => !x.due_date || x.due_date <= t).concat(todayList.filter(x=>x.status==="hecha")) },
      pendientes: { t:"Pendientes",  list: open },
      postergadas:{ t:"Postergadas", list: open.filter(x => x.status === "postergada") },
      hechas:     { t:"Hechas",      list: tasks.filter(x => x.status === "hecha") },
      todas:      { t:"Todas",       list: tasks }
    };
    const cur = filters[S.filter];
    const hour = new Date().getHours();
    const greet = hour < 12 ? "Buenos días" : hour < 19 ? "Buenas tardes" : "Buenas noches";

    $("#main").innerHTML = `
      <div class="large-title"><h1>Tareas</h1><p>${greet} · ${fmtLong(t)}</p></div>
      <div class="tasks-wrap">
        <section class="summary glass" aria-label="Resumen de hoy">
          <div class="sum-top"><h2>Progreso de hoy</h2><strong class="num">${todayDone}<small>/${todayList.length}</small></strong></div>
          <div class="meter" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${pct}" aria-label="Tareas de hoy completadas"><span style="width:${pct}%"></span></div>
          <div class="cells3">
            <div><b class="num">${open.length}</b><span>Pendientes</span></div>
            <div class="${late.length?"is-late":""}"><b class="num">${late.length}</b><span>Atrasadas</span></div>
            <div class="is-ok"><b class="num">${doneWeek}</b><span>Hechas (7 d)</span></div>
          </div>
        </section>
        <div>
          <div class="chips" role="toolbar" aria-label="Filtrar tareas">${Object.entries(filters).map(([k,v]) => `<button class="chip glass" data-f="${k}" aria-pressed="${S.filter===k}">${v.t}<span class="n">${v.list.length}</span></button>`).join("")}</div>
          <div id="taskList">${UI.taskGroups(cur.list, S.filter)}</div>
        </div>
      </div>`;
    $$("[data-f]").forEach(b => b.onclick = () => { S.filter = b.dataset.f; UI.renderTasks(); });
    $$("[data-task]").forEach(b => b.onclick = () => UI.taskSheet(b.dataset.task));
    $$("[data-toggle]").forEach(b => b.onclick = async () => {
      const x = S.tasks.find(t => t.id === b.dataset.toggle); if(!x) return;
      const done = x.status !== "hecha";
      await App.saveTask(x.id, { status: done ? "hecha" : "pendiente", done_at: done ? new Date().toISOString() : null });
      toast(done ? "Tarea completada" : "Marcada como pendiente", done ? "ok" : "info");
    });
    $$("[data-empty-add]").forEach(b => b.onclick = () => UI.taskForm());
  },

  taskGroups(list, filter){
    if(!list.length){
      const e = {
        hoy:["Nada pendiente para hoy","Puedes adelantar algo de la semana.", I.party, "var(--green)"],
        hechas:["Aún no completas tareas","Toca el círculo de una tarea para marcarla como hecha.", I.check, "var(--green)"],
        postergadas:["Nada postergado","Vas al día con tus tareas.", I.clock, "var(--orange)"]
      }[filter] || ["Sin tareas aquí","Agrega tu primera tarea con el botón +.", I.doc, "var(--k2)"];
      return `<div class="empty glass" style="margin-top:16px"><span class="ico" style="background:${e[3]}">${e[2]}</span><b>${e[0]}</b>${e[1]}</div>`;
    }
    const t = today(), prioN = { alta:0, media:1, baja:2 };
    const sorted = [...list].sort((a,b) => (a.status==="hecha") - (b.status==="hecha") || (a.due_date||"9999").localeCompare(b.due_date||"9999") || (prioN[a.priority]??1) - (prioN[b.priority]??1));
    if(filter === "hechas"){
      return `<ul class="group glass" style="margin-top:16px">${sorted.sort((a,b)=>(b.done_at||"").localeCompare(a.done_at||"")).map(UI.taskRow).join("")}</ul>`;
    }
    const groups = [["late","Atrasadas"],["today","Hoy"],["tomorrow","Mañana"],["week","Esta semana"],["later","Más adelante"],["none","Sin fecha"],["done","Completadas"]];
    const key = x => {
      if(x.status === "hecha") return "done";
      if(!x.due_date) return "none";
      const d = diffDays(x.due_date, t);
      return d < 0 ? "late" : d === 0 ? "today" : d === 1 ? "tomorrow" : d < 7 ? "week" : "later";
    };
    return groups.map(([k, title]) => {
      const items = sorted.filter(x => key(x) === k);
      return items.length ? `<h3 class="group-h ${k==="late"?"late":""}"><span>${title}</span><span class="num">${items.length}</span></h3><ul class="group glass">${items.map(UI.taskRow).join("")}</ul>` : "";
    }).join("");
  },

  taskRow(x){
    const dl = x.status !== "hecha" ? dueLabel(x.due_date) : null;
    const p = PRIO[x.priority];
    const meta = [
      dl ? `<span class="${dl.c}">${dl.t}</span>` : "",
      x.status==="hecha" ? `<span class="done">Hecha${x.done_at?" · "+fmtShort(x.done_at.slice(0,10)):""}</span>` : "",
      x.status==="postergada" ? `<span class="post">Postergada ${x.postponed_count||1}×</span>` : "",
      p && x.status !== "hecha" ? `<span class="prio" style="--pc:${p.c}">${p.t}</span>` : "",
      x.notes ? `<span>Con nota</span>` : ""
    ].filter(Boolean).join("");
    return `<li class="task st-${x.status}">
      <button class="check" data-toggle="${x.id}" aria-label="${x.status==="hecha"?"Marcar como pendiente":"Marcar como hecha"}: ${esc(x.title)}"><i>${I.check}</i></button>
      <button class="row-main" data-task="${x.id}">
        <span class="row-text"><span class="t-title" style="display:block">${esc(x.title)}</span>${meta ? `<span class="t-meta">${meta}</span>` : ""}</span>
        <span class="chev">${I.chev}</span>
      </button></li>`;
  },

  taskSheet(id){
    const x = S.tasks.find(t => t.id === id); if(!x) return;
    const dl = dueLabel(x.due_date);
    const base = x.due_date && x.due_date > today() ? x.due_date : today();
    const st = (key, label, svg, color) => `<li><button class="opt" data-st="${key}">${iconTile(svg, color)}<span>${label}</span>${x.status===key?`<span class="tick">${I.check}</span>`:""}</button></li>`;
    const layer = Sheet.open(`${bar("Tarea", { left:"Cerrar", right:"Editar", rightId:"edit" })}
      <div class="sheet-body">
        <div class="detail">
          <h3>${esc(x.title)}</h3>
          <div class="t-meta">
            <span class="${x.status!=="hecha"&&dl?dl.c:""}">${x.due_date ? fmtLong(x.due_date) : "Sin fecha"}</span>
            ${PRIO[x.priority] ? `<span class="prio" style="--pc:${PRIO[x.priority].c}">Prioridad ${PRIO[x.priority].t.toLowerCase()}</span>` : ""}
            ${x.postponed_count ? `<span class="post">Postergada ${x.postponed_count} ${x.postponed_count===1?"vez":"veces"}</span>` : ""}
          </div>
          ${x.notes ? `<p class="note">${esc(x.notes)}</p>` : ""}
        </div>
        <h4 class="group-h">Estado</h4>
        <ul class="group">
          ${st("hecha","Hecha", I.check, "var(--green)")}
          ${st("pendiente","Pendiente", I.clock, "var(--k3)")}
          <li><button class="opt" id="pp-toggle" aria-expanded="false">${iconTile(I.redo, "var(--orange)")}<span>Postergar</span>${x.status==="postergada"?`<span class="tick">${I.check}</span>`:`<span class="sub">${I.chev}</span>`}</button></li>
        </ul>
        <div id="pp" class="hidden">
          <h4 class="group-h">Postergar para</h4>
          <ul class="group">
            <li><button class="opt" data-pp="1"><span>Mañana</span><span class="sub">${fmtShort(addDays(base,1))}</span></button></li>
            <li><button class="opt" data-pp="3"><span>En 3 días</span><span class="sub">${fmtShort(addDays(base,3))}</span></button></li>
            <li><button class="opt" data-pp="7"><span>En una semana</span><span class="sub">${fmtShort(addDays(base,7))}</span></button></li>
            <li><div class="field"><label for="ppDate">Otra fecha</label><input type="date" id="ppDate" min="${addDays(today(),1)}" value="${addDays(base,1)}"></div></li>
          </ul>
        </div>
        <ul class="group" style="margin-top:22px"><li><button class="opt danger" id="del">Eliminar tarea</button></li></ul>
      </div>`, { label:"Tarea" });
    $("[data-close]", layer).onclick = () => Sheet.close();
    $("#edit", layer).onclick = () => { Sheet.close(); setTimeout(() => UI.taskForm(x), reduceMotion() ? 0 : 200); };
    const postpone = async date => {
      await App.saveTask(x.id, { status:"postergada", due_date:date, postponed_count:(x.postponed_count||0)+1, done_at:null });
      Sheet.close(); toast(`Postergada para ${dueLabel(date).t.toLowerCase()}`, "warn");
    };
    $$("[data-st]", layer).forEach(b => b.onclick = async () => {
      const s = b.dataset.st;
      await App.saveTask(x.id, { status:s, done_at: s==="hecha" ? new Date().toISOString() : null });
      Sheet.close(); toast(s==="hecha" ? "Tarea completada" : "Marcada como pendiente", s==="hecha" ? "ok" : "info");
    });
    $("#pp-toggle", layer).onclick = e => { const pp = $("#pp", layer); pp.classList.toggle("hidden"); e.currentTarget.setAttribute("aria-expanded", String(!pp.classList.contains("hidden"))); };
    $$("[data-pp]", layer).forEach(b => b.onclick = () => postpone(addDays(base, +b.dataset.pp)));
    $("#ppDate", layer).onchange = e => { if(e.target.value) postpone(e.target.value); };
    $("#del", layer).onclick = async () => {
      const ok = await confirmAlert({ title:"¿Eliminar esta tarea?", message:"No podrás deshacer esta acción." });
      if(!ok) return;
      await App.delTask(x.id); Sheet.close(); toast("Tarea eliminada", "err");
    };
  },

  taskForm(x = null){
    let prio = x?.priority || "media";
    const due = x ? (x.due_date || "") : today();
    const layer = Sheet.open(`<form id="tf" novalidate>${bar(x ? "Editar tarea" : "Nueva tarea", { right: x ? "Guardar" : "Agregar", rightDisabled: !x })}
      <div class="sheet-body">
        <ul class="group">
          <li><div class="field"><input name="title" aria-label="Título" required maxlength="140" placeholder="¿Qué tienes pendiente?" value="${esc(x?.title||"")}" autofocus autocomplete="off"></div></li>
          <li><div class="field"><textarea name="notes" aria-label="Nota" maxlength="500" placeholder="Nota (opcional)">${esc(x?.notes||"")}</textarea></div></li>
        </ul>
        <h4 class="group-h">Fecha límite</h4>
        <ul class="group">
          <li><div class="field"><label for="f-due">Fecha</label><input type="date" id="f-due" name="due" value="${due}"></div></li>
          <li><div class="quick" style="padding-top:12px">
            <button type="button" data-d="0">Hoy</button><button type="button" data-d="1">Mañana</button><button type="button" data-d="7">En una semana</button><button type="button" data-d="">Sin fecha</button>
          </div></li>
        </ul>
        <h4 class="group-h">Prioridad</h4>
        <div class="seg lg" role="group" aria-label="Prioridad">${Object.entries(PRIO).map(([k,v]) => `<button type="button" data-p="${k}" aria-pressed="${prio===k}">${v.t}</button>`).join("")}</div>
      </div></form>`, { label: x ? "Editar tarea" : "Nueva tarea" });
    const f = $("#tf", layer), save = $("#save", layer);
    f.title.addEventListener("input", () => { save.disabled = !f.title.value.trim(); });
    $("[data-close]", layer).onclick = () => Sheet.close();
    $$("[data-d]", layer).forEach(b => b.onclick = () => { f.due.value = b.dataset.d === "" ? "" : addDays(today(), +b.dataset.d); });
    $$("[data-p]", layer).forEach(b => b.onclick = () => { prio = b.dataset.p; $$("[data-p]", layer).forEach(o => o.setAttribute("aria-pressed", String(o === b))); });
    f.onsubmit = async e => {
      e.preventDefault();
      const title = f.title.value.trim(); if(!title) return f.title.focus();
      save.disabled = true;
      const row = { title, due_date: f.due.value || null, priority: prio, notes: f.notes.value.trim() || null };
      if(x) await App.saveTask(x.id, row);
      else await App.addTask({ ...row, status:"pendiente", postponed_count:0 });
      Sheet.close(); toast(x ? "Tarea actualizada" : "Tarea agregada");
    };
  },

  /* ---------- FINANZAS ---------- */
  renderFinance(){
    const m = S.month, all = S.movs;
    const inMonth = all.filter(x => x.date.slice(0,7) === m);
    const sum = (list, type) => list.filter(x => x.type === type).reduce((a,x) => a + Number(x.amount), 0);
    const savedTotal = sum(all,"ahorro") - sum(all,"retiro");
    const savedMonth = sum(inMonth,"ahorro") - sum(inMonth,"retiro");
    const inc = sum(inMonth,"ingreso"), exp = sum(inMonth,"gasto");
    // Disponible acumulado: todo lo que entró menos lo gastado y lo apartado al ahorro
    const available = sum(all,"ingreso") - sum(all,"gasto") - savedTotal;

    const cats = {};
    inMonth.filter(x => x.type === "gasto").forEach(x => cats[x.category] = (cats[x.category]||0) + Number(x.amount));
    const catList = Object.entries(cats).sort((a,b) => b[1]-a[1]);
    const maxCat = catList[0]?.[1] || 1;
    const [y, mo] = m.split("-").map(Number);
    const monthName = cap(new Date(y, mo-1, 1).toLocaleDateString("es-PE",{month:"long",year:"numeric"}));
    const byDay = {};
    [...inMonth].sort((a,b) => b.date.localeCompare(a.date) || (b.created_at||"").localeCompare(a.created_at||"")).forEach(x => (byDay[x.date] ||= []).push(x));
    const dayName = d => d === today() ? "Hoy" : d === addDays(today(),-1) ? "Ayer" : fmtLong(d);

    $("#main").innerHTML = `
      <div class="large-title"><h1>Finanzas</h1><p>Lo que ahorraste y lo que gastaste</p></div>
      <div class="month glass">
        <button class="round" data-m="-1" aria-label="Mes anterior">${I.left}</button>
        <b>${monthName}</b>
        <button class="round" data-m="1" aria-label="Mes siguiente">${I.right}</button>
      </div>
      <section class="balance glass" aria-label="Resumen">
        <h2>Total ahorrado</h2>
        <strong class="big num">${money(savedTotal)}</strong>
        <span class="delta num ${savedMonth<0?"neg":""}">${savedMonth<0?I.down:I.up}${money(Math.abs(savedMonth))} este mes</span>
        <div class="cells3">
          <div><b class="num" style="color:var(--green)">${money(inc)}</b><span>Ingresos</span></div>
          <div><b class="num">${money(exp)}</b><span>Gastado</span></div>
          <div><b class="num" style="color:${available<0?"var(--red)":"var(--label)"}">${money(available)}</b><span>Disponible</span></div>
        </div>
      </section>
      <div class="fin-grid">
        <section>
          <h3 class="group-h"><span>En qué gasté</span>${catList.length ? `<span class="num">${money(exp)}</span>` : ""}</h3>
          ${catList.length ? `<ul class="group glass">${catList.map(([c,v]) => `
            <li class="cat-row"><div class="cell">${tile(c)}
              <div class="cell-main"><div class="l1"><span>${esc(c)}</span><span class="amt num">${money(v)}</span></div>
                <div class="bar" role="img" aria-label="${Math.round(v/exp*100)}% del gasto"><span style="width:${v/maxCat*100}%;--tc:${(CATS[c]||CATS.Otros).c}"></span></div></div>
            </div></li>`).join("")}</ul>`
          : `<div class="empty glass"><span class="ico" style="background:var(--green)">${I.wallet}</span><b>Sin gastos este mes</b>Cuando registres uno, verás aquí en qué se va tu dinero.</div>`}
        </section>
        <section>
          ${inMonth.length ? Object.entries(byDay).map(([d, items]) => `
            <h3 class="group-h"><span>${dayName(d)}</span></h3>
            <ul class="group glass">${items.map(x => { const T = TYPES[x.type]; return `
              <li><button class="cell" data-mov="${x.id}">${tile(x.category)}
                <span class="cell-main"><span class="l1"><span class="mov-title">${esc(x.note || x.category)}</span><span class="amt num ${T.cls}">${T.sign>0?"+":"−"}${money(x.amount)}</span></span>
                <span class="l2" style="display:block">${T.t} · ${esc(x.category)}</span></span>
              </button></li>`; }).join("")}</ul>`).join("")
          : `<h3 class="group-h"><span>Movimientos</span></h3><div class="empty glass"><span class="ico" style="background:var(--k2)">${I.money}</span><b>Sin movimientos</b>Toca + para registrar un gasto, ingreso o ahorro.</div>`}
        </section>
      </div>`;
    $$("[data-m]").forEach(b => b.onclick = () => {
      const d = new Date(y, mo-1 + Number(b.dataset.m), 1); S.month = `${d.getFullYear()}-${pad(d.getMonth()+1)}`; UI.renderFinance();
    });
    $$("[data-mov]").forEach(b => b.onclick = () => UI.movSheet(b.dataset.mov));
  },

  movSheet(id){
    const x = S.movs.find(m => m.id === id); if(!x) return;
    const T = TYPES[x.type];
    const layer = Sheet.open(`${bar("Movimiento", { left:"Cerrar", right:"Editar", rightId:"edit" })}
      <div class="sheet-body">
        <div class="mov-hero">${tile(x.category, true)}
          <div class="big num amt ${T.cls}">${T.sign>0?"+":"−"}${money(x.amount)}</div>
          <p>${T.t} · ${esc(x.category)}</p>
        </div>
        <ul class="group" style="margin-top:18px">
          <li><div class="opt"><span>Fecha</span><span class="sub">${fmtLong(x.date)}</span></div></li>
          ${x.note ? `<li><div class="opt"><span>Descripción</span><span class="sub">${esc(x.note)}</span></div></li>` : ""}
        </ul>
        <ul class="group" style="margin-top:22px"><li><button class="opt danger" id="del">Eliminar movimiento</button></li></ul>
      </div>`, { label:"Movimiento" });
    $("[data-close]", layer).onclick = () => Sheet.close();
    $("#edit", layer).onclick = () => { Sheet.close(); setTimeout(() => UI.movForm(x), reduceMotion() ? 0 : 200); };
    $("#del", layer).onclick = async () => {
      const ok = await confirmAlert({ title:"¿Eliminar este movimiento?", message:"Se recalcularán tus totales." });
      if(!ok) return;
      await App.delMov(x.id); Sheet.close(); toast("Movimiento eliminado", "err");
    };
  },

  movForm(x = null){
    let type = x?.type || "gasto";
    const catOpts = t => TYPES[t].cats.map(c => `<option ${x?.category===c?"selected":""}>${c}</option>`).join("");
    const hints = { gasto:"Dinero que salió: comida, pasajes, copias…", ingreso:"Dinero que recibiste: mesada, pagos, trabajos.", ahorro:"Dinero que apartas de lo disponible hacia tu ahorro.", retiro:"Dinero que sacas de tu ahorro para usarlo." };
    const layer = Sheet.open(`<form id="mf" novalidate>${bar(x ? "Editar movimiento" : "Nuevo movimiento", { right: x ? "Guardar" : "Agregar", rightDisabled: !x })}
      <div class="sheet-body">
        <div class="seg" role="group" aria-label="Tipo de movimiento">${Object.entries(TYPES).map(([k,v]) => `<button type="button" data-t="${k}" aria-pressed="${type===k}">${v.t}</button>`).join("")}</div>
        <p class="hint" id="hint">${hints[type]}</p>
        <label class="amount-field"><span>${esc(CUR)}</span><input name="amount" type="number" inputmode="decimal" step="0.01" min="0.01" placeholder="0.00" aria-label="Monto" required value="${x?.amount ?? ""}" autofocus></label>
        <ul class="group">
          <li><div class="field"><label for="catSel">Categoría</label><select id="catSel" name="category">${catOpts(type)}</select></div></li>
          <li><div class="field"><label for="f-date">Fecha</label><input type="date" id="f-date" name="date" required value="${x?.date || today()}"></div></li>
          <li><div class="field"><input name="note" aria-label="Descripción" maxlength="120" placeholder="Descripción (opcional)" value="${esc(x?.note||"")}" autocomplete="off"></div></li>
        </ul>
      </div></form>`, { label: x ? "Editar movimiento" : "Nuevo movimiento" });
    const f = $("#mf", layer), save = $("#save", layer);
    const valid = () => { save.disabled = !(parseFloat(f.amount.value) > 0) || !f.date.value; };
    f.amount.addEventListener("input", valid); f.date.addEventListener("input", valid);
    $("[data-close]", layer).onclick = () => Sheet.close();
    $$("[data-t]", layer).forEach(b => b.onclick = () => {
      type = b.dataset.t; $$("[data-t]", layer).forEach(o => o.setAttribute("aria-pressed", String(o === b)));
      $("#catSel", layer).innerHTML = catOpts(type); $("#hint", layer).textContent = hints[type];
    });
    f.onsubmit = async e => {
      e.preventDefault();
      const amount = Math.round(parseFloat(f.amount.value) * 100) / 100;
      if(!(amount > 0)) return f.amount.focus();
      save.disabled = true;
      const row = { type, amount, category: f.category.value, date: f.date.value, note: f.note.value.trim() || null };
      if(x) await App.saveMov(x.id, row); else await App.addMov(row);
      S.month = row.date.slice(0,7);
      Sheet.close(); toast(x ? "Movimiento actualizado" : `${TYPES[type].t} registrado`);
    };
  }
};

/* ==================== APP ==================== */
const App = {
  async start(){
    $("#root").innerHTML = `<div class="loader"><div class="spinner" role="status" aria-label="Cargando"></div></div>`;
    try{
      const [tasks, movs] = await Promise.all([DB.list("tasks"), DB.list("transactions")]);
      S.tasks = tasks; S.movs = movs;
      UI.renderShell();
    }catch(err){
      console.error(err);
      $("#root").innerHTML = `<main class="auth"><div class="auth-card"><div class="empty glass"><span class="ico" style="background:var(--red)">${I.alert}</span><b>No se pudieron cargar tus datos</b>${esc(/fetch/i.test(err.message||"") ? "No hay conexión con el servidor." : (err.message||err))}<div style="margin-top:16px"><button class="btn btn-tint" onclick="location.reload()">Reintentar</button></div></div></div></main>`;
    }
  },
  rerender(){ if($("#main")) S.view === "tareas" ? UI.renderTasks() : UI.renderFinance(); },
  async wrap(fn){ try{ await fn(); App.rerender(); }catch(err){ console.error(err); toast("No se pudo guardar. Revisa tu conexión.", "err"); } },
  addTask(row){ return App.wrap(async () => S.tasks.push(await DB.insert("tasks", row))); },
  saveTask(id, patch){ return App.wrap(async () => { const r = await DB.update("tasks", id, patch); const i = S.tasks.findIndex(t=>t.id===id); S.tasks[i] = r || { ...S.tasks[i], ...patch }; }); },
  delTask(id){ return App.wrap(async () => { await DB.remove("tasks", id); S.tasks = S.tasks.filter(t => t.id !== id); }); },
  addMov(row){ return App.wrap(async () => S.movs.push(await DB.insert("transactions", row))); },
  saveMov(id, patch){ return App.wrap(async () => { const r = await DB.update("transactions", id, patch); const i = S.movs.findIndex(t=>t.id===id); S.movs[i] = r || { ...S.movs[i], ...patch }; }); },
  delMov(id){ return App.wrap(async () => { await DB.remove("transactions", id); S.movs = S.movs.filter(t => t.id !== id); }); }
};

// Atajos de teclado: N = nuevo, Esc = cerrar hoja
document.addEventListener("keydown", e => {
  if(e.key === "Escape" && Sheet.stack.length){ e.preventDefault(); Sheet.close(); return; }
  const typing = /INPUT|TEXTAREA|SELECT/.test(document.activeElement?.tagName);
  if(!typing && !Sheet.stack.length && $("#add") && (e.key === "n" || e.key === "N")){ e.preventDefault(); $("#add").click(); }
});

(async () => {
  try{ await DB.init(); }
  catch(err){ $("#root").innerHTML = `<main class="auth"><div class="auth-card"><div class="empty glass"><span class="ico" style="background:var(--red)">${I.alert}</span><b>Sin conexión</b>${esc(err.message)}</div></div></main>`; return; }
  if(location.hash.includes("type=recovery")) return UI.renderAuth("newpass");
  DB.session() ? App.start() : UI.renderAuth("login");
})();
})();
