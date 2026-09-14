# Propuesta UX/UI — Zuitch.com

**Herramienta de sugerencias para superadministradores + WhatsApp opcional en posts de Páginas**

| | |
|---|---|
| **Cliente / plataforma** | Zuitch.com |
| **Ámbito** | Admin + frontend |
| **Inversión** | **USD 180** (paquete cerrado) |

---

## 1. Objetivo

Dar al superadministrador una forma rápida y estructurada de reportar qué quiere mejorar o corregir en Zuitch, apuntando a elementos concretos de la interfaz, con contexto técnico automático (URL, navegador, dispositivo, dimensiones) y un historial con prioridad y estado dentro de [https://zuitch.com/admin-cp](https://zuitch.com/admin-cp).

Además, permitir que cada **Página** de Zuitch active de forma opcional un botón de WhatsApp en sus publicaciones, usando el número configurado en los ajustes de esa Página.

---

## 2. Beneficios del widget de sugerencias

Más que un formulario de feedback: convierte cada observación del superadministrador en un ticket técnico listo para ejecutar.

- **Menos ida y vuelta** — URL, navegador, dispositivo, dimensiones y elemento marcado viajan juntos.
- **Menos ambigüedad** — Seleccionar el elemento en vivo elimina interpretaciones erróneas.
- **Prioridad y estado claros** — Histórico en Admin para planificar y medir avance.
- **Evidencia adjunta** — Captura completa de la página, pegar pantallas (Ctrl+V) o subir hasta 5 adjuntos.
- **Un solo clic a la página** — Link directo desde el panel a la URL reportada.
- **Backlog vivo** — Todo centralizado: qué se pidió, cuándo, prioridad y estado.

### Cómo agiliza los tiempos de desarrollo

El superadministrador reporta en el momento y en el lugar: el ticket llega completo. Eso reduce el ciclo reportar → entender → reproducir → corregir → validar, acorta el diagnóstico y permite que el desarrollo entre directo a implementar.

| Etapa | Sin la herramienta | Con el widget |
|---|---|---|
| Reportar | Chat/correo incompleto | Formulario + elemento + metadatos + captura completa / pantallas |
| Entender | Ida y vuelta | Contexto ya incluido |
| Reproducir | Buscar pantalla / adivinar dispositivo | Link + dispositivo + viewport |
| Priorizar | Lista dispersa | Prioridad y estado en Admin |
| Validar | ¿Ya se hizo? | Estado + enlace en un clic |

---

## 3. Alcance incluido

### Módulo A — Widget de sugerencias (solo superadmin)

**Captura y formulario**

- Tipo de reporte: **Sugerencia** o **Error detectado**
- Modo de elementos: **Abierta** (comentario general) o **Seleccionar elemento** (click sobre un componente de la página)
- Campo de comentario con soporte para pegar texto y capturas (**Ctrl+V**)
- **Captura de pantalla completa** de la página actual con un clic (se adjunta automáticamente al reporte)
- Adjuntos: hasta **5** archivos o pantallas (pegar, arrastrar, captura automática o elegir desde el equipo)
- Prioridad al crear (baja / media / alta / crítica)
- Botón flotante de apertura/cierre del widget en la web pública

**Metadatos automáticos**

- URL exacta de la página donde se genera la sugerencia
- Navegador y versión
- Sistema operativo
- Tipo de dispositivo: celular, tablet o computadora
- Marca y modelo del dispositivo (cuando el navegador lo permita; best-effort)
- Dimensiones del viewport y de la pantalla (ancho × alto) y densidad de píxeles (DPR)
- Datos del elemento seleccionado: selector CSS, etiqueta, texto/HTML resumido y posición en pantalla
- Usuario (email / cuenta) del superadministrador que envió el reporte

**Panel en Admin**

- Nueva sección para ver el **histórico** de sugerencias y errores
- Visualización de **prioridad** y **estado** (pendiente, en progreso, en revisión, hecho, rechazado)
- Filtros por tipo, prioridad, estado y búsqueda por URL o texto
- Detalle completo: metadatos técnicos, elemento marcado y adjuntos
- Enlace directo para **abrir la página** donde se originó el reporte
- Actualización de estado, prioridad y nota interna desde el panel

### Módulo B — Botón WhatsApp opcional en posts de Páginas

**Configuración en la Página (ajustes):**

- Campo para ingresar el **número de WhatsApp** de la Página
- Selector / interruptor: **mostrar u ocultar** el botón de WhatsApp en las publicaciones de esa Página (por defecto: oculto)
- El botón solo aparece si el interruptor está activo **y** hay un número válido

**En las publicaciones:**

- Visible únicamente en posts publicados **desde esa Página** (no en posts de perfil de usuario ni grupos)
- Al hacer clic abre WhatsApp (`wa.me`) hacia el número configurado en la Página, con opción de incluir el enlace del post
- Comportamiento adaptable a móvil (app) y escritorio (WhatsApp Web / fallback)
- Integración coherente con el diseño actual de Zuitch (tema wowonder)

### Capacitación

- **1 hora de capacitación** remota sobre el uso de la herramienta: crear sugerencias, seleccionar elementos, captura de pantalla completa, adjuntar pantallas, y gestionar prioridad/estado en Admin

---

## 4. Entregables

| Entregable | Descripción |
|---|---|
| Widget frontend | FAB + modal + selector + metadatos + captura de pantalla completa + adjuntos (solo superadmin) |
| Backend / base de datos | Almacenamiento de sugerencias, estados, prioridades y archivos |
| Sección Admin | Histórico, filtros, detalle, estado/prioridad y link a la página |
| WhatsApp opcional en Páginas | Número + toggle en ajustes de Página; botón en posts solo si está activo |
| Capacitación | 1 hora de sesión práctica de uso |

---

## 5. Inversión

| Concepto | Valor (USD) |
|---|---:|
| Herramienta de sugerencias para superadministradores (widget + metadatos + captura completa + Admin) | Incluido |
| Botón WhatsApp opcional en posts de Páginas (número + activar/desactivar en ajustes) | Incluido |
| 1 hora de capacitación en el uso de la herramienta | Incluido |
| **Total del paquete** | **USD 180** |

Precio único por el alcance descrito. No incluye rediseño general del sitio, notificaciones externas (email/Telegram) ni desarrollos fuera del listado.

---

## 6. Fuera de alcance

- Botón WhatsApp en posts de perfiles de usuario o grupos (solo Páginas, y solo si la Página lo activa)
- App nativa iOS/Android
- Roles de moderador (solo superadministradores `admin = 1`)
- Rediseño UX completo de Zuitch
- Soporte mensual recurrente (salvo la hora de capacitación incluida)
- Nota: la captura de pantalla completa es *best-effort* en el navegador; en páginas muy largas o con contenido dinámico complejo puede requerir complementar con Ctrl+V o adjunto manual

---

## 7. Condiciones

- Desarrollo e integración sobre la plataforma actual WoWonder de **zuitch.com**
- Acceso de superadministrador requerido para pruebas y entrega
- La capacitación de 1 hora se agenda de común acuerdo tras la entrega funcional
- Ajustes menores de la herramienta (dentro del alcance) durante los primeros días posteriores a la entrega

---

**Por el proveedor** ________________ · Fecha ________  

**Por el cliente (Zuitch)** ________________ · Fecha ________
