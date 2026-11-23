# Design

Mundo visual de Echo. Fijado por el usuario: **modo glass** y **modales al estilo de las apps de Apple**. Superficie: Operate (app de trabajo diario, mobile-first).

## Escena
Celular en la mano, a cualquier hora del día: el tema sigue al sistema (claro de día, oscuro de noche).

## Mundo
iOS con materiales de vidrio. Un fondo de color difuso ("wallpaper") vive detrás de todo y los paneles son vidrio esmerilado que lo deja ver. El color del fondo cambia según la sección: índigo/rosa/turquesa en Tareas, verde/menta/ámbar en Finanzas, para que el color diga dónde estás.

## Tipografía
Pila del sistema de Apple (`-apple-system`, SF Pro); en Windows/Android cae a la fuente del sistema. Escala: título grande 34/700, título de hoja 17/600, cuerpo 17/400, secundario 15, nota 13. Números siempre tabulares.

## Color
- Tinte de la app: índigo iOS `#5856D6` (oscuro `#5E5CE6`).
- Estados con colores del sistema iOS: hecha verde `#34C759`, atrasada rojo `#FF3B30`, postergada naranja `#FF9500`, ingreso verde, gasto rojo, ahorro índigo, retiro naranja.
- Categorías: ícono SVG en cuadro redondeado de color, como en Ajustes de iOS.

## Materiales
- Vidrio: `backdrop-filter: blur(28px) saturate(180%)`, relleno translúcido, borde de 0.5px con brillo superior y sombra suave con desplazamiento.
- Listas: grupos insertados (inset grouped) con separadores finos, no tarjetas sueltas.

## Componentes
- **Hoja (sheet)**: sube desde abajo con curva de resorte de iOS, esquinas de 38px, asa para arrastrar y cerrar, barra con Cancelar / título / acción. Al abrirse, la app de fondo se encoge y oscurece (efecto tarjeta de iOS). En pantallas ≥768px es una hoja centrada.
- **Alerta**: cuadro centrado de vidrio, título, mensaje y botones en fila; acción destructiva en rojo.
- **Dock**: barra de pestañas flotante en cápsula de vidrio con botón + circular al lado.
- **Aviso (toast)**: cápsula de vidrio que baja desde arriba.
- **Control segmentado** de iOS para tipo de movimiento y prioridad.

## Movimiento
Una sola gesta firmada: la hoja que sube y la app que retrocede. Curva `cubic-bezier(.32,.72,0,1)`, 480ms. Con `prefers-reduced-motion` todo es inmediato.

## Lo que no hace
Sin emojis como íconos, sin texto con degradado, sin halos de neón, sin tarjetas anidadas.
