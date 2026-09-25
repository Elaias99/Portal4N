---

name: portal4n-suscripciones
description: Usa esta skill cuando una tarea involucre el módulo Suscripciones de Portal4N, incluyendo generación mensual, liquidaciones, pre-facturas, novedades, ajustes, zonas, OPV, proveedores, transportistas o archivos Excel mensuales utilizados como referencia operativa.
-------------------------------------

# Portal4N — Suscripciones

## Propósito

Ayudar a analizar, desarrollar y mantener el módulo Suscripciones de Portal4N respetando sus reglas de negocio, documentación y restricciones de seguridad.

La documentación existente del módulo es la fuente principal de conocimiento:

`storage/agents/docs/suscripciones/`

También deben respetarse las instrucciones generales definidas en:

`AGENTS.md`

## Modo de trabajo por defecto

Trabaja siempre en **modo solo lectura** mientras el usuario no autorice explícitamente una modificación.

En modo solo lectura puedes:

* leer código;
* buscar archivos y referencias;
* revisar rutas, modelos, controladores, servicios, vistas y JavaScript;
* leer la documentación del módulo;
* analizar archivos Excel proporcionados para el período;
* inspeccionar diferencias con Git;
* ejecutar comandos estrictamente no destructivos;
* proponer cambios;
* explicar qué archivos sería necesario modificar.

## Modificación de código

**No modifiques ningún archivo del repositorio por iniciativa propia.**

Solo puedes editar código cuando el usuario lo solicite explícitamente.

Una autorización para modificar un archivo o resolver un problema específico:

* no autoriza otros cambios;
* no autoriza refactorizaciones adicionales;
* no autoriza modificar documentación no relacionada;
* no autoriza modificar otros módulos;
* no autoriza operaciones Git de publicación.

Antes de modificar código, identifica:

1. la regla de negocio afectada;
2. los archivos involucrados;
3. el cambio que se propone realizar;
4. las validaciones o pruebas necesarias.

## Git

Puedes utilizar operaciones de Git de solo lectura, por ejemplo:

* `git status`;
* `git diff`;
* `git log`;
* `git show`;
* inspeccionar ramas;
* inspeccionar cambios existentes.

**No ejecutes por iniciativa propia:**

* `git add`;
* `git commit`;
* `git push`;
* `git merge`;
* `git rebase`;
* `git cherry-pick`;
* creación o eliminación de tags;
* creación o eliminación de ramas;
* apertura o merge de Pull Requests;
* cualquier operación que publique cambios en un repositorio remoto.

Aunque el usuario autorice una modificación de código, eso **no implica autorización para hacer commit o push**.

Commit, push, creación de ramas o Pull Requests requieren una instrucción explícita e independiente del usuario.

## Producción y datos

Por ningún motivo:

* tocar producción;
* hacer deploy;
* escribir en la base de datos de producción;
* modificar datos reales sin autorización explícita;
* ejecutar migraciones destructivas;
* ejecutar comandos destructivos;
* enviar correos reales;
* llamar OneDrive real;
* consumir integraciones externas que puedan generar efectos reales;
* inventar reglas de negocio.

## Documentación

Cuando trabajes con Suscripciones, utiliza la documentación existente en:

`storage/agents/docs/suscripciones/`

No dupliques esa documentación dentro de esta skill.

Carga solamente los documentos necesarios según la tarea.

Para procesos relacionados con Excel históricos, conciliación, regeneración o validación de períodos, consulta especialmente:

`storage/agents/docs/suscripciones/pruebas.md`

## Excel mensuales

Los archivos Excel mensuales son datos de entrada o referencia del período, no reglas permanentes de la skill.

Cuando se proporcione un Excel:

* identifica el período;
* inspecciona su estructura;
* no modifiques el archivo original;
* no asumas que todos los meses tienen exactamente la misma estructura;
* compara su información utilizando las reglas documentadas del módulo;
* informa cualquier ambigüedad antes de convertirla en una regla de negocio.

## Regla fundamental

Si existe duda sobre si una acción es de solo lectura o puede producir una modificación, efecto externo o cambio persistente:

**no la ejecutes y limita el trabajo al análisis hasta recibir una instrucción explícita del usuario.**
