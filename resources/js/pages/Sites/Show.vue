<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';

type Breakdown = {
    pageviews: number;
    visitors: number;
};

type DayRow = Breakdown & { date: string };
type PathRow = Breakdown & { path: string };
type SourceRow = Breakdown & { source: string };
type NameRow = Breakdown & { name: string };
type CountryRow = Breakdown & { code: string | null };

type SearchRow = Breakdown & { query: string };
type UtmRow = Breakdown & { utm_source: string };

type Stats = {
    unique_visitors: number;
    total_pageviews: number;
    avg_duration_seconds: number | null;
    by_day: DayRow[];
    by_path: PathRow[];
    by_source: SourceRow[];
    by_browser: NameRow[];
    by_device: NameRow[];
    by_country: CountryRow[];
    outbound_clicks: number;
    by_search_query: SearchRow[];
    by_utm_source: UtmRow[];
};

type SitePayload = {
    id: number;
    name: string;
    public_key: string;
    allowed_domains: string | null;
    embed_code: string;
};

const props = defineProps<{
    site: SitePayload;
    stats: Stats;
    range: string;
    period: { from: string; to: string };
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Siti', href: '/sites' },
    { title: props.site.name, href: `/sites/${props.site.id}` },
]);

const maxDayPv = computed(() =>
    Math.max(...props.stats.by_day.map((d) => d.pageviews), 1),
);

function rangeUrl(r: string) {
    return `/sites/${props.site.id}?range=${encodeURIComponent(r)}`;
}

function countryLabel(code: string | null) {
    if (!code) {
        return 'Sconosciuto';
    }
    try {
        const dn = new Intl.DisplayNames(['it'], { type: 'region' });
        return dn.of(code) ?? code;
    } catch {
        return code;
    }
}
</script>

