<?php

declare(strict_types=1);

/**
 * Builds an RPG-flavored "reader profile" from library stats.
 */
final class ReaderProfileService
{
    public static function build(array $stats): array
    {
        $books = max(0, (int) ($stats['books'] ?? 0));
        $read = (int) ($stats['read'] ?? 0);
        $unread = (int) ($stats['unread'] ?? 0);
        $reading = (int) ($stats['reading'] ?? 0);
        $abandoned = (int) ($stats['abandoned'] ?? 0);
        $reread = (int) ($stats['reread'] ?? 0);
        $pages = (int) ($stats['pages'] ?? 0);
        $pagesRead = (int) ($stats['pages_read'] ?? 0);
        $authors = (int) ($stats['authors'] ?? 0);
        $genres = (int) ($stats['genres'] ?? 0);

        $pctRead = $books > 0 ? (int) round(($read / $books) * 100) : 0;
        $pctUnread = $books > 0 ? (int) round(($unread / $books) * 100) : 0;
        $pctReading = $books > 0 ? (int) round(($reading / $books) * 100) : 0;
        $pctAbandoned = $books > 0 ? (int) round(($abandoned / $books) * 100) : 0;

        $archetype = self::archetype($stats, $pctRead, $pctUnread, $pctReading);
        $traits = self::traits($stats, $pctRead, $pctUnread, $pctReading, $pctAbandoned);
        $affinities = self::affinities($stats);
        $questLog = self::questLog($stats, $pctRead, $pctUnread);
        $level = self::level($books, $pagesRead, $pctRead);
        $motto = self::motto($archetype, $pctRead, $pctUnread, $pctReading);
        $house = self::house($archetype, $stats, $pctRead, $pctUnread);
        $boons = self::boons($stats, $pctRead, $pctUnread, $pctReading, $pctAbandoned, $reread);
        $omen = self::omen($stats, $pctRead, $pctUnread, $pctReading);
        $chronicle = self::chronicle(
            $archetype,
            $stats,
            $traits,
            $affinities,
            $pctRead,
            $pctUnread,
            $pctReading,
            $level,
            $house,
            $motto
        );

        return [
            'archetype' => $archetype,
            'level' => $level,
            'title' => $archetype['title'],
            'epithet' => $archetype['epithet'],
            'crest' => $archetype['crest'],
            'motto' => $motto,
            'house' => $house,
            'boons' => $boons,
            'omen' => $omen,
            'traits' => $traits,
            'affinities' => $affinities,
            'quest_log' => $questLog,
            'chronicle' => $chronicle,
            'pct_read' => $pctRead,
            'pct_unread' => $pctUnread,
            'pct_reading' => $pctReading,
            'pct_abandoned' => $pctAbandoned,
            'stats_line' => [
                'books' => $books,
                'read' => $read,
                'unread' => $unread,
                'reading' => $reading,
                'authors' => $authors,
                'genres' => $genres,
                'pages' => $pages,
                'pages_read' => $pagesRead,
                'reread' => $reread,
            ],
        ];
    }

