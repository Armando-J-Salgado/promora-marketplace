<div align="center">

# Diseño de un Motor de Códigos Promocionales

</div>

Caso de Estudio — Examen Final (ASD)

Documento: TDR-PROMO-001

Fecha: 30 de junio de 2026

Estado: Aprobado

Audiencia: Estudiantes de Ingeniería de Software — Curso: Patrones de Diseño

## Antecedentes

Promora Marketplace es una comercializadora de servicios en línea que conecta compradores con proveedores de servicios digitales. Como parte de su estrategia de adquisición y retención de clientes, el equipo de Producto requiere un motor de códigos promocionales capaz de otorgar descuentos en ordenes de compra.

El reto no es solo calcular un descuento: es que marketing pueda lanzar campañas nuevas sin depender de un despliegue de código. Hoy, cualquier regla nueva de promoción implica abrir un ticket de desarrollo. Promora necesita un motor donde las reglas de negocio se configuren, no se programen.

No existe un sistema legacy que refactorizar. Este es un diseño desde cero: el equipo de arquitectura (ustedes) debe proponer la solución completa a partir de los requerimientos descritos en este documento.

## Objetivo

Diseñar un motor de códigos promocionales extensible, testeable y desacoplado, aplicando principios SOLID y patrones de diseño, que sea capaz de:

1. Validar si un código es elegible para una orden concreta.

2. Calcular el monto de descuento que corresponde aplicar.

3. Integrarse con cualquier entidad de orden de la plataforma, sin acoplarse a una implementación concreta.

## 1. Alcance del Sistema

1. 1 Tipos de descuento soportados

<table border="1"><tr><td>Tipo</td><td>Descripción</td></tr><tr><td>fixed</td><td>Monto fijo en dólares. Ej: $10 de descuento.</td></tr><tr><td>percent</td><td>Porcentaje sobre el subtotal. Ej: 15% de descuento.</td></tr><tr><td>tiered</td><td>Porcentaje variable según el historial de ordenes del comprador.</td></tr></table>

## 1.2 Reglas de validación configurables por código

Cada código puede tener cero o más de las siguientes restricciones activas. Las reglas se activan por configuración en base de datos; agregar una regla a un código no requiere cambios en código fuente.

<table border="1"><tr><td>Clave de regla</td><td>Regla de negocio</td></tr><tr><td>min_purchase_amount</td><td>El subtotal de la orden debe superar un mínimo.</td></tr><tr><td>eligible_categories</td><td>El código aplica solo a categorías de servicio específicas, con soporte de jerarquía padre/hijo.</td></tr><tr><td>first_order_only</td><td>Solo puede usarse en la primera orden del comprador en la plataforma.</td></tr><tr><td>user_usage_limit</td><td>Número máximo de veces que un mismo usuario puede usar el código.</td></tr><tr><td>global_usage_limit</td><td>Número máximo de usos totales del código entre todos los usuarios.</td></tr><tr><td>global_amount_limit</td><td>Límite de descuento acumulado otorgado por este código en toda la plataforma.</td></tr><tr><td>restricted_usage</td><td>El código está asignado explícitamente a un subconjunto de usuarios.Solo ellos pueden usarlo.</td></tr></table>

## 1.3 Reglas de post-cálculo

<table border="1"><tr><td>Clave de regla</td><td>Comportamiento</td></tr><tr><td>max_discount_amount</td><td>Tope máximo en dólares del descuento calculado independientemente del tipo.</td></tr></table>

## 1.4 Ciclo de vida del código

<table border="1"><tr><td>draft → active → expired</td></tr><tr><td>paused</td></tr></table>

<table border="1"><tr><td>Estado</td><td>Descripción</td></tr><tr><td>draft</td><td>Creado, no disponible al público.</td></tr><tr><td>active</td><td>Válido para uso, sujeto a fechas de inicio y expiración.</td></tr><tr><td>paused</td><td>Suspendido temporalmente por operaciones.</td></tr></table>

<table border="1"><tr><td>Estado</td><td>Descripción</td></tr><tr><td>expired</td><td>Fuera de vigencia.</td></tr></table>

## 2. Restricciones Fijas (No Negociables)

Las siguientes validaciones se ejecutan siempre, en orden estricto, para cualquier código:

1. Existencia: el código debe existir en el sistema.

2. Vigencia temporal: la fecha actual debe estar dentro del rango de inicio y expiración configurado.

