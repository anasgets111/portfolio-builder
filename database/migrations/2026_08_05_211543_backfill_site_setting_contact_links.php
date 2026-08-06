<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $siteSetting = DB::table('site_settings')
            ->select(['id', 'phone', 'whatsapp_number', 'social_links'])
            ->first();

        if ($siteSetting === null) {
            return;
        }

        $socialLinks = $this->decodeSocialLinks($siteSetting->social_links);

        if (filled($siteSetting->phone) && ! $this->hasPlatform($socialLinks, 'phone')) {
            $socialLinks[] = [
                'platform' => 'Phone',
                'label' => 'Phone',
                'url' => 'tel:'.$siteSetting->phone,
            ];
        }

        if (filled($siteSetting->whatsapp_number) && ! $this->hasPlatform($socialLinks, 'whatsapp')) {
            $phoneNumber = Str::of($siteSetting->whatsapp_number)
                ->replaceMatches('/\D+/', '')
                ->toString();

            $socialLinks[] = [
                'platform' => 'WhatsApp',
                'label' => 'WhatsApp',
                'url' => 'https://api.whatsapp.com/send?phone='.$phoneNumber,
            ];
        }

        DB::table('site_settings')
            ->where('id', $siteSetting->id)
            ->update(['social_links' => json_encode($socialLinks, JSON_THROW_ON_ERROR)]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $siteSetting = DB::table('site_settings')
            ->select(['id', 'social_links'])
            ->first();

        if ($siteSetting === null) {
            return;
        }

        $socialLinks = $this->decodeSocialLinks($siteSetting->social_links);
        $phoneUrl = $this->findPlatformUrl($socialLinks, 'phone');
        $whatsAppUrl = $this->findPlatformUrl($socialLinks, 'whatsapp');
        $whatsAppNumber = Str::match('/[?&]phone=([^&]+)/', $whatsAppUrl);

        DB::table('site_settings')
            ->where('id', $siteSetting->id)
            ->update([
                'phone' => Str::startsWith($phoneUrl, 'tel:') ? Str::after($phoneUrl, 'tel:') : null,
                'whatsapp_number' => $whatsAppNumber !== '' ? urldecode($whatsAppNumber) : null,
            ]);
    }

    /** @return list<array<array-key, mixed>> */
    private function decodeSocialLinks(mixed $socialLinks): array
    {
        if (! is_string($socialLinks)) {
            return [];
        }

        $decodedSocialLinks = json_decode($socialLinks, true);

        if (! is_array($decodedSocialLinks)) {
            return [];
        }

        $links = [];

        foreach ($decodedSocialLinks as $decodedSocialLink) {
            if (is_array($decodedSocialLink)) {
                $links[] = $decodedSocialLink;
            }
        }

        return $links;
    }

    /** @param list<array<array-key, mixed>> $socialLinks */
    private function hasPlatform(array $socialLinks, string $platform): bool
    {
        return $this->findPlatformUrl($socialLinks, $platform) !== '';
    }

    /** @param list<array<array-key, mixed>> $socialLinks */
    private function findPlatformUrl(array $socialLinks, string $platform): string
    {
        foreach ($socialLinks as $socialLink) {
            $platformName = $socialLink['platform'] ?? null;
            $url = $socialLink['url'] ?? null;

            if (is_string($platformName) && Str::lower($platformName) === $platform && is_string($url)) {
                return $url;
            }
        }

        return '';
    }
};