    private static function archetype(array $stats, int $pctRead, int $pctUnread, int $pctReading): array
    {
        $topGenre = strtolower((string) (($stats['by_genre'][0]['label'] ?? '')));
        $topLang = (string) (($stats['by_language'][0]['label'] ?? 'Sin idioma'));
        $books = (int) ($stats['books'] ?? 0);

        if ($books === 0) {
            return [
                'key' => 'empty_hall',
                'title' => 'Guardián del Umbral',
                'epithet' => 'El archivo aún espera su primer tomo',
                'crest' => 'nest',
                'color' => 'lavender',
            ];
        }

        if ($pctReading >= 15) {
            return [
                'key' => 'flamekeeper',
                'title' => 'Guardián de la Llama',
                'epithet' => 'Varios grimorios arden a la vez en el atril',
                'crest' => 'reading',
                'color' => 'blue',
            ];
        }

        if ($pctRead >= 65) {
            return [
                'key' => 'archmage',
                'title' => 'Archimago del Canon',
                'epithet' => 'Ha recorrido la mayor parte del reino escrito',
                'crest' => 'read',
                'color' => 'sage',
            ];
        }

        if ($pctUnread >= 70) {
            return [
                'key' => 'hoarder',
                'title' => 'Coleccionista del Horizonte',
                'epithet' => 'Acumula mundos por explorar como tesoros sellados',
                'crest' => 'library',
                'color' => 'gold',
            ];
        }

        if (str_contains($topGenre, 'fant') || str_contains($topGenre, 'sci') || str_contains($topGenre, 'ciencia')) {
            return [
                'key' => 'planar',
                'title' => 'Caminante de Planos',
                'epithet' => 'Su brújula apunta a reinos imposibles',
                'crest' => 'collection',
                'color' => 'lavender',
            ];
        }

        if (stripos($topLang, 'espa') !== false || stripos($topLang, 'spanish') !== false) {
            return [
                'key' => 'chronicler',
                'title' => 'Cronista del Pergamino',
                'epithet' => 'Custodia voces en la lengua del hogar',
                'crest' => 'author',
                'color' => 'peach',
            ];
        }

        if ($pctRead >= 35 && $pctUnread >= 35) {
            return [
                'key' => 'balance',
                'title' => 'Equilibrista del Archive',
                'epithet' => 'Entre lo leído y lo pendiente mantiene la balanza',
                'crest' => 'dashboard',
                'color' => 'gold',
            ];
        }

        return [
            'key' => 'seeker',
            'title' => 'Buscador de Reliquias',
            'epithet' => 'Cada estante es una pista hacia el siguiente misterio',
            'crest' => 'search',
            'color' => 'rose',
        ];
    }

    private static function motto(array $archetype, int $pctRead, int $pctUnread, int $pctReading): string
    {
        return match ($archetype['key']) {
            'empty_hall' => 'Primero el silencio; después, el hechizo.',
            'flamekeeper' => 'Que ninguna página se enfríe del todo.',
            'archmage' => 'Lo leído no se pierde: se convierte en brújula.',
            'hoarder' => 'Guardar también es una forma de amar los mundos.',
            'planar' => 'Donde otros ven lomos, yo veo portales.',
            'chronicler' => 'La lengua del hogar también es magia antigua.',
            'balance' => 'Ni solo cenizas ni solo sellos: el ritmo justo.',
            default => $pctReading > 0
                ? 'Hoy el atril habla; mañana, el horizonte.'
                : ($pctUnread >= $pctRead
                    ? 'Hay tesoros que maduran en la sombra del estante.'
                    : 'Cada tapa abierta es una conquista silenciosa.'),
        };
    }

    private static function house(array $archetype, array $stats, int $pctRead, int $pctUnread): array
    {
        $topGenre = (string) (($stats['by_genre'][0]['label'] ?? 'lo Indeciso'));
        $names = [
            'empty_hall' => 'Casa del Umbral Vacío',
            'flamekeeper' => 'Orden de las Llamas Gemelas',
            'archmage' => 'Círculo del Canon Consumado',
            'hoarder' => 'Gremio del Horizonte Sellado',
            'planar' => 'Hermandad de los Portales',
            'chronicler' => 'Escuela del Pergamino Doméstico',
            'balance' => 'Balanza de los Dos Reinos',
            'seeker' => 'Ruta de las Reliquias Errantes',
        ];

        $virtue = $pctRead >= $pctUnread
            ? 'virtud: consumar lo empezado'
            : 'virtud: preservar lo posible';

        return [
            'name' => $names[$archetype['key']] ?? 'Nest Innominado',
            'sigil' => $topGenre,
            'virtue' => $virtue,
            'blurb' => "Bajo el estandarte de **{$topGenre}**, esta casa mide el valor no en oro, sino en páginas atravesadas y misterios aún intactos.",
        ];
    }

