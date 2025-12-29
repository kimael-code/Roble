<script lang="ts" setup>
import OrganizationalUnitController from '@/actions/App/Http/Controllers/Organization/OrganizationalUnitController';
import InputError from '@/components/InputError.vue';
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
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { BreadcrumbItem, Organization, OrganizationalUnit } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue-inertia';
import { LoaderCircleIcon, Workflow } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
  activeOrganizations: Array<Organization>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Unidades Administrativas',
    href: '/organizational-units',
  },
  {
    title: 'Nuevo',
    href: '',
  },
];

const buttonCancel = ref(false);
const ous = ref<Array<OrganizationalUnit>>();

type OrganizationalUnitForm = {
  organization_id: number | null;
  organizational_unit_id: string;
  code: string;
  name: string;
  acronym: string;
  floor: string;
};

const form = useForm('post', OrganizationalUnitController.store().url, <
  OrganizationalUnitForm
>{
  organization_id: null,
  organizational_unit_id: '',
  code: '',
  name: '',
  acronym: '',
  floor: '',
});

watch(
  () => form.organization_id,
  (id) => {
    if (id) {
      ous.value = props.activeOrganizations.find(
        (o) => o.id === id,
      )?.active_organizational_units;
    }
  },
);

function submit() {
  form.submit({
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}

function index() {
  router.visit(OrganizationalUnitController.index(), {
    onStart: () => (buttonCancel.value = true),
    onFinish: () => (buttonCancel.value = false),
  });
}
</script>

<template>
  <AppLayout :breadcrumbs>
    <Head title="Crear Nueva Unidad Administrativa" />
    <ContentLayout title="Crear Nueva Unidad Administrativa">
      <template #icon>
        <Workflow />
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
                  <Label class="is-required" for="organization_id">Ente</Label>
                  <Select
                    v-model="form.organization_id"
                    required
                    @update:model-value="form.validate('organization_id')"
                  >
                    <SelectTrigger id="organization_id" autofocus>
                      <SelectValue placeholder="Seleccione Ente" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Entes</SelectLabel>
                        <SelectItem
                          v-for="(organization, i) in activeOrganizations"
                          :value="organization.id"
                          :key="i"
                        >
                          {{ organization.name }}
                        </SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.organization_id" />
                </div>
                <div class="flex flex-col space-y-1.5">
                  <Label class="is-required" for="name">Nombre</Label>
                  <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    maxlength="255"
                    autocomplete="on"
                    placeholder="ej.: Dirección de Tecnología"
                    required
                    @change="form.validate('name')"
                    @keyup.enter.prevent="submit"
                    @keyup.esc="index"
                  />
                  <InputError :message="form.errors.name" />
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                    <Label for="code">Código</Label>
                    <Input
                      id="code"
                      v-model="form.code"
                      type="text"
                      maxlength="20"
                      placeholder="ej.: 000000009999"
                      @change="form.validate('code')"
                      @keyup.enter.prevent="submit"
                      @keyup.esc="index"
                    />
                    <InputError :message="form.errors.code" />
                  </div>
                  <div class="flex flex-col space-y-1.5">
                    <Label for="floor">Piso</Label>
                    <Input
                      id="floor"
                      v-model="form.floor"
                      type="text"
                      maxlength="5"
                      placeholder="ej.: PB"
                      @change="form.validate('floor')"
                      @keyup.enter.prevent="submit"
                      @keyup.esc="index"
                    />
                    <InputError :message="form.errors.floor" />
                  </div>
                </div>
                <div class="flex flex-col space-y-1.5">
                  <Label for="organizational_unit_id"
                    >Unidad Administrativa de Adscripción</Label
                  >
                  <Select
                    v-model="form.organizational_unit_id"
                    required
                    @change="form.validate('organizational_unit_id')"
                  >
                    <SelectTrigger id="organizational_unit_id">
                      <SelectValue
                        placeholder="Seleccione Unidad Administrativa de Adscripción"
                      />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>Unidades Administrativas</SelectLabel>
                        <SelectItem
                          v-for="(ou, i) in ous"
                          :value="ou.id"
                          :key="i"
                        >
                          {{ ou.name }}
                        </SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.organizational_unit_id" />
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
