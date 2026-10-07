<?php

namespace Database\Seeders\Demo;

use Random\Randomizer;

/**
 * Builds coherent demo visitor profiles: country → language, timezone and ISP (ASN); device → OS → browser.
 *
 * IP addresses come from the documentation ranges (RFC 5737 / RFC 3849), so they never identify real hosts.
 */
final class DemoAudience
{
    /**
     * @var array<string, array{languages: array<string, int>, timezones: array<string, int>, networks: list<array{0: int, 1: string}>}>
     */
    private const COUNTRIES = [
        'US' => ['languages' => ['en-US' => 92, 'es-US' => 6, 'zh-CN' => 2], 'timezones' => ['America/New_York' => 45, 'America/Chicago' => 25, 'America/Los_Angeles' => 22, 'America/Denver' => 8], 'networks' => [[7922, 'Comcast Cable Communications, LLC'], [7018, 'AT&T Services, Inc.'], [701, 'Verizon Business'], [20115, 'Charter Communications Inc'], [21928, 'T-Mobile USA, Inc.']]],
        'CA' => ['languages' => ['en-CA' => 75, 'fr-CA' => 25], 'timezones' => ['America/Toronto' => 60, 'America/Vancouver' => 25, 'America/Edmonton' => 15], 'networks' => [[812, 'Rogers Communications Canada Inc.'], [577, 'Bell Canada'], [852, 'TELUS Communications Inc.']]],
        'MX' => ['languages' => ['es-MX' => 100], 'timezones' => ['America/Mexico_City' => 100], 'networks' => [[8151, 'UNINET'], [28403, 'RadioMovil Dipsa, S.A. de C.V.']]],
        'BR' => ['languages' => ['pt-BR' => 100], 'timezones' => ['America/Sao_Paulo' => 100], 'networks' => [[28573, 'Claro NXT Telecomunicacoes Ltda'], [18881, 'TELEFONICA BRASIL S.A']]],
        'AR' => ['languages' => ['es-AR' => 100], 'timezones' => ['America/Argentina/Buenos_Aires' => 100], 'networks' => [[7303, 'Telecom Argentina S.A.']]],
        'GB' => ['languages' => ['en-GB' => 94, 'en-US' => 6], 'timezones' => ['Europe/London' => 100], 'networks' => [[2856, 'British Telecommunications PLC'], [5089, 'Virgin Media Limited'], [5607, 'Sky UK Limited']]],
        'IE' => ['languages' => ['en-IE' => 70, 'en-GB' => 30], 'timezones' => ['Europe/Dublin' => 100], 'networks' => [[5466, 'Eircom Limited'], [15502, 'Vodafone Ireland Limited']]],
        'DE' => ['languages' => ['de-DE' => 86, 'en-US' => 10, 'en-GB' => 4], 'timezones' => ['Europe/Berlin' => 100], 'networks' => [[3320, 'Deutsche Telekom AG'], [3209, 'Vodafone GmbH'], [6805, 'Telefonica Germany GmbH & Co.OHG']]],
        'AT' => ['languages' => ['de-AT' => 90, 'en-US' => 10], 'timezones' => ['Europe/Vienna' => 100], 'networks' => [[8447, 'A1 Telekom Austria AG'], [8412, 'T-Mobile Austria GmbH']]],
        'CH' => ['languages' => ['de-CH' => 60, 'fr-CH' => 25, 'it-CH' => 5, 'en-US' => 10], 'timezones' => ['Europe/Zurich' => 100], 'networks' => [[3303, 'Swisscom (Schweiz) AG'], [6730, 'Sunrise GmbH']]],
        'FR' => ['languages' => ['fr-FR' => 92, 'en-US' => 8], 'timezones' => ['Europe/Paris' => 100], 'networks' => [[3215, 'Orange S.A.'], [12322, 'Free SAS'], [15557, 'Societe Francaise Du Radiotelephone - SFR SA'], [5410, 'Bouygues Telecom SA']]],
        'BE' => ['languages' => ['nl-BE' => 55, 'fr-BE' => 40, 'en-US' => 5], 'timezones' => ['Europe/Brussels' => 100], 'networks' => [[5432, 'Proximus NV'], [6848, 'Telenet BV']]],
        'NL' => ['languages' => ['nl-NL' => 80, 'en-US' => 20], 'timezones' => ['Europe/Amsterdam' => 100], 'networks' => [[1136, 'KPN B.V.'], [33915, 'Vodafone Libertel B.V.']]],
        'LU' => ['languages' => ['fr-LU' => 50, 'de-LU' => 30, 'en-US' => 20], 'timezones' => ['Europe/Luxembourg' => 100], 'networks' => [[6661, 'POST Luxembourg']]],
        'IT' => ['languages' => ['it-IT' => 93, 'en-US' => 7], 'timezones' => ['Europe/Rome' => 100], 'networks' => [[3269, 'Telecom Italia S.p.A.'], [30722, 'Vodafone Italia S.p.A.'], [12874, 'Fastweb SpA'], [1267, 'WIND TRE S.P.A.'], [29447, 'Iliad Italia S.p.A.']]],
        'ES' => ['languages' => ['es-ES' => 90, 'ca-ES' => 5, 'en-US' => 5], 'timezones' => ['Europe/Madrid' => 100], 'networks' => [[3352, 'Telefonica De Espana S.A.U.'], [12479, 'Orange Espagne SA'], [12430, 'Vodafone Spain']]],
        'PT' => ['languages' => ['pt-PT' => 90, 'en-US' => 10], 'timezones' => ['Europe/Lisbon' => 100], 'networks' => [[3243, 'MEO - SERVICOS DE COMUNICACOES E MULTIMEDIA S.A.'], [12353, 'Vodafone Portugal']]],
        'DK' => ['languages' => ['da-DK' => 80, 'en-US' => 20], 'timezones' => ['Europe/Copenhagen' => 100], 'networks' => [[3292, 'TDC Holding A/S']]],
        'SE' => ['languages' => ['sv-SE' => 78, 'en-US' => 22], 'timezones' => ['Europe/Stockholm' => 100], 'networks' => [[3301, 'Telia Company AB'], [1257, 'Tele2 Sverige AB']]],
        'NO' => ['languages' => ['nb-NO' => 80, 'en-US' => 20], 'timezones' => ['Europe/Oslo' => 100], 'networks' => [[2119, 'Telenor Norge AS']]],
        'FI' => ['languages' => ['fi-FI' => 80, 'en-US' => 20], 'timezones' => ['Europe/Helsinki' => 100], 'networks' => [[1759, 'Telia Finland Oyj'], [719, 'Elisa Oyj']]],
        'PL' => ['languages' => ['pl-PL' => 90, 'en-US' => 10], 'timezones' => ['Europe/Warsaw' => 100], 'networks' => [[5617, 'Orange Polska Spolka Akcyjna'], [12741, 'Netia SA']]],
        'CZ' => ['languages' => ['cs-CZ' => 88, 'en-US' => 12], 'timezones' => ['Europe/Prague' => 100], 'networks' => [[5610, 'O2 Czech Republic, a.s.']]],
        'RO' => ['languages' => ['ro-RO' => 85, 'en-US' => 15], 'timezones' => ['Europe/Bucharest' => 100], 'networks' => [[8708, 'RCS & RDS SA']]],
        'IL' => ['languages' => ['he-IL' => 70, 'en-US' => 30], 'timezones' => ['Asia/Jerusalem' => 100], 'networks' => [[8551, 'Bezeq International Ltd.']]],
        'IN' => ['languages' => ['en-IN' => 70, 'en-US' => 20, 'hi-IN' => 10], 'timezones' => ['Asia/Kolkata' => 100], 'networks' => [[55836, 'Reliance Jio Infocomm Limited'], [24560, 'Bharti Airtel Ltd., Telemedia Services']]],
        'SG' => ['languages' => ['en-SG' => 70, 'en-US' => 20, 'zh-SG' => 10], 'timezones' => ['Asia/Singapore' => 100], 'networks' => [[9506, 'Singtel Fibre Broadband'], [55430, 'Starhub Ltd']]],
        'JP' => ['languages' => ['ja-JP' => 92, 'en-US' => 8], 'timezones' => ['Asia/Tokyo' => 100], 'networks' => [[2516, 'KDDI CORPORATION'], [4713, 'NTT Communications Corporation'], [17676, 'SoftBank Corp.']]],
        'KR' => ['languages' => ['ko-KR' => 92, 'en-US' => 8], 'timezones' => ['Asia/Seoul' => 100], 'networks' => [[4766, 'Korea Telecom']]],
        'AU' => ['languages' => ['en-AU' => 90, 'en-US' => 10], 'timezones' => ['Australia/Sydney' => 55, 'Australia/Melbourne' => 35, 'Australia/Perth' => 10], 'networks' => [[1221, 'Telstra Limited'], [7474, 'SingTel Optus Pty Ltd'], [7545, 'TPG Telecom Limited']]],
        'NZ' => ['languages' => ['en-NZ' => 90, 'en-US' => 10], 'timezones' => ['Pacific/Auckland' => 100], 'networks' => [[4771, 'Spark New Zealand Trading Ltd.']]],
        'ZA' => ['languages' => ['en-ZA' => 90, 'en-US' => 10], 'timezones' => ['Africa/Johannesburg' => 100], 'networks' => [[37457, 'Telkom SA Ltd.']]],
    ];

