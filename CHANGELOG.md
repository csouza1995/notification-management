# Changelog

All notable changes to `notification-management` will be documented in this file.

## [Unreleased]

### Added
- Initial release
- User notification preferences system
- Multi-channel notification support (mail, database, broadcast + custom channels)
- Channel Registry for custom notification channels
- Notification Manager with preference checking
- API REST with 7 endpoints for preference management
- `HasNotificationPreferences` trait for User model
- `UsesNotificationPreferences` trait for Notification classes
- Automatic event-to-notification mapping
- Built-in `UserLoggedNotification` for login detection
- Force channels and allowed channels for notifications
- Default preferences configuration with wildcard support
- Automatic preference initialization for new users
- Notification logging system
- Queue support via `ShouldQueue`
- Comprehensive test suite (37 tests, 75 assertions)
- PHPStan level 5 static analysis
- Complete documentation

### Features

#### Core Features
- Multiple notification channels support
- User preferences per notification type and channel
- Channel registry for custom channels
- Notification manager with intelligent routing
- Notification history tracking

#### Advanced Features
- Event-to-notification automatic mapping
- Conditional notifications
- Multiple notifiables support (collections)
- Force channels (override user preferences)
- Allowed channels (limit available channels)
- Wildcard defaults for all channels

#### Developer Experience
- Clean API with facades
- Trait-based architecture
- Configuration-driven
- Queue-ready
- Laravel Pint for code formatting
- Pest for testing
- PHPStan for static analysis

## [1.0.0] - TBD

Initial public release.