    /**
     * @return list<array{kind:string,name:string,detail:string}>
     */
    private static function boons(
        array $stats,
        int $pctRead,
        int $pctUnread,
        int $pctReading,
        int $pctAbandoned,
        int $reread
    ): array {
        $boons = [];
        $avgPages = (int) ($stats['avg_pages'] ?? 0);
        $authors = (int) ($stats['authors'] ?? 0);
        $genres = (int) ($stats['genres'] ?? 0);
        $wishlist = (int) ($stats['wishlist'] ?? 0);

        if ($pctRead >= 50) {
            $boons[] = [
                'kind' => 'bendición',
                'name' => 'Aura del Canon',
                'detail' => 'Más de la mitad del archive ya fue conquistado; las estanterías reconocen tu paso.',
            ];
        }
        if ($pctUnread >= 55) {
            $boons[] = [
                'kind' => 'bendición',
                'name' => 'Cámara de Reliquias',
                'detail' => 'Un horizonte generoso de pendientes garantiza que nunca falte magia por descubrir.',
            ];
        }
        if ($pctReading >= 10) {
            $boons[] = [
                'kind' => 'bendición',
                'name' => 'Multicanal Narrativo',
                'detail' => 'Varias historias arden a la vez: el atril es un altar de fuegos cruzados.',
            ];
        }
        if ($avgPages >= 350) {
            $boons[] = [
                'kind' => 'bendición',
                'name' => 'Piel de Dragón',
                'detail' => 'Los lomos densos no intimidan; la resistencia a epopeyas es legendaria.',
            ];
        }
        if ($authors >= 40) {
            $boons[] = [
                'kind' => 'bendición',
                'name' => 'Coro Polifónico',
                'detail' => "{$authors} autores cantan en el Nest: pocas bibliotecas domésticas reúnen tal cortejo.",
            ];
        }
        if ($genres >= 5) {
            $boons[] = [
                'kind' => 'bendición',
                'name' => 'Prisma de Géneros',
                'detail' => 'El catálogo no obedece a una sola escuela; el gusto se refracta en colores.',
            ];
        }
        if ($reread > 0) {
            $boons[] = [
                'kind' => 'bendición',
                'name' => 'Rito del Regreso',
                'detail' => "Hay {$reread} relectura(s) registradas: volver a un libro es una forma alta de lealtad.",
            ];
        }
        if ($wishlist > 0) {
            $boons[] = [
                'kind' => 'bendición',
                'name' => 'Brújula del Deseo',
                'detail' => "La wishlist apunta a {$wishlist} futuros botines; el mapa no está vacío.",
            ];
        }

        if ($pctAbandoned >= 12) {
            $boons[] = [
                'kind' => 'carga',
                'name' => 'Senderos Truncados',
                'detail' => 'Algunos caminos quedaron a medias. El archive los guarda sin rencor, pero los nombra.',
            ];
        }
        if ($pctUnread >= 75) {
            $boons[] = [
                'kind' => 'carga',
                'name' => 'Peso del Horizonte',
                'detail' => 'Tantos sellos intactos pueden oprimir: la tentación de acumular rivaliza con la de leer.',
            ];
        }
        if ($pctReading === 0 && (int) ($stats['books'] ?? 0) > 0) {
            $boons[] = [
                'kind' => 'carga',
                'name' => 'Atril en Reposo',
                'detail' => 'Ninguna llama activa. El silencio del atril espera que elijas el próximo grimorio.',
            ];
        }

        if ($boons === []) {
            $boons[] = [
                'kind' => 'bendición',
                'name' => 'Semilla del Nest',
                'detail' => 'El perfil aún germina. Cada libro nuevo será una runa más en tu leyenda.',
            ];
        }

        return array_slice($boons, 0, 6);
    }

