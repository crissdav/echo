# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users
Público general: cualquier persona puede crear una cuenta (correo y contraseña) y ver solo sus propios datos. El primer usuario es Leandro, estudiante de Ingeniería de Sistemas en la UNAS (Tingo María, Perú). Uso principal desde el celular, a lo largo de todo el día.

## Product Purpose
Echo junta en un solo lugar dos rutinas diarias: lo que tengo pendiente y lo que pasa con mi dinero. Éxito: abrir la app y en segundos saber qué toca hoy y cuánto he gastado o ahorrado, y registrar una tarea o un gasto sin fricción.

## Positioning
Tareas y finanzas personales con el mismo peso en una sola app ligera: no es un gestor de proyectos ni una app bancaria, sino el resumen del día de una persona.

## Operating Context
- Se abre en el celular en momentos cortos (entre clases, después de pagar algo) y también en laptop.
- Las dos tareas más frecuentes tienen el mismo peso: revisar/marcar pendientes y anotar un movimiento de dinero.
- Moneda por defecto: soles (S/). Idioma: español (Perú).

## Capabilities and Constraints
- Tareas con fecha límite, prioridad (baja/media/alta) y nota; estados Hecha, Pendiente y Postergada (mañana, +3 días, +1 semana o fecha elegida, con contador de postergaciones); filtros Hoy, Pendientes, Postergadas, Hechas, Todas.
- Movimientos: Ingreso, Gasto, Ahorro (aporte) y Retiro (del ahorro), con categoría, fecha y descripción. Resumen mensual con navegación entre meses: total ahorrado, ingresos, gastado, disponible y desglose de gastos por categoría.
- Login con Supabase Auth (registro, inicio de sesión, recuperación de contraseña). Datos en Supabase Postgres con RLS por usuario. Modo demo local si no hay claves.
- Stack actual: HTML + CSS + JS vanilla (sin build), mobile-first, desplegado en Vercel (plan Hobby: uso no comercial).

## Brand Commitments
- Nombre: Echo. Sin significado especial que el diseño deba reflejar.

## Evidence on Hand
No hay usuarios reales, testimonios ni métricas. No inventar cifras ni datos de uso.

## Product Principles
1. Lo de hoy primero: el estado del día se entiende de un vistazo.
2. Registrar en segundos: crear una tarea o un movimiento nunca pide más de lo necesario.
3. Tareas y dinero valen lo mismo: ninguna sección se siente secundaria.
4. Privado por defecto: cada persona ve solo lo suyo.

## Accessibility & Inclusion
Uso con una mano en el celular: objetivos táctiles cómodos y contraste legible bajo luz de día y de noche (sigue el tema del sistema).
