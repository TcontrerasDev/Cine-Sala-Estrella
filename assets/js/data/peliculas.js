/**
 * peliculas.js — Datos de cartelera Cine Sala Estrella
 * Fuente única de verdad para todas las películas.
 *
 * Estructura de cada objeto:
 * {
 *   id:           number     — identificador único
 *   titulo:       string     — título en español (o original)
 *   genero:       string     — "accion" | "drama" | "familiar" | "terror" | "documental" | "thriller" | "romance"
 *   clasificacion: string    — "ATP" | "TE+7" | "TE+14" | "TE+18"
 *   sinopsis:     string     — descripción breve
 *   poster:       string     — URL del póster (placehold.co durante desarrollo)
 *   duracion:     string     — "XhYm"
 *   director:     string
 *   sedes:        string[]   — ["pa"] | ["pn"] | ["pa","pn"]
 *   horarios:     { pa: string[], pn: string[] }
 *   trailer:      string     — URL de embed YouTube
 *   enEstreno:    boolean    — true = badge ESTRENO
 *   proximamente: boolean    — true = no en cartelera aún
 * }
 */

window.PELICULAS = [

  /* ─── EN CARTELERA (8) ──────────────────────────────── */

  {
    id: 1,
    titulo: "Sinners",
    genero: "terror",
    clasificacion: "TE+14",
    sinopsis: "Dos gemelos regresan al sur profundo de los años treinta para abrir un club y comenzar desde cero. Pero al invitar a desconocidos a celebrar, descubren que han atraído a algo mucho más antiguo y peligroso que cualquier problema que dejaron atrás.",
    poster: "https://placehold.co/300x450/0D2B1A/27AE60?text=SINNERS",
    duracion: "2h17m",
    director: "Ryan Coogler",
    sedes: ["pa", "pn"],
    horarios: {
      pa: ["15:00", "18:30", "22:00"],
      pn: ["17:00", "21:00"]
    },
    trailer: "https://www.youtube.com/embed/JuXGFQZRcGk",
    enEstreno: true,
    proximamente: false
  },

  {
    id: 2,
    titulo: "Thunderbolts*",
    genero: "accion",
    clasificacion: "TE+14",
    sinopsis: "Un grupo de antihroes y villanos reformados de Marvel se une en una operación suicida que podría cambiar el destino del planeta. Sin recursos ni apoyo oficial, deberán confiar los unos en los otros para sobrevivir.",
    poster: "https://placehold.co/300x450/1A0D2B/C0392B?text=THUNDERBOLTS",
    duracion: "2h7m",
    director: "Jake Schreier",
    sedes: ["pa", "pn"],
    horarios: {
      pa: ["14:00", "17:00", "20:00", "23:00"],
      pn: ["16:00", "20:30"]
    },
    trailer: "https://www.youtube.com/embed/Xb2wJBSj3H0",
    enEstreno: true,
    proximamente: false
  },

  {
    id: 3,
    titulo: "Misión: Imposible — La Sentencia Final",
    genero: "accion",
    clasificacion: "TE+14",
    sinopsis: "Ethan Hunt y el equipo IMF se enfrentan a su misión más devastadora: desactivar una inteligencia artificial desbocada que amenaza con reescribir la historia del mundo. El tiempo se agota y las reglas del juego han cambiado para siempre.",
    poster: "https://placehold.co/300x450/0D1A2B/4A90D9?text=M%3AI+8",
    duracion: "2h49m",
    director: "Christopher McQuarrie",
    sedes: ["pa"],
    horarios: {
      pa: ["13:30", "17:00", "21:30"],
      pn: []
    },
    trailer: "https://www.youtube.com/embed/avz06PDqDbM",
    enEstreno: false,
    proximamente: false
  },

  {
    id: 4,
    titulo: "Bailarina",
    genero: "accion",
    clasificacion: "TE+18",
    sinopsis: "Eve Macarro, una joven bailarina entrenada como asesina, emprende una implacable búsqueda de venganza contra los responsables de la muerte de su familia. Un capítulo brutal y elegante del universo John Wick.",
    poster: "https://placehold.co/300x450/2B0D1A/E74C3C?text=BAILARINA",
    duracion: "1h55m",
    director: "Len Wiseman",
    sedes: ["pa", "pn"],
    horarios: {
      pa: ["16:00", "20:00"],
      pn: ["15:30", "19:30"]
    },
    trailer: "https://www.youtube.com/embed/Xb2wJBSj3H1",
    enEstreno: false,
    proximamente: false
  },

  {
    id: 5,
    titulo: "28 Años Después",
    genero: "terror",
    clasificacion: "TE+14",
    sinopsis: "Casi tres décadas después de que el virus de la Rabia diezmara Gran Bretaña, una pequeña comunidad aislada descubre que el mundo exterior no es lo que recuerda. Danny Boyle regresa para reinventar el apocalipsis zombi.",
    poster: "https://placehold.co/300x450/1A0D0D/E67E22?text=28+A%C3%91OS",
    duracion: "1h55m",
    director: "Danny Boyle",
    sedes: ["pa", "pn"],
    horarios: {
      pa: ["14:30", "18:00", "22:30"],
      pn: ["18:00", "22:00"]
    },
    trailer: "https://www.youtube.com/embed/xsA8TkPW3h0",
    enEstreno: false,
    proximamente: false
  },

  {
    id: 6,
    titulo: "Lilo & Stitch",
    genero: "familiar",
    clasificacion: "ATP",
    sinopsis: "La versión live-action de la historia de amor y familia entre Lilo, una niña hawaiana solitaria, y Stitch, el experimento alienígena número 626 que aprende el significado de 'ohana' — familia, y la familia no abandona a nadie.",
    poster: "https://placehold.co/300x450/0D2B2B/27AE60?text=LILO+%26+STITCH",
    duracion: "1h48m",
    director: "Dean Fleischer Camp",
    sedes: ["pa", "pn"],
    horarios: {
      pa: ["12:00", "15:30", "19:00"],
      pn: ["13:00", "17:00"]
    },
    trailer: "https://www.youtube.com/embed/HKH7YOxSAGQ",
    enEstreno: false,
    proximamente: false
  },

  {
    id: 7,
    titulo: "Materialists",
    genero: "romance",
    clasificacion: "TE+14",
    sinopsis: "Una matchmaker de alto perfil en Nueva York confronta sus propias contradicciones emocionales cuando el amor entra en conflicto con todo lo que le enseñaron sobre relaciones perfectas. Celine Song explora el corazón con su mirada precisa e íntima.",
    poster: "https://placehold.co/300x450/2B2B0D/F5F0E8?text=MATERIALISTS",
    duracion: "1h44m",
    director: "Celine Song",
    sedes: ["pa"],
    horarios: {
      pa: ["13:00", "16:30", "20:30"],
      pn: []
    },
    trailer: "https://www.youtube.com/embed/Xb2wJBSj3H2",
    enEstreno: false,
    proximamente: false
  },

  {
    id: 8,
    titulo: "Novocaine",
    genero: "thriller",
    clasificacion: "TE+14",
    sinopsis: "Un hombre con una extraña condición que le impide sentir dolor se convierte en el arma más impredecible cuando secuestran a su novia. Sin miedo al daño físico, emprende una imparable búsqueda en el corazón de la ciudad.",
    poster: "https://placehold.co/300x450/0D0D1A/8A8A8A?text=NOVOCAINE",
    duracion: "1h50m",
    director: "Dan Berk, Robert Olsen",
    sedes: ["pa", "pn"],
    horarios: {
      pa: ["15:00", "19:30", "23:00"],
      pn: ["16:30", "21:00"]
    },
    trailer: "https://www.youtube.com/embed/Xb2wJBSj3H3",
    enEstreno: false,
    proximamente: false
  },

  /* ─── PRÓXIMAMENTE (4) ──────────────────────────────── */

  {
    id: 9,
    titulo: "Superman",
    genero: "accion",
    clasificacion: "TE+14",
    sinopsis: "James Gunn reimagina al Hombre de Acero en una épica contemporánea. Clark Kent navega su dualidad entre divinidad y humanidad mientras enfrenta amenazas que desafían los límites del heroísmo. El inicio del nuevo Universo DC.",
    poster: "https://placehold.co/300x450/0D1A2B/4A90D9?text=SUPERMAN",
    duracion: "2h12m",
    director: "James Gunn",
    sedes: ["pa", "pn"],
    horarios: { pa: [], pn: [] },
    trailer: "https://www.youtube.com/embed/m9j-WhUKRGs",
    enEstreno: false,
    proximamente: true
  },

  {
    id: 10,
    titulo: "Los 4 Fantásticos: Primeros Pasos",
    genero: "accion",
    clasificacion: "ATP",
    sinopsis: "Los Cuatro Fantásticos hacen su debut en el Universo Cinematográfico de Marvel en una aventura ambientada en los años sesenta retrofuturistas. Reed Richards, Sue Storm, Johnny Storm y Ben Grimm enfrentan una amenaza cósmica sin precedentes.",
    poster: "https://placehold.co/300x450/1A1A0D/E67E22?text=4+FANT%C3%81STICOS",
    duracion: "2h20m",
    director: "Matt Shakman",
    sedes: ["pa", "pn"],
    horarios: { pa: [], pn: [] },
    trailer: "https://www.youtube.com/embed/Vl64SFPe_oA",
    enEstreno: false,
    proximamente: true
  },

  {
    id: 11,
    titulo: "El Mundo Jurásico: Resurgimiento",
    genero: "accion",
    clasificacion: "TE+14",
    sinopsis: "Cinco años después del colapso de Jurassic World Dominion, Zora Bennett lidera una misión clandestina para extraer material genético de tres superdinosaurios de la selva amazónica. La naturaleza, sin embargo, tiene otros planes.",
    poster: "https://placehold.co/300x450/0D2B0D/E74C3C?text=MUNDO+JUR%C3%81SICO",
    duracion: "2h1m",
    director: "Gareth Edwards",
    sedes: ["pa", "pn"],
    horarios: { pa: [], pn: [] },
    trailer: "https://www.youtube.com/embed/n-J5uR_W_7E",
    enEstreno: false,
    proximamente: true
  },

  {
    id: 12,
    titulo: "Zootopia 2",
    genero: "familiar",
    clasificacion: "ATP",
    sinopsis: "Judy Hopps y Nick Wilde regresan en una nueva aventura donde los mundos de los animales salvajes y los domésticos chocan inesperadamente. Un caso nuevo, un misterio más profundo y la misma química irresistible que conquistó el mundo.",
    poster: "https://placehold.co/300x450/2B1A0D/27AE60?text=ZOOTOPIA+2",
    duracion: "1h48m",
    director: "Byron Howard, Rich Moore",
    sedes: ["pa", "pn"],
    horarios: { pa: [], pn: [] },
    trailer: "https://www.youtube.com/embed/Xb2wJBSj3H4",
    enEstreno: false,
    proximamente: true
  }

];
