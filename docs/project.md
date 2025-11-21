# Notification Management Package

## 📋 Visão Geral

Package Laravel para gerenciamento completo de notificações com preferências de usuário por canal.

### Problema que resolve
- ✅ Gap no mercado Laravel: falta um package robusto que permita usuários escolherem por quais canais querem receber cada tipo de notificação
- ✅ Centralizar gerenciamento de múltiplos canais de notificação
- ✅ Permitir registro de canais customizados facilmente
- ✅ Automatizar o envio de notificações baseado em eventos Laravel
- ✅ Fornecer API REST completa para gerenciamento de preferências

## 🎯 Funcionalidades Implementadas

### 1. Sistema de Canais ✅
- ✅ Suporte a canais nativos Laravel: mail, database, broadcast
- ✅ Registry (ChannelRegistry) para registrar canais customizados via código ou config
- ✅ Interface NotificationChannelInterface para criar canais customizados
- ✅ Configuração flexível de canais disponíveis

### 2. Preferências do Usuário ✅
- ✅ Usuários escolhem quais canais querem receber notificações
- ✅ Preferências granulares por tipo de notificação + canal
- ✅ API REST completa (7 endpoints) para gerenciar preferências
- ✅ Defaults configuráveis com suporte a wildcard (*)
- ✅ Auto-inicialização de preferências para novos usuários

### 3. Notification Manager ✅
- ✅ Sistema inteligente que verifica preferências antes de enviar
- ✅ Disparo automático em múltiplos canais
- ✅ Log completo de notificações enviadas (notification_logs)
- ✅ Método sendByType() para facilitar envio
- ✅ Suporte a filas (ShouldQueue)

### 4. Event-to-Notification Mapping ✅ (Nova Feature!)
- ✅ Mapeamento automático de eventos Laravel → notificações
- ✅ Extração de notifiable via string, dot notation ou closure
- ✅ Suporte a múltiplos notifiables (collections)
- ✅ Notificações condicionais
- ✅ Dados customizados por evento
- ✅ Habilitar/desabilitar mapeamentos individuais

### 5. Traits e Helpers ✅
- ✅ HasNotificationPreferences trait para User model
- ✅ UsesNotificationPreferences trait para Notification classes
- ✅ Force channels (sobrescrever preferências do usuário)
- ✅ Allowed channels (limitar canais disponíveis)

### 6. Built-in Notifications ✅
- ✅ UserLoggedNotification (detecção de login com IP, user agent, localização)

## 🏗️ Arquitetura Implementada

### Database Schema

#### user_notification_preferences ✅
- id
- user_id
- notification_type (string: 'order.shipped', 'user.logged', etc)
- channel_name (string: 'mail', 'database', 'sms', etc)
- is_enabled (boolean)
- timestamps
- **unique(user_id, notification_type, channel_name)**

#### notification_logs ✅
- id
- user_id
- channel_name
- notification_type
- status (sent, failed, pending)
- payload (json)
- error_message (nullable)
- sent_at
- timestamps

**Mudança de Arquitetura:** ❌ notification_channels table foi removida. Canais são agora registrados via config ou código (ChannelRegistry), tornando o sistema mais flexível e sem necessidade de gerenciamento de banco de dados para canais.

### Componentes Principais Implementados

1. **UserNotificationPreference Model** ✅: Preferências dos usuários
2. **NotificationLog Model** ✅: Histórico de notificações enviadas
3. **ChannelRegistry** ✅: Registra e gerencia drivers de canais customizados
4. **NotificationManager** ✅: Orquestra o envio das notificações com verificação de preferências
5. **EventNotificationMapper** ✅: Mapeia eventos para notificações automaticamente
6. **HasNotificationPreferences Trait** ✅: Adiciona funcionalidades ao User model
7. **UsesNotificationPreferences Trait** ✅: Simplifica criação de notificações
8. **NotificationPreferenceController** ✅: API REST (7 endpoints)
9. **SendUserLoggedNotification Listener** ✅: Listener exemplo para Login event