    private static function omen(array $stats, int $pctRead, int $pctUnread, int $pctReading): array
    {
        $unread = (int) ($stats['unread'] ?? 0);
        $reading = (int) ($stats['reading'] ?? 0);
        $topGenre = (string) (($stats['by_genre'][0]['label'] ?? 'un género aún innombrado'));
        $topAuthor = (string) (($stats['top_authors'][0]['label'] ?? 'un aliado desconocido'));

        if ((int) ($stats['books'] ?? 0) === 0) {
            return [
                'title' => 'Presagio del Primer Tomo',
                'body' => 'Cuando el primer ejemplar cruce el umbral, el Nest despertará y las runas empezarán a cantar.',
            ];
        }

        if ($pctReading > 0) {
            return [
                'title' => 'Presagio de la Llama',
                'body' => "Mientras {$reading} lectura(s) sigan vivas, el oráculo aconseja: terminá una antes de encender tres. "
                    . "La afinidad con **{$topGenre}** sugiere que el próximo giro de página traerá eco de ese reino.",
            ];
        }

        if ($pctUnread >= 60) {
            return [
                'title' => 'Presagio del Sello Roto',
                'body' => "Hay {$unread} mundos dormidos. Elige uno al azar —o vuelve a **{$topAuthor}**— y rompé un sello esta luna. "
                    . "El horizonte no se achica por leer: se vuelve más nítido.",
            ];
        }

        if ($pctRead >= 60) {
            return [
                'title' => 'Presagio del Canon',
                'body' => "Has recorrido mucho. El siguiente acto no es solo acumular: es releer un favorito o abrir una puerta "
                    . "hacia un género que aún no domina el estante. **{$topGenre}** ya es hogar; explorá el vecindario.",
            ];
        }

        return [
            'title' => 'Presagio del Camino Medio',
            'body' => "Ni todo consumido ni todo sellado. Esta luna favorece un duelo amable: un libro breve del pending "
                . "y una visita a las voces de **{$topAuthor}**. El Nest crece cuando el ritmo es humano.",
        ];
    }

    private static function traits(
        array $stats,
        int $pctRead,
        int $pctUnread,
        int $pctReading,
        int $pctAbandoned
    ): array {
        $traits = [];

        $traits[] = [
            'name' => 'Voracidad',
            'value' => min(99, 20 + $pctRead),
            'blurb' => $pctRead >= 50
                ? 'Los tomos caen ante su paso como dragones vencidos.'
                : 'La llama de la lectura crece, paciente y constante.',
        ];

        $traits[] = [
            'name' => 'Tesorería',
            'value' => min(99, 20 + $pctUnread),
            'blurb' => $pctUnread >= 50
                ? 'Guarda más secretos sin abrir que muchos reinos enteros.'
                : 'Prefiere consumir lo que captura; el horde es moderado.',
        ];

        $traits[] = [
            'name' => 'Multihilo',
            'value' => min(99, 15 + ($pctReading * 3)),
            'blurb' => $pctReading >= 10
                ? 'Varias sagas conviven en su mesa como hechizos simultáneos.'
                : 'Suele canalizar una sola corriente narrativa a la vez.',
        ];

        $avgPages = (int) ($stats['avg_pages'] ?? 0);
        $traits[] = [
            'name' => 'Resistencia',
            'value' => min(99, 25 + (int) round($avgPages / 8)),
            'blurb' => $avgPages >= 400
                ? 'No teme a los lomos densos ni a las epopeyas de mil páginas.'
                : 'Se mueve ágil entre volúmenes de ritmo vivo.',
        ];

        $traits[] = [
            'name' => 'Constancia',
            'value' => max(5, 90 - ($pctAbandoned * 2)),
            'blurb' => $pctAbandoned >= 15
                ? 'Algunos caminos se abandonan; el archive lo recuerda sin juicio.'
                : 'Rara vez deja un camino a medias en el bosque de papel.',
        ];

        $authors = (int) ($stats['authors'] ?? 0);
        $books = max(1, (int) ($stats['books'] ?? 1));
        $diversity = min(99, (int) round(($authors / $books) * 100));
        $traits[] = [
            'name' => 'Polifonía',
            'value' => max(10, $diversity),
            'blurb' => $diversity >= 50
                ? 'Invoca muchas voces: el coro de autores es amplio.'
                : 'Hay casas editoriales y autores que pesan como anclas favoritas.',
        ];

        return $traits;
    }