3. Estado activo: el código debe estar en estado active.

Solo si las tres pasan, se evaluan las reglas configuradas para ese código.

## 3. Lineamientos Mínimos de Persistencia

El modelo de datos concreto es parte del trabajo a diseñar. Aquí se establecen únicamente las necesidades mínimas de almacenamiento.

- Un código promocional debe persistir su tipo, valor base, estado, fechas de vigencia y el conjunto de reglas que tiene activas con sus parámetros asociados.

- Debe existir un registro por cada vez que un código fue aplicado a una orden, incluyendo: el código, la orden, el comprador y el monto descontado.

- Dicho registro debe indicar si la orden fue efectivamente pagada. Las validaciones de límites (global_usage_limit, global_amount_limit, user_usage_limit) solo deben contar usos sobre órdenes pagadas. Una orden pendiente de pago no consume cupo.

- Para códigos de tipo tiered, la configuración de tramos (cantidad mínima de órdenes $ \rightarrow $ porcentaje de descuento) debe poder almacenarse asociada al código sin requerir cambios estructurales en el esquema por cada nueva configuración de tramos.

- Un código de acceso restricted_usage debe tener una relación explícita con los usuarios autorizados a usarlo.

## 4. Contratos de Integración

El motor no debe depender de ninguna implementación concreta de orden. En cambio, opera sobre contratos que cualquier entidad de la plataforma puede implementar.

## OrderableInterface

Cualquier entidad que quiera participar en el motor debe exponer:

- getSubtotal(): float — el subtotal de la orden sobre el que se calculará el descuento.

- getOrderContext(): OrderContext — el contexto necesario para evaluar las reglas.

## OrderContext

Objeto de valor inmutable que porta la información contextual requerida por las reglas:

- buyerProfile — el perfil del comprador que intenta usar el código.

- categoryId — la categoría del servicio de la orden.

- currentOrders — colección de ordenes actualmente en proceso (ej: carrito con múltiples ítems).

Permite excluirlas de conteos históricos para evitar falsos positivos en validaciones de límite.

## 5. Flujo de Validación y Cálculo

## Fase 1 — Verificación

[código + order]

↓

Existencia      ← Regla fija (siempre ejecuta)

↓

Vigencia temporal ← Regla fija

↓

Estado activo ← Regla fija

↓

[inspecciona reglas configuradas en el código]

↓

min_purchase_amount?      (si está configurada)

eligible_categories?    (si está configurada)

first_order_only?      (si está configurada)

user_usage_limit?      (si está configurada)

global_usage_limit?      (si está configurada)

global_amount_limit?      (si está configurada)

restricted_usage?      (si está configurada)

↓

→ válido

→ excepción de validación con código de error semántico

## Fase 2 — Cálculo

[código + order]

↓

Selección de estrategia por tipo de descuento

↓

fixed |

percent | → descuento inicial calculado

tiered |

↓

[inspecciona reglas post-cálculo configuradas]

↓

max_discount_amount?    (si está configurada)

↓

→ float (monto final de descuento)

## 6. Comportamiento del Descuento por Tipo

percent

descuento = subtotal × (value / 100)

fixed

descuento = min(value, subtotal)

tiered

El código almacena una serie de tramos, cada uno con una cantidad mínima de órdenes requeridas y el porcentaje de descuento correspondiente:

<table border="1"><tr><td>Mínimo de ordenes previas</td><td>Descuento aplicado</td></tr><tr><td>0</td><td>5%</td></tr><tr><td>3</td><td>10%</td></tr><tr><td>10</td><td>15%</td></tr></table>

El motor cuenta las órdenes no canceladas y no en borrador del comprador (excluyendo las órdenes actualmente en proceso) y aplica el tramo elegible más alto.

descuento = subtotal × (porcentaje_del_tramo / 100)

## 7. Manejo de Errores

Todas las reglas de validación deben comunicar su fallo mediante un código de error semántico:

<table border="1"><tr><td>Código de error</td><td>Causa</td></tr><tr><td>invalid_code</td><td>Código inexistente, inactivo o categoría no elegible.</td></tr><tr><td>expired_coupon</td><td>Fuera del rango de fechas de vigencia.</td></tr><tr><td>usage_limit_reached</td><td>Se superó el límite de uso global o por usuario.</td></tr><tr><td>maximum_discount_reached</td><td>Se alcanzó el límite de monto global acumulado.</td></tr><tr><td>min_amount_required</td><td>Subtotal inferior al mínimo requerido.</td></tr><tr><td>code_already_used</td><td>Código de primera compra en un usuario con historial.</td></tr><tr><td>restricted_usage</td><td>Código restringido no asignado al usuario.</td></tr></table>

