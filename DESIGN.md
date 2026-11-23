# Design

Mundo visual de Echo. Fijado por el usuario: **modo glass**, **modales al estilo de las apps de Apple** y **paleta gris azulado** (ver Color). Superficie: Operate (app de trabajo diario, mobile-first).

## Escena
Celular en la mano, a cualquier hora del día: el tema sigue al sistema (claro de día, oscuro de noche).

## Mundo
iOS con materiales de vidrio. Un fondo de color difuso ("wallpaper") vive detrás de todo y los paneles son vidrio esmerilado que lo deja ver. Las manchas del fondo se reordenan entre Tareas y Finanzas dentro de la misma escala gris azulado.

## Tipografía
Pila del sistema de Apple (`-apple-system`, SF Pro); en Windows/Android cae a la fuente del sistema. Escala aumentada a pedido del usuario: título grande 38/700, título de hoja 19/600, cuerpo 19/400, secundario 17, nota 15. Números siempre tabulares.

## Color
Paleta elegida por el usuario, una escala de gris azulado:
`#2e4957 · #375261 · #415c6b · #4a6675 · #54707f · #688391 · #87a1ab · #a7bfc7 · #c8dfe2 · #eaffff`
- Tinte: `#2e4957` en claro (texto `#eaffff` encima), `#a7bfc7` en oscuro (texto `#16262e` encima).
- Fondos: claro `#dcecee` con manchas `#87a1ab / #c8dfe2 / #a7bfc7`; oscuro `#15232b` con `#2e4957 / #4a6675 / #375261`. Finanzas reordena las manchas de la misma escala.
- Vidrio teñido de la paleta (`rgba(234,255,255,.55)` claro, `rgba(46,73,87,.5)` oscuro).
- Íconos de categoría: cuadros en tonos de la escala (oscuros con ícono claro en tema claro; claros con ícono oscuro en tema oscuro).
- Los colores de estado conservan significado: hecha/ingreso verde, atrasada/gasto destructivo rojo, postergada y prioridad media naranja.

## Materiales
- Glassmorphism: paneles muy translúcidos (`rgba(255,255,255,.3)` claro, `rgba(234,255,255,.08)` oscuro), `backdrop-filter: blur(22px) saturate(150%)`, borde de 1px claro y sombra suave neutra. Detrás, cinco formas difusas de la paleta (blur 36px) para que el vidrio se note. Sin iluminados: ni brillos internos, ni halos de color en botones, ni degradados brillantes (pedido del usuario).
- Listas: grupos insertados (inset grouped) con separadores finos, no tarjetas sueltas.

## Componentes
- **Hoja (sheet)**: sube desde abajo con curva de resorte de iOS, esquinas de 38px, asa para arrastrar y cerrar, barra con Cancelar / título / acción. Al abrirse, la app de fondo se encoge y oscurece (efecto tarjeta de iOS). En pantallas ≥768px es una hoja centrada.
- **Alerta**: cuadro centrado de vidrio, título, mensaje y botones en fila; acción destructiva en rojo.
- **Navegación flotante**: cápsula de vidrio abajo. La sección activa se expande como píldora con ícono y nombre (Tareas o Finanzas); las demás y Cuenta son círculos con ícono. A la derecha, un botón + grande en píldora con el tinte. Mobile-first: tamaño compacto bajo 370px.
- **Aviso (toast)**: cápsula de vidrio que baja desde arriba.
- **Control segmentado** de iOS para tipo de movimiento y prioridad.

## Movimiento
Una sola gesta firmada: la hoja que sube y la app que retrocede. Curva `cubic-bezier(.32,.72,0,1)`, 480ms. Con `prefers-reduced-motion` todo es inmediato.

## Lo que no hace
Sin emojis como íconos, sin texto con degradado, sin halos de neón, sin tarjetas anidadas.
