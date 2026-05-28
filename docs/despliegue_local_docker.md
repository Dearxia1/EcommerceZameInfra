# Despliegue local con Docker

Esta configuracion levanta una copia local de ZAME SCENT con Node.js y MySQL.
La idea es probar cambios de aplicacion, como una pasarela de pagos, sin tocar AWS.

## Rama de trabajo

```bash
git switch feature/local-docker-payment-gateway
```

Para volver a la rama estable:

```bash
git switch main
```

## Levantar el ambiente

Desde la raiz del proyecto:

```bash
docker compose up --build
```

La aplicacion queda disponible en:

```text
http://localhost:3000
```

El checkout usa ePayco en modo desarrollo local (`EPAYCO_MOCK=true`), asi que
puedes probar compras sin credenciales reales.

El MySQL local queda expuesto en el host por el puerto `3307`, pero la app se conecta
internamente usando `mysql:3306`.

## Validar salud

```bash
curl http://localhost:3000/health
```

La respuesta esperada debe incluir:

```json
{
  "status": "HEALTHY",
  "database": "CONNECTED"
}
```

## Apagar el ambiente

```bash
docker compose down
```

Para borrar tambien los datos de MySQL local:

```bash
docker compose down -v
```

## Flujo recomendado para pasarela de pagos

1. Desarrollar en `feature/local-docker-payment-gateway`.
2. Probar localmente con Docker.
3. Crear una rama remota de pruebas solo cuando funcione.
4. Pedir aprobacion del dueno del proyecto.
5. Hacer merge a la rama estable.