    private static function affinities(array $stats): array
    {
        $out = [];
        foreach (array_slice($stats['by_genre'] ?? [], 0, 4) as $row) {
            $out[] = [
                'type' => 'género',
                'label' => (string) $row['label'],
                'total' => (int) $row['total'],
            ];
        }
        foreach (array_slice($stats['by_language'] ?? [], 0, 3) as $row) {
            if (($row['label'] ?? '') === 'Sin idioma') {
                continue;
            }
            $out[] = [
                'type' => 'idioma',
                'label' => (string) $row['label'],
                'total' => (int) $row['total'],
            ];
        }
        foreach (array_slice($stats['top_authors'] ?? [], 0, 3) as $row) {
            $out[] = [
                'type' => 'autor',
                'label' => (string) $row['label'],
                'total' => (int) $row['total'],
            ];
        }
        return $out;
    }

    private static function questLog(array $stats, int $pctRead, int $pctUnread): array
    {
        $books = (int) ($stats['books'] ?? 0);
        $unread = (int) ($stats['unread'] ?? 0);
        $reading = (int) ($stats['reading'] ?? 0);
        $wishlist = (int) ($stats['wishlist'] ?? 0);
        $series = (int) ($stats['series'] ?? 0);
        $reread = (int) ($stats['reread'] ?? 0);
        $pagesRead = (int) ($stats['pages_read'] ?? 0);

        $quests = [
            [
                'status' => $pctRead >= 50 ? 'done' : 'active',
                'name' => 'Dominio del Archive',
                'detail' => 'Alcanzar 50% de volúmenes leídos (' . $pctRead . '% actual).',
            ],
            [
                'status' => $unread === 0 && $books > 0 ? 'done' : 'active',
                'name' => 'Ningún Tomo en la Sombra',
                'detail' => $unread > 0
                    ? "Quedan {$unread} libros sin abrir en las estanterías."
                    : 'Todos los ejemplares han sido al menos marcados como leídos.',
            ],
            [
                'status' => $reading > 0 ? 'active' : 'idle',
                'name' => 'Fuegos Activos',
                'detail' => $reading > 0
                    ? "Hay {$reading} lectura(s) en curso sobre el atril."
                    : 'Ninguna llama encendida: el atril espera un nuevo grimorio.',
            ],
            [
                'status' => $wishlist > 0 ? 'active' : 'idle',
                'name' => 'Mapa de Deseos',
                'detail' => $wishlist > 0
                    ? "La wishlist guarda {$wishlist} reliquias por reclamar."
                    : 'El mapa de deseos está en blanco… por ahora.',
            ],
            [
                'status' => $series > 0 ? 'active' : 'idle',
                'name' => 'Sagas Incompletas',
                'detail' => $series > 0
                    ? "Hay {$series} saga(s)/serie(s) registradas en el archive."
                    : 'Aún no se han consignado sagas formales.',
            ],
            [
                'status' => $reread > 0 ? 'done' : 'active',
                'name' => 'El Eco del Favorito',
                'detail' => $reread > 0
                    ? "Ya hay {$reread} relectura(s): el rito del regreso está vivo."
                    : 'Releé un libro amado y registralo: el Nest honra los ecos.',
            ],
            [
                'status' => $pagesRead >= 10000 ? 'done' : 'active',
                'name' => 'Diez Mil Pasos de Tinta',
                'detail' => $pagesRead >= 10000
                    ? "Superaste las 10.000 páginas leídas ({$pagesRead})."
                    : 'Acumulá 10.000 páginas leídas en el contador del archive (' . format_number($pagesRead) . ' ahora).',
            ],
        ];

        return $quests;
    }

