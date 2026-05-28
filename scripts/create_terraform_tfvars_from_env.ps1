param(
  [string]$EnvPath = ".env",
  [string]$OutputPath = "terraform/terraform.tfvars",
  [string]$DbPass = "",
  [string]$AlertEmail = "infraalertas@zame.com.co"
)

$ErrorActionPreference = "Stop"

function Read-DotEnv {
  param([string]$Path)

  $values = @{}
  if (-not (Test-Path -LiteralPath $Path)) {
    return $values
  }

  Get-Content -LiteralPath $Path | ForEach-Object {
    $line = $_.Trim()
    if (-not $line -or $line.StartsWith("#")) {
      return
    }

    $parts = $line -split "=", 2
    if ($parts.Length -eq 2) {
      $values[$parts[0].Trim()] = $parts[1].Trim()
    }
  }

  return $values
}

function Get-Value {
  param(
    [hashtable]$DotEnv,
    [string]$Name,
    [string]$Default = ""
  )

  if ($DotEnv.ContainsKey($Name) -and $DotEnv[$Name]) {
    return $DotEnv[$Name]
  }

  $envValue = [Environment]::GetEnvironmentVariable($Name)
  if ($envValue) {
    return $envValue
  }

  return $Default
}

function Escape-HclString {
  param([string]$Value)
  return ($Value -replace "\\", "\\") -replace '"', '\"'
}

$dotEnv = Read-DotEnv -Path $EnvPath

$publicKey = Get-Value -DotEnv $dotEnv -Name "EPAYCO_PUBLIC_KEY"
$privateKey = Get-Value -DotEnv $dotEnv -Name "EPAYCO_PRIVATE_KEY"

if (-not $publicKey -or $publicKey -match "PEGA_AQUI|CHANGE_ME") {
  throw "EPAYCO_PUBLIC_KEY is missing. Put it in $EnvPath or export EPAYCO_PUBLIC_KEY."
}

if (-not $privateKey -or $privateKey -match "PEGA_AQUI|CHANGE_ME") {
  throw "EPAYCO_PRIVATE_KEY is missing. Put it in $EnvPath or export EPAYCO_PRIVATE_KEY."
}

$mock = Get-Value -DotEnv $dotEnv -Name "EPAYCO_MOCK" -Default "false"
$testMode = Get-Value -DotEnv $dotEnv -Name "EPAYCO_TEST_MODE" -Default "true"
$priceDivisor = Get-Value -DotEnv $dotEnv -Name "EPAYCO_TEST_PRICE_DIVISOR" -Default "10"
$maxAmount = Get-Value -DotEnv $dotEnv -Name "EPAYCO_TEST_MAX_AMOUNT" -Default "200000"
$responseUrl = Get-Value -DotEnv $dotEnv -Name "EPAYCO_RESPONSE_URL" -Default ""
$confirmationUrl = Get-Value -DotEnv $dotEnv -Name "EPAYCO_CONFIRMATION_URL" -Default ""

if (-not $DbPass) {
  $DbPass = Get-Value -DotEnv $dotEnv -Name "DB_PASS" -Default "SecurePass123!"
}

$outputDir = Split-Path -Parent $OutputPath
if ($outputDir -and -not (Test-Path -LiteralPath $outputDir)) {
  New-Item -ItemType Directory -Path $outputDir | Out-Null
}

$content = @"
# Generated locally from $EnvPath.
# Do not commit this file.

aws_region = "us-east-1"

db_name = "zame_db"
db_user = "admin"
db_pass = "$(Escape-HclString $DbPass)"

alert_email = "$(Escape-HclString $AlertEmail)"

epayco_mock               = $($mock.ToLower())
epayco_test_mode          = $($testMode.ToLower())
epayco_public_key         = "$(Escape-HclString $publicKey)"
epayco_private_key        = "$(Escape-HclString $privateKey)"
epayco_test_price_divisor = $priceDivisor
epayco_test_max_amount    = $maxAmount

epayco_response_url     = "$(Escape-HclString $responseUrl)"
epayco_confirmation_url = "$(Escape-HclString $confirmationUrl)"
"@

Set-Content -LiteralPath $OutputPath -Value $content

Write-Host "Created $OutputPath"
Write-Host "This file is ignored by Git and must stay local."