    /**
     * Privacy relays and VPNs: a small share of visitors appear behind these networks.
     *
     * @var list<array{0: int, 1: string}>
     */
    private const RELAY_NETWORKS = [
        [13335, 'Cloudflare, Inc.'],
        [36183, 'Akamai Technologies, Inc.'],
    ];

    /**
     * Platform names follow what the tracker records (jenssegers/agent).
     *
     * @var array<string, array<string, int>>
     */
    private const PLATFORMS = [
        'desktop' => ['Windows' => 55, 'OS X' => 31, 'Linux' => 8, 'Ubuntu' => 3, 'ChromeOS' => 3],
        'mobile' => ['iOS' => 52, 'AndroidOS' => 48],
        'tablet' => ['iOS' => 72, 'AndroidOS' => 28],
    ];

    /**
     * @var array<string, array<string, int>>
     */
    private const BROWSERS = [
        'Windows' => ['Chrome' => 66, 'Edge' => 22, 'Firefox' => 9, 'Opera' => 3],
        'OS X' => ['Chrome' => 50, 'Safari' => 40, 'Firefox' => 8, 'Edge' => 2],
        'Linux' => ['Chrome' => 52, 'Firefox' => 48],
        'Ubuntu' => ['Firefox' => 60, 'Chrome' => 40],
        'ChromeOS' => ['Chrome' => 100],
        'iOS' => ['Safari' => 84, 'Chrome' => 14, 'Firefox' => 2],
        'AndroidOS' => ['Chrome' => 88, 'Firefox' => 5, 'Opera' => 4, 'Edge' => 3],
    ];