## 📦 Requisitos

- Laravel 10+
- PHP 8.1+

## 🔧 Setup de Desenvolvimento

### Instalação das Dependências

```bash
composer install
```

### Ferramentas Instaladas

- **Laravel Pint**: Code style fixer (Laravel preset)
- **Pest**: Framework de testes moderno e elegante
- **Larastan**: Análise estática de código (PHPStan para Laravel)
- **Orchestra Testbench**: Ambiente de testes para packages Laravel

### Scripts Disponíveis

```bash
# Executar testes
composer test

# Executar testes com coverage
composer test-coverage

# Formatar código (Laravel Code Style)
composer format

# Análise estática de código
composer analyse
```

### Estrutura do Projeto

```
notification-management/
├── src/                          # Código fonte do package
│   ├── Models/                   # Eloquent Models
│   ├── Managers/                 # NotificationManager e ChannelRegistry
│   ├── Traits/                   # HasNotificationPreferences
│   ├── Contracts/                # Interfaces
│   ├── Channels/                 # Built-in channels
│   └── NotificationManagementServiceProvider.php
├── database/
│   └── migrations/               # Migrations do package
├── config/
│   └── notification-management.php
├── tests/                        # Testes com Pest
│   ├── Feature/
│   └── Unit/
├── docs/                         # Documentação
└── composer.json
```

## 🔧 Uso Proposto

### Instalação
```bash
composer require csouza/notification-management
php artisan vendor:publish --tag=notification-management
php artisan migrate
```

### Configuração User Model
```php
use Csouza\NotificationManagement\Traits\HasNotificationPreferences;

class User extends Authenticatable
{
    use HasNotificationPreferences;
}
```

### Registro de Canal Customizado
```php
// AppServiceProvider
NotificationChannelRegistry::register('telegram', TelegramChannel::class);
```

### Envio de Notificação
```php
NotificationManager::send($user, 'order.shipped', [
    'order_id' => 123,
    'tracking_code' => 'ABC123'
]);
```

### Gerenciar Preferências
```php
// Ativar canal para um tipo de notificação
$user->enableNotificationChannel('order.shipped', 'email');
$user->disableNotificationChannel('order.shipped', 'sms');

// Verificar se usuário quer receber por um canal
$user->wantsNotificationVia('order.shipped', 'email'); // true/false

// Obter todos os canais ativos do usuário para uma notificação
$user->getActiveChannelsFor('order.shipped'); // ['email', 'push']
```

## ✅ Status do Projeto

### Completado (100% Backend)
- ✅ Core functionality (NotificationManager, ChannelRegistry)
- ✅ Built-in channels (mail, database, broadcast)
- ✅ API REST completa (7 endpoints)
- ✅ Testes automatizados (37 tests, 75 assertions - Pest)
- ✅ Documentação completa (README, 7 docs + CHANGELOG + SECURITY)
- ✅ Queue support (ShouldQueue)
- ✅ Built-in notification (UserLoggedNotification)
- ✅ Event-to-Notification automatic mapping
- ✅ Traits (HasNotificationPreferences, UsesNotificationPreferences)
- ✅ Channel limiting (forceChannels, allowedChannels)
- ✅ PHPStan level 5 (0 errors)
- ✅ GitHub Actions (tests, phpstan, code style)

### Não Implementado (Decisão de Escopo)
- ❌ Interface UI (Blade/Livewire) - **Backend only**
- ⏳ Suporte a notificações em lote - **Futuro**
- ⏳ Rate limiting por canal - **Futuro**

## 📝 Padrões de Desenvolvimento Seguidos

- ✅ Código desacoplado e testável
- ✅ PSR-12 coding standards (Laravel Pint)
- ✅ Todos os métodos públicos documentados
- ✅ Exemplos práticos na documentação
- ✅ Type hints e return types
- ✅ Static analysis (PHPStan level 5)
