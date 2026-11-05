<?php

declare(strict_types=1);

namespace App\Addons\RackList\Specs;

use Illuminate\Database\Eloquent\Model;

final class PterodactylSpecExtractor extends AbstractSpecExtractor
{
    public function productType(): string
    {
        return 'pterodactyl';
    }

    protected function configModel(): string
    {
        return 'App\\Modules\\Pterodactyl\\Models\\PterodactylConfig';
    }

    protected function mapConfig(Model $config): array
    {
        // Memory and disk are stored in GB; cpu is a Pterodactyl limit in percent (100 = 1 core, 0 = unlimited).
        $cpuPercent = (float) $config->getAttribute('cpu');

        return [
            'ram' => $this->formatQuantity($config->getAttribute('memory'), 'GB'),
            'storage' => $this->formatQuantity($config->getAttribute('disk'), 'GB'),
            'cpu' => $this->formatQuantity($cpuPercent / 100, 'cores'),
        ];
    }
}
