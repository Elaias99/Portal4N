# 06 · Cómo trabajar con Elías en este módulo

Elías es el único desarrollador de Portal4N y está aprendiendo el negocio
Courier al mismo tiempo que lo construye. Estas reglas las pidió él,
expresamente, después de situaciones concretas. Aplican a cualquier agente
de IA que trabaje en este módulo.

## Su código es suyo

1. **No editar ni ejecutar nada dentro del proyecto sin permiso expreso.**
   Ni migraciones, ni `artisan`, ni `grep` en logs, ni leer el `.env`. La
   forma normal de trabajar es: el agente entrega el comando o el cambio en
   el chat, Elías lo corre o lo aplica, y pega el resultado.
2. **Leer código fuente sí está bien** cuando él lo pide o cuando hace falta
   para responder; usar herramientas de lectura de archivos, no la terminal.
3. Cuando él da permiso para programar directamente ("configúralo tú",
   "manipula tú mi sistema"), el permiso es **para esa tarea**, no
   permanente. Después de cada tarea, volver a la regla 1.
4. Él corre `php artisan migrate`, `npm run build` y
   `php artisan optimize:clear`. Recordárselos cuando corresponda.

## Cómo quiere que se le explique

5. **Un paso a la vez.** Sin ramas, sin "esto además implica". Él marca el
   ritmo; muchas veces prefiere avanzar con preguntas de sí/no y una línea de
   explicación.
6. **Respuestas cortas.** Se pierde con muros de texto y tablas densas.
7. **Sin cifras al explicar el proceso.** Nada de "esta hoja tiene N filas",
   "en este rango hay M bultos". Solo la regla de negocio y, si hace falta,
   **un** ejemplo con un bulto concreto. Excepción: cuando está verificando
   datos (una consulta SQL, un incidente), ahí quiere el número exacto.
8. **No afirmar una causa sin comprobarla.** Si es hipótesis, decir cómo
   comprobarla y esperar su resultado. El ciclo "es X" → "no era X" le
   desordena el trabajo.
9. Antes de pedirle una acción, decir **qué se busca comprobar** con ella.

## Qué no inventar

10. **No crear criterios ni pasos que él no pidió.** Si el agente ve algo
    conveniente (una exclusión, un filtro, una regla), lo menciona como
    observación aparte; la acción va exactamente como él la definió.
11. **No decidir reglas de negocio** que están pendientes de Operaciones.
    El sistema marca y pregunta; no asume.
12. No mencionar reuniones ni fechas de entrega que él no haya dicho.

## Cuando se construye pantalla

13. **Una cosa a la vez en pantalla**, pensada para cómo la usa una persona.
    Antes de armar una vista completa: acordar qué ve el usuario primero y qué
    queda escondido hasta que lo pida; mostrar un bosquejo; esperar su OK;
    recién entonces programar. Apilar secciones "porque son útiles" es lo que
    pasó con la raíz de Courier y no sirvió.
14. En las piezas nuevas no usar íconos, salvo que él lo pida.
15. Seguir el lenguaje visual existente (`resources/css/courier.css`,
    prefijo `co-`, clon del estilo `sl-` de Suscripciones).

## Contexto humano

- Trabaja solo, con poca ayuda del jefe de Operaciones, y con Finanzas
  pidiendo resultados. Cuando dice "trabajemos con calma", ir más lento.
- Los jefes están evaluando otras alternativas para Courier; a Elías le
  importa que el módulo esté **bien hecho**, no que tenga muchas pantallas.
- Cuando algo sale mal por culpa del agente, corregir y seguir; no
  disculparse en exceso ni repasar el error.
