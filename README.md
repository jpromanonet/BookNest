# BookNest

**Personal library system** — catálogo doméstico de ejemplares físicos, sin login, sin préstamos y sin ubicaciones.

Estética **Pastel Fantasy Terminal**: pergamino, tipografía pixel + monoespaciada, bordes duros e iconografía pixel art. Como un programa que un bibliotecario-mago hubiera usado en una PC de 1989.

```text
╔══════════════════════════════════════════╗
║  BOOKNEST                     v0.1       ║
║  PERSONAL LIBRARY SYSTEM                 ║
╚══════════════════════════════════════════╝
```

---

## Qué es

Cada registro es un **ejemplar físico**. Podés tener varias ediciones del mismo libro como fichas independientes. Goodreads alimenta metadata (editable siempre). La vista principal es una **tabla**; la grilla es inventario RPG opcional.

### Incluye (v0.1)

- Dashboard con métricas y progreso de lectura
- Biblioteca (tabla / grilla, búsqueda, filtros, paginación)
- Alta / edición / detalle / eliminación de ejemplares
- Autores, editoriales, colecciones, sagas, géneros y tags
- Estados de lectura y estado físico
- Lookup de metadata vía Goodreads (página pública)
- Wishlist con “mover a biblioteca”
- Estadísticas básicas
- Importación / exportación JSON y CSV
- Seed de los 217 ejemplares iniciales

---

## Stack

| Capa | Tecnología |
|------|------------|
| Backend | PHP 8.1+ |
| Base | MySQL 8 / MariaDB |
| Front | HTML, CSS, JavaScript (sin frameworks) |
| Metadata | Goodreads (scraping público, best-effort) |

---

## Requisitos

- PHP con `pdo_mysql`, `mbstring`, `curl` (recomendado)
- MySQL en marcha
- Apache (o el servidor que ya sirve `W:\`) — rutas vía `index.php?r=/ruta`

---

## Instalación

1. Copiá el proyecto a `W:\booknest` (o servilo desde el repo).
2. Copiá `.env.example` → `.env` y ajustá la base:

```env
APP_NAME=BookNest
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=booknest
DB_USER=root
DB_PASS=
```

3. Creá la base:

- Abrí `install.php` en el navegador, **o**
- Importá `databases/booknest.sql` a mano.

4. Cargá los 217 libros del seed:

```bash
php seed.php --replace
```

Desde el instalador web también podés pulsar **Importar 217 libros**.

5. Entrá a `index.php`.

---

## Navegación

```text
Dashboard · Biblioteca · Autores · Colecciones
Wishlist · Estadísticas · Importar/Exportar · Configuración
```

---

## Design system (resumen)

| Token | Hex | Uso |
|------:|-----|-----|
| Pergamino | `#F4EDDF` | Fondo |
| Marfil | `#FFF9EE` | Ventanas |
| Tinta | `#403845` | Texto |
| Lavanda | `#BBA9D6` | Accent |
| Salvia | `#ABC4A4` | Leído |
| Oro | `#CFAC68` | Colecciones |

- Títulos: **Pixelify Sans**
- UI: **IBM Plex Mono**
- Sinopsis: **Georgia**
- Bordes 2px, `border-radius` 0–2px, sombras pixel (`4px 4px 0`)

---

## Principios

1. Un registro = un ejemplar físico  
2. Ediciones distintas pueden coexistir  
3. La tabla es la experiencia principal  
4. Toda metadata externa es editable  
5. Sin usuarios, roles, préstamos ni ubicaciones  
6. Los datos locales son la fuente de verdad  

---

## Estructura

```text
BookNest/
├── app/            Controllers, Services, Views
├── assets/         CSS, JS, iconos pixel, fondo
├── config/         .env loader, app, database
├── data/           BookNest.json (seed 217)
├── databases/      booknest.sql
├── storage/covers/ Portadas subidas
├── index.php
├── install.php
└── seed.php
```

---

## Licencia / uso

Proyecto personal. Hecho para una sola biblioteca en casa.
