<?php

namespace Csouza\NotificationManagement\Managers;

use Csouza\NotificationManagement\Contracts\NotificationChannelInterface;
use Csouza\NotificationManagement\Exceptions\ChannelNotFoundException;
use Csouza\NotificationManagement\Exceptions\InvalidChannelException;

class ChannelRegistry
{
    /**
     * Canais registrados
     *
     * @var array<string, string>
     */
    protected array $channels = [];

    /**
     * Instâncias dos canais
     *
     * @var array<string, NotificationChannelInterface>
     */
    protected array $instances = [];

    /**
     * Registra um novo canal
     *
     * @param  string  $name  Nome do canal (ex: 'telegram', 'whatsapp')
     * @param  string  $channelClass  FQCN da classe que implementa NotificationChannelInterface
     *
     * @throws InvalidChannelException
     */
    public function register(string $name, string $channelClass): void
    {
        if (! class_exists($channelClass)) {
            throw new InvalidChannelException("Channel class {$channelClass} does not exist.");
        }

        if (! in_array(NotificationChannelInterface::class, class_implements($channelClass) ?: [])) {
            throw new InvalidChannelException("Channel class {$channelClass} must implement NotificationChannelInterface.");
        }

        $this->channels[$name] = $channelClass;
    }

    /**
     * Remove um canal registrado
     */
    public function unregister(string $name): void
    {
        unset($this->channels[$name], $this->instances[$name]);
    }

    /**
     * Verifica se um canal está registrado
     */
    public function has(string $name): bool
    {
        return isset($this->channels[$name]);
    }

    /**
     * Obtém uma instância do canal
     *
     * @throws ChannelNotFoundException
     */
    public function get(string $name): NotificationChannelInterface
    {
        if (! $this->has($name)) {
            throw new ChannelNotFoundException("Channel '{$name}' is not registered.");
        }

        if (! isset($this->instances[$name])) {
            $this->instances[$name] = app($this->channels[$name]);
        }

        return $this->instances[$name];
    }

    /**
     * Retorna todos os canais registrados
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->channels;
    }

    /**
     * Retorna os nomes de todos os canais registrados
     *
     * @return array<string>
     */
    public function names(): array
    {
        return array_keys($this->channels);
    }

    /**
     * Limpa todos os canais registrados
     */
    public function clear(): void
    {
        $this->channels = [];
        $this->instances = [];
    }
}