<template>
    <Head :title="`${site.name} · Statistiche`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ site.name }}
                    </h1>
                    <p class="text-muted-foreground text-sm">
                        Periodo: {{ period.from }} — {{ period.to }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="rangeUrl('today')"
                        class="ring-offset-background focus-visible:ring-ring inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        :class="
                            range === 'today'
                                ? 'bg-primary text-primary-foreground border-primary'
                                : 'bg-background hover:bg-muted border-input'
                        "
                    >
                        Oggi
                    </Link>
                    <Link
                        :href="rangeUrl('7d')"
                        class="ring-offset-background focus-visible:ring-ring inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        :class="
                            range === '7d'
                                ? 'bg-primary text-primary-foreground border-primary'
                                : 'bg-background hover:bg-muted border-input'
                        "
                    >
                        7 giorni
                    </Link>
                    <Link
                        :href="rangeUrl('30d')"
                        class="ring-offset-background focus-visible:ring-ring inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        :class="
                            range === '30d'
                                ? 'bg-primary text-primary-foreground border-primary'
                                : 'bg-background hover:bg-muted border-input'
                        "
                    >
                        30 giorni
                    </Link>
                    <Link
                        :href="rangeUrl('3m')"
                        class="ring-offset-background focus-visible:ring-ring inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        :class="
                            range === '3m'
                                ? 'bg-primary text-primary-foreground border-primary'
                                : 'bg-background hover:bg-muted border-input'
                        "
                    >
                        3 mesi
                    </Link>
                    <Link
                        :href="rangeUrl('6m')"
                        class="ring-offset-background focus-visible:ring-ring inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        :class="
                            range === '6m'
                                ? 'bg-primary text-primary-foreground border-primary'
                                : 'bg-background hover:bg-muted border-input'
                        "
                    >
                        6 mesi
                    </Link>
                    <Link
                        :href="rangeUrl('1y')"
                        class="ring-offset-background focus-visible:ring-ring inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        :class="
                            range === '1y'
                                ? 'bg-primary text-primary-foreground border-primary'
                                : 'bg-background hover:bg-muted border-input'
                        "
                    >
                        1 anno
                    </Link>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Visitatori unici</CardDescription>
                        <CardTitle class="text-3xl tabular-nums">
                            {{ stats.unique_visitors }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Visualizzazioni</CardDescription>
                        <CardTitle class="text-3xl tabular-nums">
                            {{ stats.total_pageviews }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Tempo medio in pagina</CardDescription>
                        <CardTitle class="text-3xl tabular-nums">
                            {{
                                stats.avg_duration_seconds != null
                                    ? `${stats.avg_duration_seconds}s`
                                    : '—'
                            }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Click in uscita</CardDescription>
                        <CardTitle class="text-3xl tabular-nums">
                            {{ stats.outbound_clicks }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <Card v-if="stats.by_day.length">
                <CardHeader>
                    <CardTitle class="text-base">Andamento</CardTitle>
                    <CardDescription>Visualizzazioni per giorno</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div
                        v-for="row in stats.by_day"
                        :key="row.date"
                        class="flex items-center gap-3 text-sm"
                    >
                        <span
                            class="text-muted-foreground w-28 shrink-0 font-mono text-xs"
                            >{{ row.date }}</span
                        >
                        <div
                            class="bg-muted h-7 min-w-0 flex-1 overflow-hidden rounded"
                        >
                            <div
                                class="bg-primary/80 flex h-full items-center px-2 text-xs text-white"
                                :style="{
                                    width: `${Math.max(8, (row.pageviews / maxDayPv) * 100)}%`,
                                }"
                            >
                                <span class="tabular-nums">{{
                                    row.pageviews
                                }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Pagine</CardTitle>
                        <CardDescription>Top percorsi</CardDescription>
                    </CardHeader>
                    <CardContent class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-muted-foreground border-b">
                                    <th class="py-2 pr-2 font-medium">
                                        Percorso
                                    </th>
                                    <th class="py-2 pr-2 text-right font-medium">
                                        Viste
                                    </th>
                                    <th class="py-2 text-right font-medium">
                                        Univoci
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in stats.by_path"
                                    :key="row.path"
                                    class="border-b border-border/60"
                                >
                                    <td
                                        class="max-w-[200px] truncate py-2 pr-2 font-mono text-xs"
                                        :title="row.path"
                                    >
                                        {{ row.path }}
                                    </td>
                                    <td class="py-2 pr-2 text-right tabular-nums">
                                        {{ row.pageviews }}
                                    </td>
                                    <td class="py-2 text-right tabular-nums">
                                        {{ row.visitors }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card v-if="stats.by_utm_source.length">
                    <CardHeader>
                        <CardTitle class="text-base">UTM source</CardTitle>
                        <CardDescription>Campagne etichettate</CardDescription>
                    </CardHeader>
                    <CardContent class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-muted-foreground border-b">
                                    <th class="py-2 pr-2 font-medium">
                                        utm_source
                                    </th>
                                    <th class="py-2 pr-2 text-right font-medium">
                                        Viste
                                    </th>
                                    <th class="py-2 text-right font-medium">
                                        Univoci
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in stats.by_utm_source"
                                    :key="row.utm_source"
                                    class="border-b border-border/60"
                                >
                                    <td class="max-w-[200px] truncate py-2 pr-2">
                                        {{ row.utm_source }}
                                    </td>
                                    <td class="py-2 pr-2 text-right tabular-nums">
                                        {{ row.pageviews }}
                                    </td>
                                    <td class="py-2 text-right tabular-nums">
                                        {{ row.visitors }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card v-if="stats.by_search_query.length">
                    <CardHeader>
                        <CardTitle class="text-base">Query di ricerca</CardTitle>
                        <CardDescription
                            >Termini da motori di ricerca o parametri
                            ?q= sulla pagina</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-muted-foreground border-b">
                                    <th class="py-2 pr-2 font-medium">Query</th>
                                    <th class="py-2 pr-2 text-right font-medium">
                                        Viste
                                    </th>
                                    <th class="py-2 text-right font-medium">
                                        Univoci
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in stats.by_search_query"
                                    :key="row.query"
                                    class="border-b border-border/60"
                                >
                                    <td class="max-w-[240px] truncate py-2 pr-2">
                                        {{ row.query }}
                                    </td>
                                    <td class="py-2 pr-2 text-right tabular-nums">
                                        {{ row.pageviews }}
                                    </td>
                                    <td class="py-2 text-right tabular-nums">
                                        {{ row.visitors }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Sorgenti</CardTitle>
                        <CardDescription>Referrer / motore</CardDescription>
                    </CardHeader>
                    <CardContent class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-muted-foreground border-b">
                                    <th class="py-2 pr-2 font-medium">
                                        Sorgente
                                    </th>
                                    <th class="py-2 pr-2 text-right font-medium">
                                        Viste
                                    </th>
                                    <th class="py-2 text-right font-medium">
                                        Univoci
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in stats.by_source"
                                    :key="row.source"
                                    class="border-b border-border/60"
                                >
                                    <td class="max-w-[180px] truncate py-2 pr-2">
                                        {{ row.source }}
                                    </td>
                                    <td class="py-2 pr-2 text-right tabular-nums">
                                        {{ row.pageviews }}
                                    </td>
                                    <td class="py-2 text-right tabular-nums">
                                        {{ row.visitors }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Browser</CardTitle>
                    </CardHeader>
                    <CardContent class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-muted-foreground border-b">
                                    <th class="py-2 pr-2 font-medium">
                                        Browser
                                    </th>
                                    <th class="py-2 pr-2 text-right font-medium">
                                        Viste
                                    </th>
                                    <th class="py-2 text-right font-medium">
                                        Univoci
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in stats.by_browser"
                                    :key="row.name"
                                    class="border-b border-border/60"
                                >
                                    <td class="py-2 pr-2">{{ row.name }}</td>
                                    <td class="py-2 pr-2 text-right tabular-nums">
                                        {{ row.pageviews }}
                                    </td>
                                    <td class="py-2 text-right tabular-nums">
                                        {{ row.visitors }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Dispositivo</CardTitle>
                    </CardHeader>
                    <CardContent class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-muted-foreground border-b">
                                    <th class="py-2 pr-2 font-medium">Tipo</th>
                                    <th class="py-2 pr-2 text-right font-medium">
                                        Viste
                                    </th>
                                    <th class="py-2 text-right font-medium">
                                        Univoci
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in stats.by_device"
                                    :key="row.name"
                                    class="border-b border-border/60"
                                >
                                    <td class="py-2 pr-2">{{ row.name }}</td>
                                    <td class="py-2 pr-2 text-right tabular-nums">
                                        {{ row.pageviews }}
                                    </td>
                                    <td class="py-2 text-right tabular-nums">
                                        {{ row.visitors }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="text-base">Paese</CardTitle>
                    </CardHeader>
                    <CardContent class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-muted-foreground border-b">
                                    <th class="py-2 pr-2 font-medium">Paese</th>
                                    <th class="py-2 pr-2 text-right font-medium">
                                        Viste
                                    </th>
                                    <th class="py-2 text-right font-medium">
                                        Univoci
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, i) in stats.by_country"
                                    :key="`${row.code ?? 'x'}-${i}`"
                                    class="border-b border-border/60"
                                >
                                    <td class="py-2 pr-2">
                                        {{ countryLabel(row.code) }}
                                        <span
                                            v-if="row.code"
                                            class="text-muted-foreground ml-1 font-mono text-xs"
                                            >({{ row.code }})</span
                                        >
                                    </td>
                                    <td class="py-2 pr-2 text-right tabular-nums">
                                        {{ row.pageviews }}
                                    </td>
                                    <td class="py-2 text-right tabular-nums">
                                        {{ row.visitors }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Snippet</CardTitle>
                    <CardDescription>
                        Incolla prima della chiusura di
                        <code class="text-xs">&lt;/body&gt;</code>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <pre
                        class="bg-muted overflow-auto rounded-md p-3 font-mono text-xs leading-relaxed whitespace-pre-wrap"
                        >{{ site.embed_code }}</pre
                    >
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