## 8. Alcance de la Implementación Funcional

Este caso de estudio no entrega código fuente ni repositorio legacy: el diseño se construye desde cero. Pero a diferencia de un ejercicio puramente teórico, sí se exige una implementación funcional, acotada estrictamente al flujo de validación.

## 8.1 Qué se debe implementar

- El PromoCodeEngine, solo en su flujo de validación (Fase 1 — Verificación, sección 5): determinar si un código es elegible para una orden concreta, aplicando en orden las reglas fijas de sección 2 y las reglas configurables de sección 1.2 que estén activas en el código.

- El resultado de la validación debe poder expresarse como "válido" o como una excepción/resultado con el código de error semántico correspondiente de sección 7.

- Endpoint HTTP para validar promoCode, ademas, este codigo debe incluir el cálculo del monto de descuento (sección 6, Fase 2),

La persistencia real en base de datos y el se documentan y justifican a nivel de diseño en el ASD, pero no requieren código funcional.

## 8.2 Cómo se debe construir

- El resto de entidades y clases necesarias para ejercitar el flujo de validación (implementaciones de OrderableInterface, OrderContext, perfiles de comprador, historial de uso, etc.) no se prueban por separado: existen para soportar la demostración del PromoCodeEngine y deben construirse con TDD.

- Todos los escenarios de prueba se construyen usando factories, sin fixtures estáticos. Las factories deben permitir generar, por ejemplo, un comprador con historial de ordenes, un código con límites de uso ya alcanzados, un código restringido a ciertos usuarios, etc.

- Cada regla de validación de sección 1.2 y sección 2 debe tener pruebas unitarias independientes entre sí, demostrando tanto el caso en que la regla bloquea como el caso en que la regla permite.

En resumen: el PromoCodeEngine (validación) es lo único que debe funcionar de extremo a extremo, demostrado con un suite de pruebas TDD construido sobre factories. Todo lo demás (cálculo, persistencia, endpoint) es propuesta de diseño documentada, no código.

## 9. Patrones de Diseño — Qué se Espera en la Presentación

Esta es la sección central del entregable académico. Como parte del documento y de la defensa, cada equipo debe:

1. Identificar qué patrones de diseño proponen para la solución.

2. Justificar por qué cada patrón fue la elección adecuada para el problema que resuelve (conectado al requerimiento concreto, no al catálogo en general).

3. Señalar qué principios SOLID se ven reflejados y en qué parte del diseño.

4. Indicar qué patrones consideraron y descartaron, y por qué.

5. Indicar qué cambios habría sido necesario hacer en el motor si no se hubieran aplicado los patrones elegidos.

No se sugieren patrones específicos en este documento. Identificarlos y justificarlos correctamente es parte de lo evaluado.

## 10. Criterios de Aceptación del Diseño

□ Agregar una nueva regla de validación no requeriría modificar PromoCodeEngine.

Agregar un nuevo tipo de descuento no requeriría modificar el flujo principal de validación.

Las reglas activas de un código se determinan en tiempo de ejecución desde la base de datos, no en tiempo de compilación.

Cada regla de validación sería testeable de forma unitaria e independiente.

El motor no está acoplado a ninguna entidad de orden concreta.

□ El cálculo y la validación pueden ejecutarse de forma independiente.

Las reglas de post-cálculo se aplican después del cálculo y antes de retornar el resultado (a nivel de diseño).

□ El diseño contempla un código de error semántico distinto para cada escenario de fallo de sección 7.

□ El PromoCodeEngine valida correctamente la elegibilidad de un código de forma funcional (código ejecutable, no pseudocódigo).

□ El conjunto de pruebas del PromoCodeEngine fue construido con TDD y usa factories, sin fixtures estáticos.

Cada regla de validación (sección 1.2 y sección 2) tiene al menos una prueba que demuestra el caso bloqueado y una que demuestra el caso permitido.

## 11. Glosario

