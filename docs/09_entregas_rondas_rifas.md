# 🎁 Módulo de Entregas, Desembolsos de Préstamos y Firma Digital

## 📌 Propósito del Módulo
Garantizar la transparencia contable y la evidencia jurídica en el soporte de entregas de **Préstamos (Desembolsos de Crédito)** (opción principal por defecto), **Rondas ($300.000 COP)** y **Rifas ($150.000 COP)** a los socios de la natillera comunitaria a lo largo del año.

---

## ⚙️ Reglas de Negocio y Evidencias

1. **Desembolso de Préstamos (Primera Opción):**
   - Permite seleccionar a cualquier socio deudor y registrar la constancia digital del dinero entregado.
   - Cuenta con acceso directo desde el módulo de Préstamos (`/admin/prestamos`) mediante el botón *"✍ Firma/Foto"*.
2. **Ahorro Neto Constante del Socio:**
   - Sin importar si la cuota quincenal es de $55.000, $60.000 o $65.000 COP, el **Ahorro Neto acreditado al socio para fin de año es SIEMPRE $40.000 COP**.
   - Desglose por quincena:
     - **Cuota $55.000:** $40.000 Ahorro + $10.000 Fondo Ronda + $5.000 Fondo Rifa.
     - **Cuota $60.000:** $40.000 Ahorro + $10.000 Fondo Ronda + $10.000 Fondo Rifa (Especial Rifa).
     - **Cuota $65.000:** $40.000 Ahorro + $20.000 Fondo Ronda + $5.000 Fondo Rifa (Especial Ronda).

3. **Cronograma y Saldo Acumulado de Premios:**
   - **Ronda ($300.000 COP):** Recauda $500.000 COP en quincenas regulares. Sobran $200.000 que pasan al acumulado para liberar 2 o 3 socios cuando el saldo lo permite.
   - **Rifa ($150.000 COP):** Recauda $250.000 COP en quincenas regulares. Sobran $100.000 que se acumulan para quincenas especiales.

4. **Firma Digital y Foto Evidencia:**
   - Pad táctil HTML5 Canvas para firma digital del socio sobre celular o PC.
   - Captura de foto evidencia con la cámara o archivo.
   - Almacenamiento seguro en `/uploads/firmas/` y `/uploads/evidencias/`.

---
[⬅️ Anterior: Dashboard del Socio](./08_dashboard_socio.md) | [📋 Índice](./README.md)
