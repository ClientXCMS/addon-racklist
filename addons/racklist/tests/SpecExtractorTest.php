<?php

namespace App\Addons\RackList\Tests;

require_once __DIR__.'/RackListTestCase.php';

use App\Addons\RackList\Services\CatalogMapping;
use App\Addons\RackList\Specs\ProxmoxSpecExtractor;
use App\Addons\RackList\Specs\PterodactylSpecExtractor;
use App\Addons\RackList\Specs\SpecExtractorInterface;
use App\Addons\RackList\Specs\SpecResolver;
use App\Models\Store\Product;
use Illuminate\Database\Eloquent\Model;

class SpecExtractorTest extends RackListTestCase
{
    private function mapConfig(object $extractor, array $attributes): array
    {
        $config = new class extends Model
        {
            protected $guarded = [];
        };
        $config->forceFill($attributes);
        $method = new \ReflectionMethod($extractor, 'mapConfig');

        return array_filter($method->invoke($extractor, $config));
    }

    public function test_proxmox_config_is_converted_to_racklist_specs(): void
    {
        $specs = $this->mapConfig(new ProxmoxSpecExtractor, ['memory' => 4, 'disk' => 80.0, 'cores' => 2, 'sockets' => 2]);

        $this->assertSame(['ram' => '4 GB', 'storage' => '80 GB', 'cpu' => '4 cores'], $specs);
    }

    public function test_pterodactyl_cpu_percent_becomes_cores_and_unlimited_is_dropped(): void
    {
        $extractor = new PterodactylSpecExtractor;

        $this->assertSame(['ram' => '6 GB', 'storage' => '20 GB', 'cpu' => '1.5 cores'], $this->mapConfig($extractor, ['memory' => 6, 'disk' => 20, 'cpu' => 150]));
        $this->assertArrayNotHasKey('cpu', $this->mapConfig($extractor, ['memory' => 6, 'disk' => 20, 'cpu' => 0]));
    }

    public function test_manual_specs_override_automatic_ones(): void
    {
        $product = $this->createMappedProduct();
        $product->update(['type' => 'fake']);
        $resolver = new SpecResolver($this->app->make(CatalogMapping::class));
        $resolver->addExtractor(new class implements SpecExtractorInterface
        {
            public function productType(): string
            {
                return 'fake';
            }

            public function extract(Product $product): array
            {
                return ['ram' => '4 GB', 'cpu' => '2 cores'];
            }
        });
        $this->app->make(CatalogMapping::class)->setProductMapping($product, null, false, ['ram' => '8 GB']);

        $this->assertSame(['ram' => '8 GB', 'cpu' => '2 cores'], $resolver->resolve($product->fresh()));
    }
}