<table border="1"><tr><td>Término</td><td>Definición</td></tr><tr><td>Código promocional</td><td>Entidad que representa un cupón con su tipo, valor y reglas activas.</td></tr><tr><td>PromoCodeEngine</td><td>Orquestador central de validación y cálculo.</td></tr><tr><td>Orderable</td><td>Contrato que implementa cualquier entidad de la plataforma que represente una orden comprable.</td></tr><tr><td>OrderContext</td><td>Objeto de valor con información contextual de la orden actual.</td></tr><tr><td>Reglas configurables</td><td>Conjunto de restricciones activadas por configuración en base de datos, sin cambios en código.</td></tr><tr><td>Fase Base</td><td>Reglas de validación fijas que siempre se ejecutan.</td></tr><tr><td>Fase Dinámica</td><td>Reglas condicionadas por la configuración del código en base de datos.</td></tr></table>

<div align="center">

# Información del Entregable Académico (ASD)

</div>

## Modalidad

Trabajo grupal, en equipos de 5 a 6 integrantes (no necesariamente los mismos conformados para la Dinámica Grupal de semanas 9-10). Todos los integrantes deben participar tanto en la elaboración del documento como en la defensa oral.

## Entregable

Un documento de diseño (ASD) en respuesta a este TDR, que debe incluir como mínimo:

1. Descripción del sistema y sus requerimientos.

2. Arquitectura propuesta, con justificación.

3. Patrones de diseño seleccionados, dónde aplican y por qué.

4. Patrones descartados y por qué.

5. Buenas prácticas aplicadas (principios SOLID y dónde se reflejan).

6. Stack tecnológico propuesto, con justificación.

7. Trade-offs aceptados en la decisión final.

Se recomienda incluir al menos un diagrama (UML, componentes o secuencia) que represente la arquitectura propuesta.

Además del documento, se entrega un repositorio con la implementación funcional del PromoCodeEngine (flujo de validación, sección 8), incluyendo el conjunto de pruebas construido con TDD y factories. El enlace al repositorio debe incluirse en el documento.

## Fechas clave

<table border="1"><tr><td>Hito</td><td>Fecha</td></tr><tr><td>Entrega del documento ASD</td><td>Semana 11 — viernes, 11:59 p.m.</td></tr><tr><td>Defensa oral</td><td>Semana 12 — viernes 24 de julio, 4:00 p.m. a 6:30 p.m.</td></tr><tr><td>Sorteo del orden de exposición</td><td>Antes de la semana 12</td></tr></table>

## Formato de la defensa

- 15-20 minutos por grupo.

- Todos los miembros deben participar y poder responder preguntas sobre cualquier decisión de diseño.

- El catedrático puede preguntar a cualquier integrante sobre cualquier parte del documento.

<div align="center">

Rúbrica de evaluación

</div>

<table border="1"><tr><td>Criteria</td><td>Descripción</td><td>Ponderación</td></tr><tr><td>Análisis del sistema y requerimientos</td><td>Comprensión clara del problema, los requerimientos funcionales y las restricciones de este TDR</td><td>10%</td></tr><tr><td>Arquitectura propuesta y justificación</td><td>Arquitectura coherente, justificada técnicamente y alineada a los requerimientos</td><td>15%</td></tr><tr><td>Patrones seleccionados y aplicación</td><td>Aplicación correcta y bien argumentada de patrones de diseño relevantes al problema, no al catálogo en general</td><td>20%</td></tr><tr><td>Patrones descartados y justificación</td><td>Análisis crítico de alternativas no elegidas y razón de su descarte</td><td>5%</td></tr><tr><td>Principios SOLID y buenas prácticas</td><td>Identificación clara de qué principios se aplican y en qué parte exacta del diseño</td><td>10%</td></tr><tr><td>Implementación funcional del PromoCodeEngine</td><td>Validación de sección 8 funcionando correctamente, construida con TDD y pruebas sobre factories, sin fixtures estáticos</td><td>20%</td></tr><tr><td>Trade-offs y factibilidad técnica</td><td>Identificación honesta de limitaciones, riesgos y decisiones de compromiso aceptadas</td><td>10%</td></tr><tr><td>Documento y defensa oral</td><td>Claridad, estructura profesional del documento y dominio del equipo durante la defensa</td><td>10%</td></tr><tr><td>Total</td><td></td><td>100%</td></tr></table>

Este entregable corresponde al Examen Final (25% de la nota final del curso).