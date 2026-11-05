<?php

declare(strict_types=1);

namespace App\Addons\RackList\Specs;

use Illuminate\Database\Eloquent\Model;

final class ProxmoxSpecExtractor extends AbstractSpecExtractor
{
    public function productType(): string
    {
        return 'proxmox';
    }

    protected function configModel(): string
    {
        return 'App\\Modules\\Proxmox\\Models\\ProxmoxConfigModel';
    }

    protected function mapConfig(Model $config): array
    {
        // Proxmox stores memory and disk in GB; vCPU count is cores x sockets.
        $cores = (int) $config->getAttribute('cores') * max(1, (int) $config->getAttribute('sockets'));

        return [
            'ram' => $this->formatQuantity($config->getAttribute('memory'), 'GB'),
            'storage' => $this->formatQuantity($config->getAttribute('disk'), 'GB'),
            'cpu' => $this->formatQuantity($cores, 'cores'),
        ];
    }
}
