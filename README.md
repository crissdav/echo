# Echo

App web personal: **tareas** (hecha / pendiente / postergada) + **finanzas** (ingresos, gastos, ahorro).
HTML + CSS + JS puro, sin compilación. Login y base de datos con **Supabase**. Hosting en **Vercel**.

## Archivos
| Archivo | Qué es |
|---|---|
| `index.html` | Toda la app (estilos + lógica) |
| `supabase.sql` | Tablas + seguridad por usuario (RLS) |

## 1. Probar en modo demo
Abre `index.html` en el navegador. Sin claves de Supabase funciona en **modo demo**: crea una cuenta local y todo se guarda en ese navegador.

## 2. Conectar Supabase (login real + datos en la nube)
1. Crea un proyecto en https://supabase.com
2. **SQL Editor → New query** → pega `supabase.sql` → **Run**
3. **Project Settings → API**: copia *Project URL* y *anon public key*
4. En `index.html`, arriba del todo:
   ```js
   window.APP_CONFIG = {
     SUPABASE_URL: "https://xxxx.supabase.co",
     SUPABASE_ANON_KEY: "eyJhbGciOi...",
     MONEDA: "S/"
   };
   ```
   (La *anon key* es pública por diseño; la seguridad la dan las políticas RLS del SQL.)
5. **Authentication → URL Configuration**: en *Site URL* y *Redirect URLs* pon tu dominio de Vercel
   (`https://echo-fiis1.vercel.app`). Así funcionan la confirmación de correo y "¿Olvidaste tu contraseña?".
6. Opcional: si no quieres confirmar el correo al registrarte → **Authentication → Providers → Email → Confirm email: off**.

## 3. Despliegue
El proyecto `echo` de Vercel está enlazado a este repo: cada push a `main` se publica solo en https://echo-fiis1.vercel.app (sin build, Framework: Other).

## Cómo funciona
**Tareas**: toca una tarea y elige **Hecha**, **Pendiente** o **Postergar** (mañana, +3 días, +1 semana o fecha a elegir). Las postergadas llevan un contador. Filtros: Hoy, Pendientes, Postergadas, Hechas, Todas. Las atrasadas se marcan en rojo.

**Finanzas**:
- **Ingreso**: dinero que entra · **Gasto**: dinero que sale
- **Ahorro**: apartas dinero al ahorro · **Retiro**: lo sacas del ahorro
- *Total ahorrado* = ahorros − retiros (histórico)
- *Disponible* = ingresos − gastos − total ahorrado (histórico)
- Desglose de gastos por categoría del mes, con navegación entre meses.

Atajos: `N` nuevo elemento · `Esc` cerrar.