    /**
     * Crawlers as recorded by the tracker: name, version, ASN, organization.
     *
     * @var list<array{0: string, 1: string, 2: int, 3: string, 4: int}>
     */
    private const CRAWLERS = [
        ['Googlebot', '2.1', 15169, 'Google LLC', 50],
        ['Bingbot', '2.0', 8075, 'Microsoft Corporation', 25],
        ['Applebot', '0.1', 714, 'Apple Inc.', 12],
        ['DuckDuckBot', '1.1', 8075, 'Microsoft Corporation', 8],
        ['YandexBot', '3.0', 13238, 'YANDEX LLC', 5],
    ];

    /**
     * Stable releases at a reference date and their cadence, used to age browser versions over the demo period.
     */
    private const RELEASE_REFERENCE = '2026-10-07';

    public function __construct(private Randomizer $random) {}

    /**
     * @param  array<string, int>  $countries
     * @param  array<string, int>  $devices
     * @return array{visitor_id: string, country_code: string, browser_language: string, timezone: string, device_type: string, os: string, browser: string, ip_address: string, asn: ?int, as_organization: ?string, is_bot: bool}
     */
    public function visitor(array $countries, array $devices): array
    {
        $country = $this->pick($countries);
        $profile = self::COUNTRIES[$country] ?? self::COUNTRIES['US'];
        $device = $this->pick($devices);
        $os = $this->pick(self::PLATFORMS[$device]);

        $network = $this->chance(0.04)
            ? self::RELAY_NETWORKS[$this->random->getInt(0, count(self::RELAY_NETWORKS) - 1)]
            : $this->pickNetwork($profile['networks']);

        return [
            'visitor_id' => $this->uuid(),
            'country_code' => $country,
            'browser_language' => $this->pick($profile['languages']),
            'timezone' => $this->pick($profile['timezones']),
            'device_type' => $device,
            'os' => $os,
            'browser' => $this->pick(self::BROWSERS[$os]),
            'ip_address' => $this->ipAddress(),
            'asn' => $network[0],
            'as_organization' => $network[1],
            'is_bot' => false,
        ];
    }

