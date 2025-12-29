<script lang="ts" setup>
import routes from '@/actions/App/Http/Controllers/Organization/OrganizationController';
import InputError from '@/components/InputError.vue';
import { AspectRatio } from '@/components/ui/aspect-ratio';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue-inertia';
import { Building, LoaderCircleIcon } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Entes',
    href: '/organizations',
  },
  {
    title: 'Nuevo',
    href: '',
  },
];

const buttonCancel = ref(false);
const urlLogo = ref('');

type OrganizationForm = {
  rif: string;
  name: string;
  logo_path: File | null;
  acronym: string;
  address: string;
};

const form = useForm('post', routes.store().url, <OrganizationForm>{
  rif: '',
  name: '',
  logo_path: null,
  acronym: '',
  address: '',
});

function handleLogoChange(e: Event) {
  const inputElement = e.target as HTMLInputElement;

  if (inputElement && inputElement.files && inputElement.files.length) {
    urlLogo.value = URL.createObjectURL(inputElement.files[0]);
    form.logo_path = inputElement.files[0];
  } else {
    urlLogo.value = '';
    form.logo_path = null;
  }
}

function submit() {
  form.submit({
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}

function index() {
  router.visit(routes.index(), {
    onStart: () => (buttonCancel.value = true),
    onFinish: () => (buttonCancel.value = false),
  });
}
</script>

<template>
  <AppLayout :breadcrumbs>
    <Head title="Crear Nuevo Ente" />
    <ContentLayout title="Crear Nuevo Ente">
      <template #icon>
        <Building />
      </template>
      <section class="mx-auto w-full">
        <Card class="container">
          <CardHeader>
            <CardDescription
              >Los campos con asterisco rojo son requeridos.</CardDescription
            >
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submit">
              <div class="grid w-full items-center gap-4">
                <div class="flex flex-col space-y-1.5">
                  <Label class="is-required" for="rif">RIF</Label>
                  <Input
                    id="rif"
                    v-model="form.rif"
                    type="text"
                    maxlength="12"
                    autocomplete="on"
                    placeholder="ej.: J-12345678-9"
                    required
                    autofocus
                    @change="form.validate('rif')"
                    @keyup.enter.prevent="submit"
                    @keyup.esc="index"
                  />
                  <InputError :message="form.errors.rif" />
                </div>
                <div class="flex flex-col space-y-1.5">
                  <Label class="is-required" for="name">Nombre</Label>
                  <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    maxlength="255"
                    autocomplete="organization"
                    placeholder="ej.: Global Fonseca y Tórrez"
                    required
                    @change="form.validate('name')"
                    @keyup.enter.prevent="submit"
                    @keyup.esc="index"
                  />
                  <InputError :message="form.errors.name" />
                </div>
                <div class="flex flex-col space-y-1.5">
                  <Label for="acronym">Acrónimo</Label>
                  <Input
                    id="acronym"
                    v-model="form.acronym"
                    type="text"
                    maxlength="20"
                    placeholder="ej.: ACME"
                    @change="form.validate('acronym')"
                    @keyup.enter.prevent="submit"
                    @keyup.esc="index"
                  />
                  <InputError :message="form.errors.acronym" />
                </div>
                <div class="flex flex-col space-y-1.5">
                  <Label for="address">Dirección</Label>
                  <Textarea
                    id="adrress"
                    v-model="form.address"
                    placeholder="ej.: Carretera Ybarra, Edif 6, Abril de Asis Edo. Vargas"
                    @change="form.validate('address')"
                    @keyup.esc="index"
                  ></Textarea>
                </div>
                <div class="flex flex-col space-y-1.5">
                  <Label class="is-required" for="logo_path">Logo</Label>
                  <Input
                    type="file"
                    id="logo_path"
                    accept="image/png"
                    @change="handleLogoChange"
                  />
                  <InputError :message="form.errors.logo_path" />
                </div>
                <div
                  v-if="urlLogo"
                  class="w-full overflow-hidden rounded-xs shadow-sm sm:w-[350px]"
                >
                  <AspectRatio :ratio="31 / 8">
                    <img
                      class="h-full w-full object-cover"
                      :src="urlLogo"
                      alt="Logo seleccionado"
                    />
                  </AspectRatio>
                </div>
              </div>
            </form>
          </CardContent>
          <CardFooter class="flex justify-between px-6 pb-6">
            <Button
              variant="outline"
              :disabled="buttonCancel"
              @click="index"
              @keyup.esc="index"
              @keyup.enter="index"
            >
              <LoaderCircleIcon
                v-if="buttonCancel"
                class="h-4 w-4 animate-spin"
              />
              Cancelar
            </Button>
            <Button
              :disabled="buttonCancel || form.processing"
              @click="submit"
              @keyup.esc="index"
              @keyup.enter="submit"
            >
              <LoaderCircleIcon
                v-if="form.processing"
                class="h-4 w-4 animate-spin"
              />
              Guardar
            </Button>
          </CardFooter>
        </Card>
      </section>
    </ContentLayout>
  </AppLayout>
</template>
