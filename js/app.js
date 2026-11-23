(() => {
"use strict";
/* ==================== UTILIDADES ==================== */
const CFG = window.APP_CONFIG || {};
const CUR = CFG.MONEDA || "S/";
const DEMO = !(CFG.SUPABASE_URL && CFG.SUPABASE_ANON_KEY);
const $ = (s, el = document) => el.querySelector(s);
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
const fmtShort = s => parse(s).toLocaleDateString("es-PE",{day:"numeric",month:"short"});
function dueLabel(s){
  if(!s) return null;
  const d = diffDays(s, today());
  if(d < 0) return { t: d === -1 ? "Ayer" : `Hace ${-d} días`, c:"late" };
  if(d === 0) return { t:"Hoy", c:"today" };
  if(d === 1) return { t:"Mañana", c:"" };
  if(d < 7) return { t: cap(parse(s).toLocaleDateString("es-PE",{weekday:"long"})), c:"" };
  return { t: fmtShort(s), c:"" };
}
function toast(t){ const el=document.createElement("div"); el.className="toast"; el.textContent=t; document.body.appendChild(el); setTimeout(()=>el.remove(),2200); }
async function sha256(t){ const b = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(t)); return [...new Uint8Array(b)].map(x=>x.toString(16).padStart(2,"0")).join(""); }

const ICON = {
  check:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
  logo:'<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
  tasks:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2v4M16 2v4M3 10h18M8 15l2 2 4-4"/></svg>',
  money:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="13" rx="3"/><circle cx="12" cy="12.5" r="2.5"/><path d="M6 6V5a2 2 0 012-2h8a2 2 0 012 2v1"/></svg>',
  plus:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
  out:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17l5-5-5-5M20 12H9M12 21H6a2 2 0 01-2-2V5a2 2 0 012-2h6"/></svg>',
  left:'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M15 6l-6 6 6 6"/></svg>',
  right:'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M9 6l6 6-6 6"/></svg>',
  chev:'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6l6 6-6 6"/></svg>'
};
const PRIO = { alta:{t:"Alta",c:"var(--bad)"}, media:{t:"Media",c:"var(--warn)"}, baja:{t:"Baja",c:"var(--info)"} };
const TYPES = {
  gasto:   { t:"Gasto",   sign:-1, color:"var(--bad)",  bg:"rgba(255,107,107,.14)", cats:["Comida","Transporte","Universidad","Servicios","Salud","Ocio","Ropa","Hogar","Otros"] },
  ingreso: { t:"Ingreso", sign: 1, color:"var(--ok)",   bg:"rgba(62,207,142,.14)",  cats:["Mesada","Trabajo","Freelance","Venta","Beca","Otros"] },
  ahorro:  { t:"Ahorro",  sign:-1, color:"var(--accent)", bg:"rgba(124,156,255,.16)", cats:["Ahorro general","Emergencias","Meta personal"] },
  retiro:  { t:"Retiro",  sign: 1, color:"var(--warn)", bg:"rgba(245,177,76,.15)",  cats:["Ahorro general","Emergencias","Meta personal"] }
};
const CAT_EMOJI = { Comida:"🍽️",Transporte:"🚌",Universidad:"🎓",Servicios:"💡",Salud:"💊",Ocio:"🎮",Ropa:"👕",Hogar:"🏠",Otros:"📦",Mesada:"💵",Trabajo:"💼",Freelance:"💻",Venta:"🛒",Beca:"🏅","Ahorro general":"🐷",Emergencias:"🛟","Meta personal":"🎯" };
const CAT_COLORS = ["#7c9cff","#ff6b6b","#f5b14c","#3ecf8e","#a78bfa","#5cc8ff","#ff8fc7","#9be36d","#c9a27e"];

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
  async reset(){ throw new Error("En modo demo no hay envío de correos. Configura Supabase para recuperar contraseñas."); },
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
    await new Promise((res, rej) => { const s=document.createElement("script"); s.src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"; s.onload=res; s.onerror=()=>rej(new Error("No se pudo cargar Supabase")); document.head.appendChild(s); });
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
    if(error) throw new Error(error.message.includes("Invalid") ? "Correo o contraseña incorrectos." : error.message);
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

/* ==================== UI ==================== */
const UI = {
  /* ---------- AUTH ---------- */
  renderAuth(mode = "login", msg = null){
    const titles = {
      login:["Bienvenido a Echo","Inicia sesión para ver tus pendientes y finanzas."],
      register:["Crea tu cuenta","Organiza tus tareas y controla tu dinero."],
      reset:["Recuperar contraseña","Te enviaremos un enlace a tu correo."],
      newpass:["Nueva contraseña","Escribe tu nueva contraseña."]
    }[mode];
    $("#root").innerHTML = `
    <div class="auth"><form class="auth-card" id="authForm" novalidate>
      <div class="logo">${ICON.logo}</div>
      ${DEMO ? '<span class="demo-badge">MODO DEMO · datos solo en este navegador</span>' : ""}
      <h1>${titles[0]}</h1><p class="sub">${titles[1]}</p>
      ${msg ? `<div class="msg ${msg.ok?"ok":"err"}">${esc(msg.t)}</div>` : ""}
      ${mode !== "newpass" ? `<div class="field"><label>Correo</label><input class="input" type="email" name="email" autocomplete="email" placeholder="tucorreo@gmail.com" required></div>` : ""}
      ${mode !== "reset" ? `<div class="field"><label>Contraseña</label><input class="input" type="password" name="pass" autocomplete="${mode==="login"?"current-password":"new-password"}" placeholder="Mínimo 6 caracteres" required minlength="6"></div>` : ""}
      ${mode === "register" ? `<div class="field"><label>Repite la contraseña</label><input class="input" type="password" name="pass2" autocomplete="new-password" required></div>` : ""}
      <button class="btn btn-primary" type="submit">${{login:"Entrar",register:"Crear cuenta",reset:"Enviar enlace",newpass:"Guardar contraseña"}[mode]}</button>
      <div class="auth-foot">
        ${mode==="login" ? `<a href="#" class="link" data-mode="register">Crear cuenta</a><a href="#" class="link" data-mode="reset">¿Olvidaste tu contraseña?</a>` : `<a href="#" class="link" data-mode="login">← Volver a iniciar sesión</a>`}
      </div>
    </form></div>`;
    document.querySelectorAll("[data-mode]").forEach(a => a.onclick = e => { e.preventDefault(); UI.renderAuth(a.dataset.mode); });
    $("#authForm").onsubmit = async e => {
      e.preventDefault();
      const f = new FormData(e.target);
      const email = (f.get("email")||"").trim().toLowerCase(), pass = f.get("pass")||"";
      const btn = e.target.querySelector("button[type=submit]"); btn.disabled = true; btn.textContent = "Un momento…";
      try{
        if(mode !== "newpass" && !/^\S+@\S+\.\S+$/.test(email)) throw new Error("Escribe un correo válido.");
        if(mode !== "reset" && pass.length < 6) throw new Error("La contraseña debe tener al menos 6 caracteres.");
        if(mode === "login"){ await DB.signIn(email, pass); return App.start(); }
        if(mode === "register"){
          if(pass !== f.get("pass2")) throw new Error("Las contraseñas no coinciden.");
          const r = await DB.signUp(email, pass);
          if(r.logged) return App.start();
          return UI.renderAuth("login", { ok:true, t:"Cuenta creada. Revisa tu correo para confirmarla y luego inicia sesión." });
        }
        if(mode === "reset"){ await DB.reset(email); return UI.renderAuth("login", { ok:true, t:"Listo, revisa tu correo para cambiar la contraseña." }); }
        if(mode === "newpass"){ await DB.newPassword(pass); toast("Contraseña actualizada"); history.replaceState(null,"",location.pathname); return App.start(); }
      }catch(err){ UI.renderAuth(mode, { t: err.message || "Ocurrió un error." }); }
    };
  },

  /* ---------- SHELL ---------- */
  renderShell(){
    const email = DB.session()?.email || "";
    const tabs = `
      <button class="tab ${S.view==="tareas"?"active":""}" data-view="tareas">${ICON.tasks}<span>Tareas</span></button>
      <button class="tab ${S.view==="finanzas"?"active":""}" data-view="finanzas">${ICON.money}<span>Finanzas</span></button>`;
    $("#root").innerHTML = `
    <div class="app">
      <header class="top">
        <div class="logo">${ICON.logo}</div>
        <div class="brand"><b>Echo</b><span>${esc(email)}${DEMO?" · demo":""}</span></div>
        <nav class="tabs">${tabs}</nav>
        <button class="icon-btn" id="logout" title="Cerrar sesión">${ICON.out}</button>
      </header>
      <main id="main"></main>
    </div>
    <nav class="bottom-nav">${tabs}</nav>
    <button class="fab" id="fab" title="Agregar (N)">${ICON.plus}</button>`;
    document.querySelectorAll("[data-view]").forEach(b => b.onclick = () => { S.view = b.dataset.view; localStorage.setItem("pd_view", S.view); UI.renderShell(); });
    $("#logout").onclick = async () => { await DB.signOut(); UI.renderAuth("login"); };
    $("#fab").onclick = () => S.view === "tareas" ? UI.taskForm() : UI.movForm();
    S.view === "tareas" ? UI.renderTasks() : UI.renderFinance();
  },

  /* ---------- TAREAS ---------- */
  renderTasks(){
    const t = today();
    const tasks = S.tasks;
    const open = tasks.filter(x => x.status !== "hecha");
    const late = open.filter(x => x.due_date && x.due_date < t);
    const todayList = tasks.filter(x => x.due_date === t);
    const todayDone = todayList.filter(x => x.status === "hecha").length;
    const weekAgo = addDays(t, -6);
    const doneWeek = tasks.filter(x => x.status === "hecha" && (x.done_at||"").slice(0,10) >= weekAgo).length;
    const postponed = open.filter(x => x.status === "postergada");
    const pct = todayList.length ? Math.round(todayDone / todayList.length * 100) : 0;

    const filters = {
      hoy:        { t:"Hoy",         list: open.filter(x => !x.due_date || x.due_date <= t).concat(todayList.filter(x=>x.status==="hecha")) },
      pendientes: { t:"Pendientes",  list: open },
      postergadas:{ t:"Postergadas", list: postponed },
      hechas:     { t:"Hechas",      list: tasks.filter(x => x.status === "hecha") },
      todas:      { t:"Todas",       list: tasks }
    };
    const cur = filters[S.filter];
    const hour = new Date().getHours();
    const greet = hour < 12 ? "Buenos días" : hour < 19 ? "Buenas tardes" : "Buenas noches";

    $("#main").innerHTML = `
      <section class="hello"><h2>${greet} 👋</h2><p>${fmtLong(t)}</p></section>
      <section class="stats">
        <div class="stat hero"><small>Progreso de hoy</small><strong>${todayDone}/${todayList.length}</strong><div class="progress"><div style="width:${pct}%"></div></div></div>
        <div class="stat"><small><i style="background:var(--accent)"></i>Pendientes</small><strong>${open.length}</strong></div>
        <div class="stat"><small><i style="background:var(--bad)"></i>Atrasadas</small><strong>${late.length}</strong></div>
        <div class="stat"><small><i style="background:var(--ok)"></i>Hechas (7 días)</small><strong>${doneWeek}</strong></div>
      </section>
      <div class="chips">${Object.entries(filters).map(([k,v]) => `<button class="chip ${S.filter===k?"active":""}" data-f="${k}">${v.t}<span class="n">${v.list.length}</span></button>`).join("")}</div>
      <section id="taskList">${UI.taskGroups(cur.list, S.filter)}</section>`;
    document.querySelectorAll("[data-f]").forEach(b => b.onclick = () => { S.filter = b.dataset.f; UI.renderTasks(); });
    document.querySelectorAll("[data-task]").forEach(b => b.onclick = () => UI.taskSheet(b.dataset.task));
  },

  taskGroups(list, filter){
    if(!list.length){
      const e = { hoy:["🎉","Nada pendiente para hoy","Disfruta o adelanta algo de la semana."], hechas:["✅","Aún no completas tareas","Toca una tarea y márcala como hecha."], postergadas:["🗓️","Sin tareas postergadas","Vas al día."] }[filter] || ["📝","No hay tareas aquí","Toca + para agregar una nueva."];
      return `<div class="empty"><div class="big">${e[0]}</div><b>${e[1]}</b>${e[2]}</div>`;
    }
    const t = today(), prioN = { alta:0, media:1, baja:2 };
    const sorted = [...list].sort((a,b) => (a.status==="hecha") - (b.status==="hecha") || (a.due_date||"9999").localeCompare(b.due_date||"9999") || (prioN[a.priority]??1) - (prioN[b.priority]??1));
    if(filter === "hechas") return sorted.sort((a,b)=>(b.done_at||"").localeCompare(a.done_at||"")).map(UI.taskCard).join("");
    const groups = [["late","Atrasadas"],["today","Hoy"],["tomorrow","Mañana"],["week","Esta semana"],["later","Más adelante"],["none","Sin fecha"],["done","Completadas"]];
    const key = x => {
      if(x.status === "hecha") return "done";
      if(!x.due_date) return "none";
      const d = diffDays(x.due_date, t);
      return d < 0 ? "late" : d === 0 ? "today" : d === 1 ? "tomorrow" : d < 7 ? "week" : "later";
    };
    return groups.map(([k, title]) => {
      const items = sorted.filter(x => key(x) === k);
      return items.length ? `<div class="group-title ${k==="late"?"late":""}"><span>${title}</span><span>${items.length}</span></div>${items.map(UI.taskCard).join("")}` : "";
    }).join("");
  },

  taskCard(x){
    const dl = x.status !== "hecha" ? dueLabel(x.due_date) : null;
    const p = PRIO[x.priority];
    return `<button class="task st-${x.status}" data-task="${x.id}" style="--pcolor:${p?p.c:"transparent"}">
      <span class="check">${ICON.check}</span>
      <span class="t-body"><div class="t-title">${esc(x.title)}</div>
        <div class="t-meta">
          ${dl ? `<span class="tag ${dl.c}">${dl.t}</span>` : ""}
          ${x.status==="hecha" ? `<span class="tag done">Hecha${x.done_at?" · "+fmtShort(x.done_at.slice(0,10)):""}</span>` : ""}
          ${x.status==="postergada" ? `<span class="tag post">Postergada ×${x.postponed_count||1}</span>` : ""}
          ${p ? `<span class="tag">${p.t}</span>` : ""}
        </div></span>
      <span class="chev">${ICON.chev}</span></button>`;
  },

  taskSheet(id){
    const x = S.tasks.find(t => t.id === id); if(!x) return;
    const dl = dueLabel(x.due_date);
    UI.modal(`
      <h3>${esc(x.title)}</h3>
      <div class="t-meta">${x.due_date ? `<span class="tag ${x.status!=="hecha"&&dl?dl.c:""}">📅 ${fmtLong(x.due_date)}</span>` : `<span class="tag">Sin fecha</span>`}
        ${PRIO[x.priority] ? `<span class="tag">Prioridad ${PRIO[x.priority].t.toLowerCase()}</span>` : ""}
        ${x.postponed_count ? `<span class="tag post">Postergada ${x.postponed_count} ${x.postponed_count===1?"vez":"veces"}</span>` : ""}</div>
      ${x.notes ? `<p class="note">${esc(x.notes)}</p>` : ""}
      <div class="status-grid">
        <button class="st-btn ok ${x.status==="hecha"?"sel":""}" data-st="hecha"><span class="dot">✓</span>Hecha</button>
        <button class="st-btn pe ${x.status==="pendiente"?"sel":""}" data-st="pendiente"><span class="dot">○</span>Pendiente</button>
        <button class="st-btn po ${x.status==="postergada"?"sel":""}" data-st="postergada"><span class="dot">↷</span>Postergar</button>
      </div>
      <div class="postpone hidden" id="pp">
        <p>¿Para cuándo la postergas?</p>
        <div class="pp-opts">
          <button data-pp="1">Mañana</button><button data-pp="3">+3 días</button><button data-pp="7">+1 semana</button>
        </div>
        <div class="row"><input class="input" type="date" id="ppDate" value="${addDays(x.due_date && x.due_date > today() ? x.due_date : today(), 1)}"><button class="btn btn-ghost" id="ppGo" style="flex:none">Elegir</button></div>
      </div>
      <div class="sheet-actions"><button class="btn btn-ghost" id="edit">Editar</button><button class="btn btn-danger" id="del">Eliminar</button></div>`);
    const base = x.due_date && x.due_date > today() ? x.due_date : today();
    const postpone = async date => {
      await App.saveTask(x.id, { status:"postergada", due_date:date, postponed_count:(x.postponed_count||0)+1, done_at:null });
      UI.close(); toast(`Postergada para ${dueLabel(date).t.toLowerCase()}`);
    };
    document.querySelectorAll("[data-st]").forEach(b => b.onclick = async () => {
      const st = b.dataset.st;
      if(st === "postergada"){ $("#pp").classList.toggle("hidden"); return; }
      await App.saveTask(x.id, { status:st, done_at: st==="hecha" ? new Date().toISOString() : null });
      UI.close(); toast(st==="hecha" ? "¡Bien hecho! ✓" : "Marcada como pendiente");
    });
    document.querySelectorAll("[data-pp]").forEach(b => b.onclick = () => postpone(addDays(base, +b.dataset.pp)));
    $("#ppGo").onclick = () => { const v = $("#ppDate").value; if(v) postpone(v); };
    $("#edit").onclick = () => UI.taskForm(x);
    $("#del").onclick = async () => { if(!confirm("¿Eliminar esta tarea?")) return; await App.delTask(x.id); UI.close(); toast("Tarea eliminada"); };
  },

  taskForm(x = null){
    let prio = x?.priority || "media";
    UI.modal(`
      <h3>${x ? "Editar tarea" : "Nueva tarea"}</h3><p class="muted" style="font-size:14px;margin-bottom:16px">${x?"Actualiza los datos.":"¿Qué tienes pendiente?"}</p>
      <form id="tf">
        <div class="field"><label>Tarea</label><input class="input" name="title" required maxlength="140" placeholder="Ej. Entregar informe de Cloud" value="${esc(x?.title||"")}"></div>
        <div class="field"><label>Fecha límite</label>
          <div class="chips" style="margin-bottom:8px">
            <button type="button" class="chip" data-d="0">Hoy</button><button type="button" class="chip" data-d="1">Mañana</button><button type="button" class="chip" data-d="7">En una semana</button><button type="button" class="chip" data-d="">Sin fecha</button>
          </div>
          <input class="input" type="date" name="due" value="${x ? (x.due_date||"") : today()}"></div>
        <div class="field"><label>Prioridad</label>
          <div class="seg three">${Object.entries(PRIO).reverse().map(([k,v])=>`<button type="button" data-p="${k}" class="${prio===k?"on":""}">${v.t}</button>`).join("")}</div></div>
        <div class="field"><label>Nota (opcional)</label><textarea class="input" name="notes" maxlength="500" placeholder="Detalles, enlaces, materiales…">${esc(x?.notes||"")}</textarea></div>
        <div class="sheet-actions"><button type="button" class="btn btn-ghost" id="cancel">Cancelar</button><button class="btn btn-primary" type="submit">${x?"Guardar":"Agregar tarea"}</button></div>
      </form>`);
    const f = $("#tf");
    f.title.focus();
    document.querySelectorAll("[data-d]").forEach(b => b.onclick = () => { f.due.value = b.dataset.d === "" ? "" : addDays(today(), +b.dataset.d); });
    document.querySelectorAll("[data-p]").forEach(b => b.onclick = () => { prio = b.dataset.p; document.querySelectorAll("[data-p]").forEach(o=>o.classList.toggle("on", o===b)); });
    $("#cancel").onclick = UI.close;
    f.onsubmit = async e => {
      e.preventDefault();
      const title = f.title.value.trim(); if(!title) return f.title.focus();
      const row = { title, due_date: f.due.value || null, priority: prio, notes: f.notes.value.trim() || null };
      if(x) await App.saveTask(x.id, row);
      else await App.addTask({ ...row, status:"pendiente", postponed_count:0 });
      UI.close(); toast(x ? "Tarea actualizada" : "Tarea agregada");
    };
  },

  /* ---------- FINANZAS ---------- */
  renderFinance(){
    const m = S.month;
    const all = S.movs;
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

    $("#main").innerHTML = `
      <section class="hello"><h2>Mis finanzas 💰</h2><p>Lo que ahorré y lo que gasté</p></section>
      <div class="month-nav"><button class="icon-btn" data-m="-1">${ICON.left}</button><b>${monthName}</b><button class="icon-btn" data-m="1">${ICON.right}</button></div>
      <section class="savings">
        <small>🐷 Total ahorrado</small>
        <strong>${money(savedTotal)}</strong>
        <span>${savedMonth >= 0 ? "+" : ""}${money(savedMonth)} este mes</span>
      </section>
      <section class="fin-stats">
        <div class="stat"><small><i style="background:var(--ok)"></i>Ingresos</small><strong>${money(inc)}</strong></div>
        <div class="stat"><small><i style="background:var(--bad)"></i>Gastado</small><strong>${money(exp)}</strong></div>
        <div class="stat"><small><i style="background:var(--info)"></i>Disponible</small><strong style="color:${available<0?"var(--bad)":"inherit"}">${money(available)}</strong></div>
      </section>
      <div class="fin-grid">
      <section class="panel">
        <h3>¿En qué gasté? <small>${catList.length ? money(exp) : ""}</small></h3>
        ${catList.length ? catList.map(([c,v],i) => `
          <div class="bar-row"><div class="lbl"><span>${CAT_EMOJI[c]||"•"} ${esc(c)}</span><span>${money(v)} · ${Math.round(v/exp*100)}%</span></div>
          <div class="bar"><div style="width:${v/maxCat*100}%;background:${CAT_COLORS[i%CAT_COLORS.length]}"></div></div></div>`).join("")
          : `<p class="muted" style="font-size:14px">Sin gastos registrados este mes.</p>`}
      </section>
      <section class="panel">
        <h3>Movimientos <small>${inMonth.length}</small></h3>
        ${inMonth.length ? Object.entries(byDay).map(([d, items]) => `
          <div class="day-h">${d === today() ? "Hoy" : d === addDays(today(),-1) ? "Ayer" : fmtLong(d)}</div>
          ${items.map(x => { const T = TYPES[x.type]; return `
            <button class="mov" data-mov="${x.id}">
              <span class="ic" style="background:${T.bg}">${CAT_EMOJI[x.category]||"•"}</span>
              <span class="b"><b>${esc(x.note || x.category)}</b><span>${T.t} · ${esc(x.category)}</span></span>
              <span class="amt" style="color:${T.color}">${T.sign>0?"+":"−"}${money(x.amount)}</span>
            </button>`; }).join("")}`).join("")
          : `<div class="empty" style="border:0;padding:28px 10px"><div class="big">🧾</div><b>Sin movimientos</b>Toca + para registrar un gasto, ingreso o ahorro.</div>`}
      </section>
      </div>`;
    document.querySelectorAll("[data-m]").forEach(b => b.onclick = () => {
      const d = new Date(y, mo-1 + Number(b.dataset.m), 1); S.month = `${d.getFullYear()}-${pad(d.getMonth()+1)}`; UI.renderFinance();
    });
    document.querySelectorAll("[data-mov]").forEach(b => b.onclick = () => UI.movSheet(b.dataset.mov));
  },

  movSheet(id){
    const x = S.movs.find(m => m.id === id); if(!x) return;
    const T = TYPES[x.type];
    UI.modal(`
      <div style="text-align:center;margin-bottom:18px">
        <div class="ic" style="width:56px;height:56px;border-radius:18px;background:${T.bg};display:grid;place-items:center;font-size:26px;margin:0 auto 10px">${CAT_EMOJI[x.category]||"•"}</div>
        <div style="font-size:32px;font-weight:800;color:${T.color}">${T.sign>0?"+":"−"}${money(x.amount)}</div>
        <p class="muted">${T.t} · ${esc(x.category)} · ${fmtLong(x.date)}</p>
        ${x.note ? `<p class="note">${esc(x.note)}</p>` : ""}
      </div>
      <div class="sheet-actions"><button class="btn btn-ghost" id="edit">Editar</button><button class="btn btn-danger" id="del">Eliminar</button></div>`);
    $("#edit").onclick = () => UI.movForm(x);
    $("#del").onclick = async () => { if(!confirm("¿Eliminar este movimiento?")) return; await App.delMov(x.id); UI.close(); toast("Movimiento eliminado"); };
  },

  movForm(x = null){
    let type = x?.type || "gasto";
    const catOpts = t => TYPES[t].cats.map(c => `<option ${x?.category===c?"selected":""}>${c}</option>`).join("");
    UI.modal(`
      <h3>${x ? "Editar movimiento" : "Nuevo movimiento"}</h3><p class="muted" style="font-size:14px;margin-bottom:16px">Registra en qué se fue (o llegó) tu dinero.</p>
      <form id="mf">
        <div class="seg">${Object.entries(TYPES).map(([k,v]) => `<button type="button" data-t="${k}" class="${type===k?"on":""}">${v.t}</button>`).join("")}</div>
        <div class="field"><label>Monto (${esc(CUR)})</label><input class="input amount-input" name="amount" type="number" inputmode="decimal" step="0.01" min="0.01" placeholder="0.00" required value="${x?.amount ?? ""}"></div>
        <div class="row">
          <div class="field"><label>Categoría</label><select class="input" name="category" id="catSel">${catOpts(type)}</select></div>
          <div class="field"><label>Fecha</label><input class="input" type="date" name="date" required value="${x?.date || today()}"></div>
        </div>
        <div class="field"><label>Descripción (opcional)</label><input class="input" name="note" maxlength="120" placeholder="Ej. Almuerzo en el comedor" value="${esc(x?.note||"")}"></div>
        <p class="muted" id="hint" style="font-size:12px;margin:-4px 0 14px"></p>
        <div class="sheet-actions"><button type="button" class="btn btn-ghost" id="cancel">Cancelar</button><button class="btn btn-primary" type="submit">${x?"Guardar":"Registrar"}</button></div>
      </form>`);
    const f = $("#mf");
    const hints = { gasto:"Dinero que salió: comida, pasajes, copias…", ingreso:"Dinero que recibiste: mesada, pagos, trabajos.", ahorro:"Dinero que apartas de lo disponible hacia tu ahorro.", retiro:"Dinero que sacas de tu ahorro para usarlo." };
    const setHint = () => $("#hint").textContent = hints[type];
    setHint();
    f.amount.focus();
    document.querySelectorAll("[data-t]").forEach(b => b.onclick = () => {
      type = b.dataset.t; document.querySelectorAll("[data-t]").forEach(o => o.classList.toggle("on", o===b));
      $("#catSel").innerHTML = catOpts(type); setHint();
    });
    $("#cancel").onclick = UI.close;
    f.onsubmit = async e => {
      e.preventDefault();
      const amount = Math.round(parseFloat(f.amount.value) * 100) / 100;
      if(!(amount > 0)) return f.amount.focus();
      const row = { type, amount, category: f.category.value, date: f.date.value, note: f.note.value.trim() || null };
      if(x) await App.saveMov(x.id, row); else await App.addMov(row);
      S.month = row.date.slice(0,7);
      UI.close(); toast(x ? "Movimiento actualizado" : `${TYPES[type].t} registrado`);
    };
  },

  /* ---------- MODAL ---------- */
  modal(html){
    UI.close();
    const o = document.createElement("div"); o.className = "overlay"; o.id = "overlay";
    o.innerHTML = `<div class="sheet" role="dialog" aria-modal="true"><div class="grab"></div>${html}</div>`;
    o.onclick = e => { if(e.target === o) UI.close(); };
    document.body.appendChild(o);
  },
  close(){ $("#overlay")?.remove(); }
};

/* ==================== APP ==================== */
const App = {
  async start(){
    try{
      const [tasks, movs] = await Promise.all([DB.list("tasks"), DB.list("transactions")]);
      S.tasks = tasks; S.movs = movs;
      UI.renderShell();
    }catch(err){
      console.error(err);
      $("#root").innerHTML = `<div class="loader"><div style="text-align:center;max-width:360px;padding:20px"><b>No se pudieron cargar tus datos</b><p class="muted" style="margin:8px 0 16px">${esc(err.message||err)}<br>¿Ejecutaste el archivo supabase.sql?</p><button class="btn btn-ghost" onclick="location.reload()">Reintentar</button></div></div>`;
    }
  },
  rerender(){ if($("#main")) S.view === "tareas" ? UI.renderTasks() : UI.renderFinance(); },
  async wrap(fn){ try{ await fn(); App.rerender(); }catch(err){ console.error(err); toast("Error: " + (err.message || "no se pudo guardar")); } },
  addTask(row){ return App.wrap(async () => S.tasks.push(await DB.insert("tasks", row))); },
  saveTask(id, patch){ return App.wrap(async () => { const r = await DB.update("tasks", id, patch); const i = S.tasks.findIndex(t=>t.id===id); S.tasks[i] = r || { ...S.tasks[i], ...patch }; }); },
  delTask(id){ return App.wrap(async () => { await DB.remove("tasks", id); S.tasks = S.tasks.filter(t => t.id !== id); }); },
  addMov(row){ return App.wrap(async () => S.movs.push(await DB.insert("transactions", row))); },
  saveMov(id, patch){ return App.wrap(async () => { const r = await DB.update("transactions", id, patch); const i = S.movs.findIndex(t=>t.id===id); S.movs[i] = r || { ...S.movs[i], ...patch }; }); },
  delMov(id){ return App.wrap(async () => { await DB.remove("transactions", id); S.movs = S.movs.filter(t => t.id !== id); }); }
};

// Atajos de teclado: N = nuevo, Esc = cerrar
document.addEventListener("keydown", e => {
  if(e.key === "Escape") UI.close();
  const typing = /INPUT|TEXTAREA|SELECT/.test(document.activeElement?.tagName);
  if(!typing && !$("#overlay") && $("#main") && (e.key === "n" || e.key === "N")){ e.preventDefault(); $("#fab").click(); }
});

(async () => {
  try{ await DB.init(); }catch(err){ $("#root").innerHTML = `<div class="loader">${esc(err.message)}</div>`; return; }
  const recovering = location.hash.includes("type=recovery");
  if(recovering) return UI.renderAuth("newpass");
  DB.session() ? App.start() : UI.renderAuth("login");
})();
})();
