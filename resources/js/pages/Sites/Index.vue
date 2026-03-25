<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Copy, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';

type SiteRow = {
    id: number;
    name: string;
    public_key: string;
    allowed_domains: string | null;
    embed_code: string;
    created_at: string;
};

defineProps<{
    sites: SiteRow[];
}>();

const page = usePage<{ flash?: { success?: string } }>();
const flashSuccess = computed(() => page.props.flash?.success);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Siti', href: '/sites' },
];

const form = useForm({
    name: '',
    allowed_domains: '',
});

function submit() {
    form.post('/sites');
}

function copy(text: string) {
    void navigator.clipboard.writeText(text);
}

function destroySite(id: number) {
    if (!confirm('Eliminare questo sito e tutte le statistiche?')) {
        return;
    }
    router.delete(`/sites/${id}`);
}
</script>

<template>
    <Head title="Siti" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-8 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">I tuoi siti</h1>
                <p class="text-muted-foreground text-sm">
                    Aggiungi un sito e incolla lo snippet sulle pagine che vuoi
                    misurare.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Nuovo sito</CardTitle>
                    <CardDescription>
                        Nome interno e, opzionalmente, domini consentiti
                        (es.
                        <code class="text-xs">miosito.com, www.miosito.com</code>
                        ). Vuoto = accetta da qualsiasi origine (solo per test).
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        class="flex flex-col gap-4"
                        @submit.prevent="submit"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Nome</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                                placeholder="Il mio blog"
                                autocomplete="off"
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-destructive text-sm"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="allowed_domains"
                                >Domini consentiti (opzionale)</Label
                            >
                            <Input
                                id="allowed_domains"
                                v-model="form.allowed_domains"
                                placeholder="esempio.com, www.esempio.com"
                                autocomplete="off"
                            />
                            <p
                                v-if="form.errors.allowed_domains"
                                class="text-destructive text-sm"
                            >
                                {{ form.errors.allowed_domains }}
                            </p>
                        </div>
                        <Button type="submit" :disabled="form.processing">
                            Aggiungi sito
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <div
                v-if="flashSuccess"
                class="text-sm text-green-600 dark:text-green-400"
            >
                {{ flashSuccess }}
            </div>

            <div v-if="sites.length === 0" class="text-muted-foreground text-sm">
                Nessun sito ancora. Creane uno qui sopra.
            </div>

            <div v-else class="flex flex-col gap-4">
                <Card v-for="site in sites" :key="site.id">
                    <CardHeader
                        class="flex flex-row items-start justify-between gap-4 space-y-0"
                    >
                        <div class="min-w-0 flex-1">
                            <CardTitle class="truncate">
                                <Link
                                    :href="`/sites/${site.id}`"
                                    class="hover:underline"
                                >
                                    {{ site.name }}
                                </Link>
                            </CardTitle>
                            <CardDescription class="mt-1 font-mono text-xs">
                                {{ site.public_key }}
                            </CardDescription>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <Button variant="outline" size="icon" as-child>
                                <Link :href="`/sites/${site.id}`">
                                    <span class="sr-only">Statistiche</span>
                                    →
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                type="button"
                                @click="copy(site.embed_code)"
                            >
                                <Copy class="size-4" />
                                <span class="sr-only">Copia snippet</span>
                            </Button>
                            <Button
                                variant="destructive"
                                size="icon"
                                type="button"
                                @click="destroySite(site.id)"
                            >
                                <Trash2 class="size-4" />
                                <span class="sr-only">Elimina</span>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <pre
                            class="bg-muted max-h-32 overflow-auto rounded-md p-3 font-mono text-xs leading-relaxed whitespace-pre-wrap"
                            >{{ site.embed_code }}</pre
                        >
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
