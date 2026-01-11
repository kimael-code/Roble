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
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { BreadcrumbItem, Organization } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue-inertia';
import { Building, LoaderCircleIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
  organization: Organization;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Entes',
    href: '/organizations',
  },
  {
    title: 'Editar',
    href: '',
  },
];

const buttonCancel = ref(false);
const urlLogo = ref(props.organization.logo_url);
const statusDisabled = computed(() =>
  props.organization.disabled_at ? true : false,
);

type OrganizationForm = {
  rif: string;
  name: string;
  logo_path: File | string | null;
  acronym: string;
  address: string;
  disabled: boolean;
};

const form = useForm('post', routes.update(props.organization.id).url, <
  OrganizationForm
>{
  _method: 'put',
  rif: props.organization.rif,
  name: props.organization.name,
  logo_path: props.organization.logo_path,
  acronym: props.organization.acronym,
  address: props.organization.address,
  disabled: statusDisabled.value,
});

function handleLogoChange(e: Event) {
  const inputElement = e.target as HTMLInputElement;

  if (inputElement && inputElement.files && inputElement.files.length) {
    urlLogo.value = URL.createObjectURL(inputElement.files[0]);
    form.logo_path = inputElement.files[0];
  } else {
    urlLogo.value = props.organization.logo_url;
    form.logo_path = props.organization.logo_path;
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
    <Head title="Editar Ente" />
    <ContentLayout title="Editar Ente">
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
                    id="address"
                    v-model="form.address"
                    autocomplete="street-address"
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
                <div class="flex items-center space-x-2">
                  <Checkbox
                    id="disabled"
                    v-model:model-value="form.disabled"
                    @update:model-value="form.validate('disabled')"
                    @keyup.enter.prevent="submit"
                    @keyup.esc="index"
                  />
                  <Label for="disabled">Desactivar</Label>
                  <InputError :message="form.errors.disabled" />
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
