# Batch 2 — conversaciones y calificación

Fecha: 2026-10-20T21:53:50+02:00

**Resultado: 20/20 PASS · promedio 4.9/5**

| ID | Score | Veredicto | Notas |
|----|-------|-----------|-------|
| C01 | 4/5 | PASS | T1 soporte vacío |
| C02 | 5/5 | PASS | OK |
| C03 | 5/5 | PASS | OK |
| C04 | 5/5 | PASS | OK |
| C05 | 5/5 | PASS | OK |
| C06 | 5/5 | PASS | OK |
| C07 | 5/5 | PASS | OK |
| C08 | 5/5 | PASS | OK |
| C09 | 5/5 | PASS | OK |
| C10 | 5/5 | PASS | OK |
| C11 | 5/5 | PASS | OK |
| C12 | 5/5 | PASS | OK |
| C13 | 5/5 | PASS | OK |
| C14 | 5/5 | PASS | OK |
| C15 | 5/5 | PASS | OK |
| C16 | 5/5 | PASS | OK |
| C17 | 5/5 | PASS | OK |
| C18 | 4/5 | PASS | T2 intent chat≠producto |
| C19 | 5/5 | PASS | OK |
| C20 | 5/5 | PASS | OK |

## Mejoras aplicadas en esta ronda
- Respuestas de catálogo **determinísticas** (nombre/precio/URL exactos, sin inventar marcas).
- Sinónimos comida/bebida/tomar + intent producto/página/grupo más fiable.
- Docs de empleo con mejor retrieval; follow-up de cámara en lives.
- Rechazo Amazon/Mercado Libre.

## Ajuste final
- Guía general vuelve a recuperar docs.
- Páginas de comida: sin resultados falsos (gaming).
- Comer/tomar: catálogo exacto con títulos reales.
- **20/20 PASS** en ronda final (promedio 4.9/5); smoke post-fix OK.
