# Pasarela de pagos ePayco

Esta rama integra ePayco en el endpoint `POST /api/checkout`.

## Modo ePayco test en AWS con Terraform

Para despliegue en AWS, las claves no se guardan en codigo. Copia:

```bash
cp terraform/terraform.tfvars.example terraform/terraform.tfvars
```

Luego edita `terraform/terraform.tfvars` y coloca:

```hcl
epayco_mock        = false
epayco_test_mode   = true
epayco_public_key  = "TU_PUBLIC_KEY"
epayco_private_key = "TU_PRIVATE_KEY"
```

`terraform.tfvars` esta ignorado por Git. Terraform inyecta esas variables en
el `.env` de la EC2 durante el `user_data.sh`.

Tambien puedes usar variables de entorno sin crear archivo:

```bash
export TF_VAR_epayco_public_key="TU_PUBLIC_KEY"
export TF_VAR_epayco_private_key="TU_PRIVATE_KEY"
```

En AWS, si no defines `epayco_response_url` ni `epayco_confirmation_url`,
Terraform usa automaticamente el DNS publico del ALB.

Nota de seguridad: en este sandbox academico se inyectan secretos por
Terraform/user-data por simplicidad. En produccion real, las claves deberian
vivir en AWS Secrets Manager o SSM Parameter Store y la EC2 deberia leerlas con
un IAM Role.

## Precios en modo pruebas

Como ePayco puede bloquear transacciones altas si la cuenta no esta validada,
en modo `EPAYCO_TEST_MODE=true` la app usa precios de prueba:

```env
EPAYCO_TEST_PRICE_DIVISOR=10
EPAYCO_TEST_MAX_AMOUNT=200000
```

Con esa configuracion, un producto de `200000 COP` se muestra y cobra como
`20000 COP`. El backend tambien bloquea checkouts cuyo total de prueba supere
`200000 COP`.

## Flujo implementado

1. El frontend envia datos de envio, documento, contacto y tarjeta.
2. El backend valida el carrito y crea una orden `PENDING_PAYMENT`.
3. En modo mock aprueba localmente.
4. En modo ePayco test el backend:
   - crea token de tarjeta con `epayco.token.create`
   - crea cliente con `epayco.customers.create`
   - cobra con `epayco.charge.create`
5. Si el pago queda aprobado, la orden pasa a `COMPLETED` y el carrito se limpia.
6. Se guarda el resultado en `PaymentTransactions`.

## Seguridad

La app no guarda numero de tarjeta ni CVC. En modo real esos datos solo se usan
para tokenizar/cobrar con ePayco durante la solicitud.

Para produccion real conviene reemplazar el ingreso directo de tarjeta por el
Checkout o tokenizacion cliente-side de ePayco, para reducir alcance PCI.
