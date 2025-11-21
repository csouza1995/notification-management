# Notification Management Package

## 📋 Visão Geral

Package Laravel para gerenciamento completo de notificações com preferências de usuário por canal.

### Problema que resolve
- Gap no mercado Laravel: falta um package robusto que permita usuários escolherem por quais canais querem receber cada tipo de notificação
- Centralizar gerenciamento de múltiplos canais de notificação
- Permitir registro de canais customizados facilmente

## 🎯 Funcionalidades Principais

### 1. Sistema de Canais
- Suporte a múltiplos canais: Email, SMS, Push, Slack, Discord, etc.
- Registry para registrar canais customizados
- Configuração flexível de canais disponíveis

### 2. Preferências do Usuário
- Usuários podem escolher quais canais querem receber notificações
- Preferências por tipo de notificação
- Interface para gerenciar preferências

### 3. Notification Manager
- Sistema inteligente que verifica preferências antes de enviar
- Disparo automático em múltiplos canais
- Log de notificações enviadas

## 🏗️ Arquitetura

### Database Schema

#### notification_channels
- id
- name (email, sms, push, slack, etc)
- driver_class
- is_active
- config (json)
- timestamps

#### user_notification_preferences
- id
- user_id
- notification_type
- channel_id
- is_enabled
- timestamps

#### notification_logs (opcional)
- id
- user_id
- notification_type
- channel_id
- status (sent, failed, pending)
- payload (json)
- error_message (nullable)
- sent_at
- timestamps

### Componentes Principais

1. **NotificationChannel Model**: Representa os canais disponíveis
2. **UserNotificationPreference Model**: Preferências dos usuários
3. **ChannelRegistry**: Registra e gerencia drivers de canais
4. **NotificationManager**: Orquestra o envio das notificações
5. **HasNotificationPreferences Trait**: Adiciona funcionalidades ao User model

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

## 🚀 Roadmap

- [ ] Core functionality
- [ ] Built-in channels (email, database)
- [ ] API REST para gerenciar preferências
- [ ] Interface UI opcional (Blade components)
- [ ] Testes automatizados
- [ ] Documentação completa
- [ ] Suporte a notificações em lote
- [ ] Queue support
- [ ] Rate limiting por canal

## 📝 Notas de Desenvolvimento

- Manter código desacoplado e testável
- Seguir PSR-12 coding standards
- Documentar todos os métodos públicos
- Criar exemplos práticos na documentação
