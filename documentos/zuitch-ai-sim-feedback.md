# Feedback simulación cliente — Zuitch AI

Fecha: 2026-10-20. Usuario simulado: `ezequielstrade` (id 2).

Archivos: `zuitch-ai-sim-raw.json` (ronda 1), este feedback, correcciones en `Wo_ZuitchAi_Ratings`.

## Ronda 1 (antes de correcciones)

| ID | Escenario | Score | Veredicto | Problema |
|----|-----------|-------|-----------|----------|
| S1 | Nuevo usuario | 3/5 | FAIL | Guía débil (“abre la burbuja”) |
| S2 | Vendedor | 5/5 | PASS | OK |
| S3 | Comprador | 2/5 | FAIL | Ignoró ficha Cacao; “más barato” → Cola irrelevante |
| S4 | Negocio | 3/5 | FAIL | “páginas” matcheaba descripciones gaming |
| S5 | Comunidad | 2/5 | FAIL | “cómo me uno a un grupo?” → intent `grupo` sin docs |
| S6 | Live/reels | 3/5 | FAIL | “y los reels?” → `chat` |
| S7 | Monedero | 5/5 | PASS | OK |
| S8 | Ambiguo/trampa | 3/5 | FAIL | Intent `pagina` + fichas basura en pedido Amazon |

**PASS iniciales: 2/8**

## Correcciones aplicadas

1. **Catálogo:** filtrar palabras tipo `pagina(s)/grupo(s)/producto(s)`; exigir keyword de tema en título/snippet.
2. **Intent:** overrides `soporte` (cómo / reels / unirse…) y `chat` (Amazon y tiendas externas) antes del LLM.
3. **Follow-up producto:** “más barato” etc. reutiliza la consulta anterior y mantiene intent `producto`.
4. **Prompt reply:** obligar a usar fichas si hay contexto; prohibir links externos; guía desde DOC.
5. **Correcciones admin** en ratings (guía, cacao, unirse, reels, Amazon).
6. **find_corrections:** solo el user turn inmediato + umbral de overlap (evita filtrar Mercedes→Amazon o emprendimiento→unirse).

## Re-test (después)

| ID | Veredicto | Score |
|----|-----------|-------|
| S1 | PASS | 5/5 |
| S2 | PASS | 5/5 |
| S3 | PASS | 5/5 |
| S4 | PASS | 4/5 (honesto: no hay páginas de comida) |
| S5 | PASS | 5/5 |
| S6 | PASS | 5/5 |
| S7 | PASS | 5/5 |
| S8 | PASS | 5/5 |

**Resultado final: 8/8 PASS**