    /**
     * @return array{visitor_id: string, country_code: string, browser_language: string, timezone: ?string, device_type: string, os: string, browser: string, browser_version: string, ip_address: string, asn: int, as_organization: string, is_bot: bool}
     */
    public function crawler(): array
    {
        $weights = [];
        foreach (self::CRAWLERS as $index => $crawler) {
            $weights[$index] = $crawler[4];
        }
        [$name, $version, $asn, $organization] = self::CRAWLERS[$this->pick($weights)];

        return [
            'visitor_id' => $this->uuid(),
            'country_code' => 'US',
            'browser_language' => 'en-US',
            'timezone' => null,
            'device_type' => 'desktop',
            'os' => 'unknown',
            'browser' => $name,
            'browser_version' => $version,
            'ip_address' => $this->ipAddress(),
            'asn' => $asn,
            'as_organization' => $organization,
            'is_bot' => true,
        ];
    }

    /**
     * Browser version a visitor would report on a given day: newer releases over time, with some users lagging behind.
     */
    public function browserVersion(string $browser, int $timestamp): string
    {
        $weeksBack = intdiv(strtotime(self::RELEASE_REFERENCE.' 00:00:00 UTC') - $timestamp, 7 * 86400);
        $lag = $this->pick([0 => 60, 1 => 28, 2 => 8, 3 => 4]);

        if ($browser === 'Safari') {
            // Safari ships a major release each September and a minor one roughly every eight weeks.
            $minorsBack = max(0, intdiv($weeksBack + 3, 8)) + ($lag > 1 ? 1 : 0);
            $major = 27;
            $minor = 0;
            while ($minorsBack > 0) {
                if ($minor === 0) {
                    $major--;
                    $minor = 6;
                } else {
                    $minor--;
                }
                $minorsBack--;
            }

            return "{$major}.{$minor}";
        }

        $current = match ($browser) {
            'Firefox' => 157,
            'Opera' => 135,
            default => 154,
        };
        $major = $current - max(0, intdiv($weeksBack, 4)) - $lag;

        return $browser === 'Firefox' ? "{$major}.0" : "{$major}.0.0.0";
    }

    /**
     * @template TKey of array-key
     *
     * @param  array<TKey, int|float>  $weights
     * @return TKey
     */
    public function pick(array $weights): int|string
    {
        $total = array_sum($weights);
        $target = $this->random->nextFloat() * $total;

        foreach ($weights as $value => $weight) {
            $target -= $weight;
            if ($target < 0) {
                return $value;
            }
        }

        return array_key_last($weights);
    }

    public function chance(float $probability): bool
    {
        return $this->random->nextFloat() < $probability;
    }

    public function uuid(): string
    {
        $bytes = $this->random->getBytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * @param  list<array{0: int, 1: string}>  $networks
     * @return array{0: int, 1: string}
     */
    private function pickNetwork(array $networks): array
    {
        $weights = [];
        foreach ($networks as $index => $network) {
            $weights[$index] = count($networks) - $index + 1;
        }

        return $networks[$this->pick($weights)];
    }

    private function ipAddress(): string
    {
        if ($this->chance(0.3)) {
            return sprintf('2001:db8:%x:%x::%x', $this->random->getInt(0, 0xFFFF), $this->random->getInt(0, 0xFFFF), $this->random->getInt(1, 0xFFFF));
        }

        $prefix = ['192.0.2', '198.51.100', '203.0.113'][$this->random->getInt(0, 2)];

        return $prefix.'.'.$this->random->getInt(1, 254);
    }
}
