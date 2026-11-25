# 10. Identidad visual y logo 2027

## Paleta (solo 2 colores de marca)
- **Verde pino** `#14573D`: color principal (menú, botones, encabezados, cifras clave).
- **Dorado caléndula** `#E8A317`: acento puntual (un detalle por pantalla, no más).
- Neutros: blanco, gris verdoso muy claro para fondos y un verde casi negro para el texto.
- Rojo solo para errores y alertas reales, nunca como decoración.

## Elección del logo (mesa directiva)
1. Se generan 3 propuestas con Gemini usando los prompts de abajo (una imagen por prompt) y se comparan con las 3 propuestas SVG de `public/img/logo/` (se ven en `/logos.html`).
2. Se ven juntas a tamaño grande y reducidas a 32 px (el logo debe seguir reconociéndose).
3. Votación de la mesa directiva; el ganador se vectoriza y se pone en el menú, el login y el ícono de la aplicación.

## Prompt 1 · Monograma (símbolo compacto)
```
Diseña el logotipo de "InfoNatillera", una plataforma web para una natillera colombiana: un grupo comunitario de 50 socios que se reúne cada 15 días para ahorrar, hacer préstamos entre ellos y repartir rondas y rifas. Quiero una marca premium, sobria y minimalista.

Concepto: un monograma de las letras "I" y "N" unidas en un solo trazo continuo, dentro de una forma de moneda o círculo, donde el espacio negativo sugiere un brote o una hoja que crece (ahorro que crece). Un único detalle dorado: un punto o una pequeña hoja.

Paleta, solo 2 colores: verde pino profundo (#14573D) y dorado caléndula (#E8A317), sobre fondo blanco liso.
Estilo: vector plano, geometría limpia, trazos de grosor uniforme, esquinas suavemente redondeadas, sin degradados, sin sombras, sin efectos 3D, sin texturas.
Condiciones: tiene que verse bien reducido a 32 px, ser simétrico y reconocible en una sola tinta. Nada de alcancías con forma de cerdito, billetes ni símbolos de dólar. Sin texto adicional dentro del símbolo.
Entrega: una sola imagen cuadrada 1:1, el símbolo centrado con mucho aire alrededor, y debajo "InfoNatillera" en una tipografía geométrica sans-serif en negrita (estilo Plus Jakarta Sans o similar).
```

## Prompt 2 · Comunidad (círculo de socios)
```
Crea el logotipo de "InfoNatillera", la plataforma de una natillera colombiana (grupo comunitario de 50 socios que ahorran juntos, se prestan con interés y sortean rondas y rifas). Debe transmitir confianza, comunidad y transparencia, con un acabado premium y minimalista.

Concepto: un anillo formado por pequeñas figuras o puntos idénticos, que representan a los socios, dispuestos en círculo alrededor de una moneda central. Uno solo de los puntos es dorado: es el beneficiado del turno. La composición debe sugerir un ciclo que gira, como una ronda.

Paleta, solo 2 colores: verde pino profundo (#14573D) y dorado caléndula (#E8A317), sobre fondo blanco liso.
Estilo: vector plano y geométrico, proporciones exactas, sin degradados, sin sombras, sin efectos 3D. Pocos elementos: tiene que leerse con claridad a 32 px, así que si hace falta simplifica el anillo a menos puntos.
Evita: personas dibujadas con detalle, manos, caritas, cerditos alcancía, billetes y signos de dólar.
Entrega: una sola imagen cuadrada 1:1 con el símbolo centrado y amplio espacio libre, y debajo el nombre "InfoNatillera" en sans-serif geométrica en negrita, en verde pino.
```

## Prompt 3 · Logotipo tipográfico (solo nombre)
```
Diseña un logotipo puramente tipográfico (wordmark) para "InfoNatillera", la plataforma de gestión de una natillera colombiana: ahorro comunitario, préstamos entre socios, rondas y rifas. Quiero algo elegante, moderno y minimalista, de nivel premium, como la marca de una fintech seria pero cercana.

Concepto: el nombre en una sola línea, en una sans-serif geométrica de peso semibold con espaciado entre letras ajustado. La "o" de "Info" se transforma en una moneda dorada con un pequeño corte o brote en su interior. Las demás letras en verde pino profundo. Sin ningún otro adorno ni símbolo aparte.

Paleta, solo 2 colores: verde pino profundo (#14573D) y dorado caléndula (#E8A317), sobre fondo blanco liso.
Estilo: vector plano, sin degradados, sin sombras, sin biseles. Alineación y kerning cuidadosos; debe leerse bien incluso a 120 px de ancho.
Entrega: una imagen horizontal 3:1 con el wordmark centrado; después, en la misma imagen y más pequeño, una versión del logotipo en blanco sobre fondo verde pino (#14573D) para ver cómo funciona en negativo.
```
