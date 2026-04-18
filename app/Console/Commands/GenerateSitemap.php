<?php

namespace App\Console\Commands;

use App\Models\FuelSite;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate public/sitemap.xml';

    public function handle(): void
    {
        $sitemap = Sitemap::create();

        $sitemap->add(Url::create('/')->setPriority(1.0)->setChangeFrequency('daily'));
        $sitemap->add(Url::create('/fuel')->setPriority(0.8)->setChangeFrequency('weekly'));
        $sitemap->add(Url::create('/dashboard')->setPriority(0.8)->setChangeFrequency('weekly'));
        $sitemap->add(Url::create('/about')->setPriority(0.8)->setChangeFrequency('weekly'));

        FuelSite::select('id')->chunk(200, function ($sites) use ($sitemap) {
            foreach ($sites as $site) {
                $sitemap->add(
                    Url::create("/fuel/{$site->id}")
                        ->setPriority(0.6)
                        ->setChangeFrequency('weekly')
                );
            }
        });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap written to ' . public_path('sitemap.xml'));
    }
}
