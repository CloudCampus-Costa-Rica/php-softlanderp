# SoftlandERP Environment Variables Configuration

This package now supports reading configuration values from environment variables, making it compatible with Laravel and other frameworks that use `.env` files.

## Configuration Variables

The following constants can now be configured via environment variables:

### Cliente Configuration
- `CLIENTES_CATEGORIA_CLIENTE` - Default: "LOC"
- `CLIENTES_NIVEL_PRECIO` - Default: "ND-LOCAL"
- `CLIENTES_CODIGO_CONSECUTIVO` - Default: "CLI-L"
- `CLIENTES_PAIS` - Default: "CRI"

### General Tipo Configuration
- `GENERAL_TIPO_STR_FISICO` - Default: "FISICAS"
- `GENERAL_TIPO_STR_JURIDICO` - Default: "JURIDICO"

## Usage

### 1. Create a .env file

Create a `.env` file in your project root with the desired values:

```env
# Cliente Configuration
CLIENTES_CATEGORIA_CLIENTE=LOC
CLIENTES_NIVEL_PRECIO=ND-LOCAL
CLIENTES_CODIGO_CONSECUTIVO=CLI-L
CLIENTES_PAIS=CRI

# General Tipo Configuration
GENERAL_TIPO_STR_FISICO=FISICAS
GENERAL_TIPO_STR_JURIDICO=JURIDICO
```

### 2. Load environment variables

Before using the constants, call the `loadEnv()` method:

```php
use SoftlandERP\Constantes;

// Load environment variables from .env file
Constantes::loadEnv();

// Or specify a custom path
Constantes::loadEnv('/path/to/your/.env');
```

### 3. Use the getter methods

Instead of using the constants directly, use the getter methods:

```php
// Old way (still works but uses hardcoded values)
$categoria = Constantes::CLIENTES_CATEGORIA_CLIENTE;

// New way (reads from environment variables)
$categoria = Constantes::getClientesCategoriaCliente();
$nivelPrecio = Constantes::getClientesNivelPrecio();
$codigoConsecutivo = Constantes::getClientesCodigoConsecutivo();
$pais = Constantes::getClientesPais();
$tipoFisico = Constantes::getGeneralTipoStrFisico();
$tipoJuridico = Constantes::getGeneralTipoStrJuridico();
```

## Laravel Integration

In a Laravel project, you can add these variables to your `.env` file and they will be automatically available. The package will automatically detect and use Laravel's environment variables.

## Fallback Values

If an environment variable is not set, the getter methods will return the original hardcoded values as fallbacks, ensuring backward compatibility.

## Auto-detection

The `loadEnv()` method automatically searches for `.env` files in common locations:
1. Current package directory
2. Parent directory (useful when package is in vendor/)
3. Current working directory 