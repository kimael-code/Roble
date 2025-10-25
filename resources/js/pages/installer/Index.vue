<script setup lang="ts">
import { wizard } from '@/actions/App/Http/Controllers/InstallerController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircleIcon } from 'lucide-vue-next';
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-gray-100 opacity-100 transition-opacity duration-750 dark:bg-gray-900 starting:opacity-0"
    >
        <Head title="Instalador" />

        <Card class="mx-4 w-full max-w-xl">
            <CardHeader class="text-center">
                <CardTitle class="text-2xl font-bold">
                    Bienvenido al Instalador del Superusuario en
                    {{ $page.props.name }}
                </CardTitle>
                <CardDescription>
                    Gracias por elegir {{ $page.props.name }}. Siga los pasos
                    para configurar su sistema.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="space-y-6">
                    <Separator />
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400">
                            Haga clic en el botón de abajo para comenzar el
                            proceso de instalación.
                        </p>
                    </div>
                    <Form :action="wizard()" v-slot="{ processing }">
                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="processing"
                        >
                            <LoaderCircleIcon
                                v-if="processing"
                                class="h-4 w-4 animate-spin"
                            />
                            Iniciar Instalación
                        </Button>
                    </Form>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
