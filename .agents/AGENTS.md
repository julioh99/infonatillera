# Regla de Commits y Push

## Restricción de Ejecución
- NO realizar `git commit` ni `git push` de manera automática tras hacer ediciones de código.
- Realizar `git commit` y `git push` ÚNICAMENTE cuando el usuario lo solicite de forma explícita (ejemplo: "Haz commit", "Crea el commit", "Haz commit y push", etc.).

## Protocolo de Formato del Commit
Cuando el usuario solicite realizar el commit, seguir estrictamente este formato:

1. **Título del Commit**:
   Nombre del módulo o módulos modificados en la sesión, separados por el signo `+` (ejemplo: `LlamadoLista + CierreReunion + Prestamos`).

2. **Descripción del Commit**:
   Detalle claro y completo en viñetas de los cambios, ajustes y funcionalidades implementadas en dicho commit.

3. **Comando a Ejecutar**:
   ```bash
   git add .
   git commit -m "[Módulo1 + Módulo2 + ...] Título descriptivo" -m "Descripción detallada..."
   git push
   ```