    private static function chronicle(
        array $archetype,
        array $stats,
        array $traits,
        array $affinities,
        int $pctRead,
        int $pctUnread,
        int $pctReading,
        array $level,
        array $house,
        string $motto
    ): string {
        $books = (int) ($stats['books'] ?? 0);
        $pages = (int) ($stats['pages'] ?? 0);
        $pagesRead = (int) ($stats['pages_read'] ?? 0);
        $authors = (int) ($stats['authors'] ?? 0);
        $read = (int) ($stats['read'] ?? 0);
        $unread = (int) ($stats['unread'] ?? 0);
        $reading = (int) ($stats['reading'] ?? 0);
        $abandoned = (int) ($stats['abandoned'] ?? 0);
        $reread = (int) ($stats['reread'] ?? 0);
        $avgPages = (int) ($stats['avg_pages'] ?? 0);
        $genres = (int) ($stats['genres'] ?? 0);

        $topGenre = 'lo desconocido';
        $topAuthor = null;
        $topLang = null;
        foreach ($affinities as $a) {
            if ($a['type'] === 'género' && $topGenre === 'lo desconocido') {
                $topGenre = $a['label'];
            }
            if ($a['type'] === 'autor' && $topAuthor === null) {
                $topAuthor = $a['label'];
            }
            if ($a['type'] === 'idioma' && $topLang === null) {
                $topLang = $a['label'];
            }
        }

        if ($books === 0) {
            return "En la sala de pergamino solo resuena el eco. El archive de BookNest está listo: "
                . "falta el primer volumen para que despierte la magia del catálogo.\n\n"
                . "Cuando llegue, las crónicas abrirán un capítulo nuevo. Hasta entonces, el Guardián del Umbral "
                . "vigila la puerta con paciencia de bibliotecario eterno.\n\n"
                . "— *Así empieza toda leyenda: con un estante vacío y una promesa.*";
        }

        $lines = [];

        $lines[] = "Las crónicas del Nest nombran a esta biblioteca doméstica como **{$archetype['title']}** — "
            . "{$archetype['epithet']}. Bajo el estandarte de **{$house['name']}**, con sigilo en **{$house['sigil']}**, "
            . "el lector avanza con el lema grabado en la guarda: *«{$motto}»*.";

        $lines[] = "En sus anaqueles conviven **{$books} ejemplares**, un océano aproximado de **{$pages} páginas**, "
            . "y el coro de **{$authors} autores**"
            . ($genres > 0 ? " repartidos en **{$genres} géneros** etiquetados" : '')
            . ". El promedio de los lomos ronda las **{$avgPages} páginas**: "
            . ($avgPages >= 400
                ? 'territorio de epopeyas y resistencias largas.'
                : ($avgPages >= 250
                    ? 'territorio de novelas de aliento medio, perfectas para noches enteras.'
                    : 'territorio ágil, de lecturas que entran como flechas limpias.'));

        $lines[] = "El oráculo del progreso declara sin adornos: **{$pctRead}%** del reino ya fue recorrido "
            . "({$read} tomos), **{$pctUnread}%** permanece sellado ({$unread} pendientes)"
            . ($pctReading > 0 ? ", y **{$pctReading}%** arde ahora mismo sobre el atril ({$reading} en curso)" : ', y el atril espera una nueva llama')
            . ". "
            . ($abandoned > 0
                ? "Hay también **{$abandoned}** caminos truncados: no como fracaso, sino como mapa de lo que no pidió ser terminado."
                : "Casi no hay abandonos: la constancia es un hechizo discreto pero poderoso.");

        if ($pagesRead > 0) {
            $lines[] = "Si se contaran los pasos de tinta ya dados, el contador marcaría cerca de **{$pagesRead} páginas leídas**. "
                . "Eso no es solo estadística: es tiempo robado al ruido, conversaciones con fantasmas generosos, "
                . "y la prueba de que este archive no es decoración sino brújula.";
        }

        if ($topAuthor) {
            $lines[] = "Entre los aliados recurrentes destaca **{$topAuthor}**, cuya presencia marca el mapa personal "
                . "como una torre recurrente en el horizonte. "
                . ($topLang
                    ? "La lengua que más resuena en estos salones es **{$topLang}**, y con ella el Nest habla en voz íntima."
                    : "Las lenguas del catálogo aún se revelan a medias, pero las voces ya tienen favoritos.");
        } else {
            $lines[] = "La afinidad elemental más clara apunta hacia **{$topGenre}**, como una runa grabada "
                . "en la mesa de lectura: no es el único camino, pero es el que el archive repite con más fervor.";
        }

        if ($topAuthor && $topGenre !== 'lo desconocido') {
            $lines[] = "Si el Nest tuviera un clima, sería el de **{$topGenre}**: esa estación vuelve una y otra vez, "
                . "tiñendo wishlist, estantes y conversaciones. No es prisión: es hogar. Y desde el hogar se parten expediciones.";
        }

        $voracity = (int) ($traits[0]['value'] ?? 50);
        $treasury = (int) ($traits[1]['value'] ?? 50);
        if ($voracity >= 70) {
            $lines[] = "Los bardos del barrio dicen que pocos grimorios resisten su ritmo: la **Voracidad** es alta "
                . "y el atril casi no enfría. Hay magia en terminar; hay también magia en saber cuándo cerrar el día "
                . "con un marcador y no con culpa.";
        } elseif ($treasury >= 70) {
            $lines[] = "Prefiere reunir reliquias antes de gastarlas: el horizonte de pendientes es un tesoro "
                . "tan valioso como lo ya leído. En la **Tesorería** del Nest, cada tapa intacta es una promesa "
                . "futura — y las promesas, bien cuidadas, también alimentan.";
        } else {
            $lines[] = "Avanza con pulso de mago paciente: ni precipita el final ni deja que el polvo cubra "
                . "demasiado tiempo un tomo abierto. Entre consumir y coleccionar, ha encontrado un ritmo propio, "
                . "ese equilibrio raro que las crónicas celebran más que cualquier récord.";
        }

        if ($reread > 0) {
            $lines[] = "Y hay un detalle que los escribas subrayan dos veces: **{$reread} relectura(s)**. "
                . "Volver a un libro es declarar que el mundo cabía en esas páginas más de una vez. "
                . "Eso, en cualquier escuela de magia doméstica, es rango de lealtad.";
        }

        $lines[] = "Hoy el Perfil Lector lo registra como **{$level['rank']}**, nivel **{$level['number']}**, "
            . "con **{$level['xp']} XP** de archive. No es un título de vanidad: es la suma de noches, "
            . "márgenes, búsquedas de ISBN y la pequeña ceremonia de marcar un libro como leído.";

        $lines[] = "Así queda escrito —con tinta de estantería y un poco de fantasía pastel— el retrato de este "
            . "Nest: disciplina sin dureza, curiosidad sin prisa, y la certeza de que cada tapa es una puerta. "
            . "Mientras haya un volumen sin abrir o una frase que merezca volver, la crónica continúa.\n\n"
            . "— *Fin del capítulo actual. El siguiente empieza en la próxima página que elijas.*";

        return implode("\n\n", $lines);
    }

    private static function level(int $books, int $pagesRead, int $pctRead): array
    {
        $xp = ($books * 3) + (int) floor($pagesRead / 50) + ($pctRead * 2);
        $level = max(1, min(99, (int) floor(sqrt($xp / 4)) + 1));
        $next = (int) pow($level, 2) * 4;
        $prev = (int) pow(max(0, $level - 1), 2) * 4;
        $span = max(1, $next - $prev);
        $into = max(0, $xp - $prev);
        $pct = (int) round(($into / $span) * 100);

        $ranks = [
            1 => 'Novicio del Lomo',
            5 => 'Aprendiz de Catálogo',
            10 => 'Escriba del Nest',
            15 => 'Bibliotecario Errante',
            20 => 'Custodio de Sagas',
            30 => 'Señor de los Estantes',
            40 => 'Archimago Doméstico',
            50 => 'Leyenda del Pergamino',
        ];
        $rank = 'Novicio del Lomo';
        foreach ($ranks as $min => $label) {
            if ($level >= $min) {
                $rank = $label;
            }
        }

        return [
            'number' => $level,
            'xp' => $xp,
            'pct' => min(100, $pct),
            'rank' => $rank,
        ];
    }
}
