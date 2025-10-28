<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Switch } from '@/components/ui/switch';
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/components/ui/tags-input';
import AppLayout from '@/layouts/AppLayout.vue';
import ContentLayout from '@/layouts/ContentLayout.vue';
import { BreadcrumbItem, Employee, OrganizationalUnit, Pagination, Permission, Role, SearchFilter } from '@/types';
import { Head, router, WhenVisible } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { useForm } from 'laravel-precognition-vue-inertia';
import { LoaderCircleIcon, Search, UserIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import CardPerson from './partials/CardPerson.vue';
import UserController from "@/actions/App/Http/Controllers/Security/UserController";

const props = defineProps<{
  filters: SearchFilter;
  employees: Array<Employee>;
  ous: Array<OrganizationalUnit>;
  permissions: Array<Permission>;
  roles: Array<Role>;
  paginationOu: Pagination<OrganizationalUnit>;
  paginationPerm: Pagination<Permission>;
  paginationRole: Pagination<Role>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Usuarios',
    href: '/users',
  },
  {
    title: 'Nuevo',
    href: '',
  },
];

const buttonCancel = ref(false);
const openSheetPermissions = ref(false);
const openSheetRoles = ref(false);
const openSheetOUs = ref(false);
const openDialog = ref(false);
const search = ref(props.filters.search);
const searching = ref(false);

type formUser = {
  name: string;
  email: string;
  is_external: boolean;
  id_card: string;
  names: string;
  surnames: string;
  phones: string;
  emails: string;
  position: string;
  staff_type: string;
  ou_names: string[];
  roles: string[];
  permissions: string[];
};

const form = useForm('post', UserController.store().url, <formUser>{
  name: '',
  email: '',
  is_external: false,
  id_card: '',
  names: '',
  surnames: '',
  phones: '',
  emails: '',
  position: '',
  staff_type: '',
  ou_names: [],
  roles: [],
  permissions: [],
});

const showDialogTrigger = computed(() => (form.id_card ? false : true));

const ouNamesError = computed(() => {
  const errors = Object.entries(form.errors)
    .filter(([key]) => key.startsWith('ou_names.'))
    .map(([, value]) => value);
  return errors.length ? errors[0] : form.errors.ou_names || '';
});

const rolesError = computed(() => {
  const errors = Object.entries(form.errors)
    .filter(([key]) => key.startsWith('roles.'))
    .map(([, value]) => value);
  return errors.length ? errors[0] : form.errors.roles || '';
});

const permissionsError = computed(() => {
  const errors = Object.entries(form.errors)
    .filter(([key]) => key.startsWith('permissions.'))
    .map(([, value]) => value);
  return errors.length ? errors[0] : form.errors.permissions || '';
});

function submit() {
  form.submit({
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}

function index() {
  router.visit(UserController.index(), {
    onStart: () => (buttonCancel.value = true),
    onFinish: () => (buttonCancel.value = false),
  });
}

watchDebounced(
  search,
  (s) => {
    if (s === '') search.value = undefined;

    router.reload({
      data: { search: s },
      preserveUrl: true,
      onStart: () => (searching.value = true),
      onFinish: () => (searching.value = false),
    });
  },
  { debounce: 500, maxWait: 1000 },
);

watch([openSheetPermissions, openSheetRoles, openSheetOUs], ([isOpenSheetPermissions, isOpenSheetRoles, isOpenSheetOUs]) => {
  if (!isOpenSheetRoles || !isOpenSheetPermissions || !isOpenSheetOUs) {
    search.value = undefined;
    router.visit(UserController.create(), { preserveScroll: true, preserveState: true });
  }
});

function handlePermissionSelection(permission: Permission) {
  if (!form.permissions.includes(permission.description)) {
    form.permissions.push(permission.description);
  } else {
    form.permissions.splice(form.permissions.indexOf(permission.description), 1);
  }
}

function handleRoleSelection(role: Role) {
  if (!form.roles.includes(role.name)) {
    form.roles.push(role.name);
  } else {
    form.roles.splice(form.roles.indexOf(role.name), 1);
  }
}

function handleOUSelection(ou: OrganizationalUnit) {
  if (!form.ou_names.includes(ou.name)) {
    form.ou_names.push(ou.name);
  } else {
    form.ou_names.splice(form.ou_names.indexOf(ou.name), 1);
  }
}

function handleEmployeeSelection(employe: { [index: string]: any }) {
  form.id_card = employe.id_card;
  form.names = employe.names;
  form.surnames = employe.surnames;
  form.position = employe.position;
  form.staff_type = employe.staff_type_name;
  form.ou_names.push(employe.org_unit_name);
  openDialog.value = false;
  search.value = undefined;
}

function handleExternalPerson() {
  form.validate({
    only: ['id_card', 'names', 'surnames', 'phones', 'emails', 'position', 'staff_type'],
    onSuccess: () => {
      openDialog.value = false;
      search.value = '';
    },
  });
}
</script>

<template>
  <AppLayout :breadcrumbs>
    <Head title="Crear Nuevo Usuario" />
    <ContentLayout title="Crear Nuevo Usuario">
      <template #icon>
        <UserIcon />
      </template>
      <section class="mx-auto w-full">
        <Card class="container">
          <CardHeader>
            <CardDescription>Los campos con asterisco rojo son requeridos.</CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submit" @keyup.enter.prevent="submit">
              <div class="grid w-full items-center gap-4">
                <div class="flex flex-col space-y-1.5">
                  <Label class="is-required" for="name">Nombre de Usuario</Label>
                  <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    maxlength="255"
                    autocomplete="on"
                    placeholder="ej.: pedro.p"
                    required
                    autofocus
                    @change="form.validate('name')"
                    @keyup.esc="index"
                  />
                  <InputError :message="form.errors.name" />
                </div>
                <div class="flex flex-col space-y-1.5">
                  <Label class="is-required" for="email">Correo Electrónico</Label>
                  <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    maxlength="255"
                    autocomplete="email"
                    placeholder="ej.: pedro.p@correo.com"
                    required
                    @change="form.validate('email')"
                    @keyup.esc="index"
                  />
                  <InputError :message="form.errors.email" />
                </div>
                <br />
                <Dialog v-model:open="openDialog">
                  <DialogTrigger v-if="showDialogTrigger" as-child>
                    <Button variant="outline"> Empleado / Persona </Button>
                  </DialogTrigger>
                  <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                      <DialogTitle>Empleado / Persona</DialogTitle>
                      <DialogDescription>
                        Busque y seleccione a un empleado. Si el usuario a registrar no pertenece a la organización active el suiche "Usuario Externo"
                        para establecer los datos personales manualmente.
                      </DialogDescription>
                    </DialogHeader>
                    <div class="flex items-center space-x-2">
                      <Switch id="is_external" v-model:model-value="form.is_external" />
                      <Label for="is_external">Usuario Externo</Label>
                    </div>
                    <div v-if="!form.is_external" class="relative w-full max-w-sm items-center p-4">
                      <Input id="search" type="text" placeholder="Buscar..." class="pl-10" v-model="search" />
                      <span class="absolute inset-y-0 start-0 flex items-center justify-center px-5">
                        <LoaderCircleIcon v-if="searching" class="size-6 animate-spin text-muted-foreground" />
                        <Search v-else class="size-6 text-muted-foreground" />
                      </span>
                    </div>
                    <ScrollArea v-if="!form.is_external" class="m-3 h-20 rounded-md border">
                      <div class="p-4">
                        <div v-for="(employee, i) in employees" :key="i">
                          <Label :for="employee.id_card" class="flex items-center space-x-3">
                            <Checkbox :id="employee.id_card" @update:model-value="handleEmployeeSelection(employee)" />
                            <span>{{ `${employee.nationality}-${employee.id_card} ${employee.names} ${employee.surnames}` }}</span>
                          </Label>
                          <Separator class="my-2" />
                        </div>
                      </div>
                    </ScrollArea>
                    <div v-else class="grid gap-4">
                      <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="id_card" class="is-required text-right"> Nro. de CI </Label>
                        <Input id="id_card" v-model="form.id_card" class="col-span-3" maxlength="8" placeholder="ej.: 12345678" />
                      </div>
                      <InputError class="mt-0" :message="form.errors.id_card" />
                      <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="names" class="is-required text-right"> Nombres </Label>
                        <Input id="names" v-model="form.names" class="col-span-3" maxlength="255" placeholder="ej.: Pedro ó Pedro Luis" />
                      </div>
                      <InputError :message="form.errors.names" />
                      <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="surnames" class="is-required text-right"> Apellidos </Label>
                        <Input id="surnames" v-model="form.surnames" class="col-span-3" maxlength="255" placeholder="ej.: Pérez ó Pérez González" />
                      </div>
                      <InputError :message="form.errors.surnames" />
                      <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="position" class="text-right"> Cargo </Label>
                        <Input id="position" v-model="form.position" class="col-span-3" maxlength="255" placeholder="ej.: Auditor Externo" />
                        <InputError :message="form.errors.position" />
                      </div>
                    </div>
                    <DialogFooter v-if="form.is_external">
                      <Button type="button" @click="handleExternalPerson"> Guardar </Button>
                    </DialogFooter>
                  </DialogContent>
                </Dialog>
                <CardPerson
                  :is-external="form.is_external"
                  :idCard="form.id_card"
                  :names="form.names"
                  :surnames="form.surnames"
                  :phones="form.phones"
                  :emails="form.emails"
                  :position="form.position"
                  :staffType="form.staff_type"
                  :employee-ous="form.ou_names"
                  @quit-person="form.reset('is_external', 'id_card', 'names', 'surnames', 'phones', 'emails', 'position', 'staff_type', 'ou_names')"
                />
                <br />
                <div class="5 flex flex-col space-y-1">
                  <Label for="ou_names">Unidades Administrativas</Label>
                  <TagsInput id="ou_names" v-model="form.ou_names">
                    <TagsInputItem v-for="ou_name in form.ou_names" :key="ou_name" :value="ou_name">
                      <TagsInputItemText />
                      <TagsInputItemDelete />
                    </TagsInputItem>
                    <TagsInputInput placeholder="Unidades Administrativas seleccionados..." @click="openSheetOUs = true" />
                  </TagsInput>
                  <InputError :message="ouNamesError" />
                </div>
                <div class="5 flex flex-col space-y-1">
                  <Label for="roles">Roles</Label>
                  <TagsInput id="roles" v-model="form.roles">
                    <TagsInputItem v-for="role in form.roles" :key="role" :value="role">
                      <TagsInputItemText />
                      <TagsInputItemDelete />
                    </TagsInputItem>
                    <TagsInputInput placeholder="Roles seleccionados..." @click="openSheetRoles = true" />
                  </TagsInput>
                  <InputError :message="rolesError" />
                </div>
                <div class="5 flex flex-col space-y-1">
                  <Label for="permissions">Permisos Directos</Label>
                  <TagsInput id="permissions" v-model="form.permissions">
                    <TagsInputItem v-for="permission in form.permissions" :key="permission" :value="permission">
                      <TagsInputItemText />
                      <TagsInputItemDelete />
                    </TagsInputItem>
                    <TagsInputInput placeholder="Permisos seleccionados..." @click="openSheetPermissions = true" />
                  </TagsInput>
                  <InputError :message="permissionsError" />
                </div>
              </div>
            </form>
          </CardContent>
          <CardFooter class="flex justify-between px-6 pb-6">
            <Button variant="outline" :disabled="buttonCancel" @click="index" @keyup.esc="index" @keyup.enter="index">
              <LoaderCircleIcon v-if="buttonCancel" class="h-4 w-4 animate-spin" />
              Cancelar
            </Button>
            <Button :disabled="buttonCancel || form.processing" @click="submit" @keyup.esc="index" @keyup.enter="submit">
              <LoaderCircleIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
              Guardar
            </Button>
          </CardFooter>
        </Card>

        <Sheet v-model:open="openSheetRoles">
          <SheetContent>
            <SheetHeader>
              <SheetTitle>Seleccionar Roles</SheetTitle>
              <SheetDescription>
                Haga clic en el rol que necesite ser asignado al usuario. El usuario puede pertenecer a más de un rol a la vez, por lo que puede
                seleccionar varios. Haga clic en Seleccionar cuando haya terminado.
              </SheetDescription>
            </SheetHeader>
            <div class="relative w-full max-w-sm items-center p-4">
              <Input id="search" type="text" placeholder="Buscar..." class="pl-10" v-model="search" />
              <span class="absolute inset-y-0 start-0 flex items-center justify-center px-5">
                <Search class="size-6 text-muted-foreground" />
              </span>
            </div>
            <ScrollArea class="m-3 h-75 rounded-md border">
              <div class="p-4">
                <div v-for="(role, i) in roles" :key="i">
                  <Label :for="role.name" class="flex items-center space-x-3">
                    <Checkbox :id="role.name" :model-value="form.roles.includes(role.name)" @update:model-value="handleRoleSelection(role)" />
                    <span>{{ role.name }}</span>
                  </Label>
                  <Separator class="my-2" />
                </div>
                <WhenVisible
                  :params="{
                    data: { page: paginationRole?.current_page + 1 },
                    only: ['roles', 'paginationRole'],
                  }"
                  always
                >
                  <div class="text-muted">No hay más registros</div>
                </WhenVisible>
              </div>
            </ScrollArea>
            <SheetFooter>
              <SheetClose as-child>
                <Button>Cerrar</Button>
              </SheetClose>
            </SheetFooter>
          </SheetContent>
        </Sheet>

        <Sheet v-model:open="openSheetPermissions">
          <SheetContent>
            <SheetHeader>
              <SheetTitle>Seleccionar Permisos</SheetTitle>
              <SheetDescription>
                Haga clic en los permisos que necesiten ser asignados directamente al usuario, sin tomar en cuenta el rol (o roles) al que pertenezca.
                Haga clic en Seleccionar cuando haya terminado.
              </SheetDescription>
            </SheetHeader>
            <div class="relative w-full max-w-sm items-center p-4">
              <Input id="search" type="text" placeholder="Buscar..." class="pl-10" v-model="search" />
              <span class="absolute inset-y-0 start-0 flex items-center justify-center px-5">
                <Search class="size-6 text-muted-foreground" />
              </span>
            </div>
            <ScrollArea class="m-3 h-75 rounded-md border">
              <div class="p-4">
                <div v-for="(permission, i) in permissions" :key="i">
                  <Label :for="permission.description" class="flex items-center space-x-3">
                    <Checkbox
                      :id="permission.description"
                      :model-value="form.permissions.includes(permission.description)"
                      @update:model-value="handlePermissionSelection(permission)"
                    />
                    <span>{{ permission.description }}</span>
                  </Label>
                  <Separator class="my-2" />
                </div>
                <WhenVisible
                  :params="{
                    data: { page: paginationPerm?.current_page + 1 },
                    only: ['permissions', 'paginationPerm'],
                  }"
                  always
                >
                  <div class="text-muted">No hay más registros</div>
                </WhenVisible>
              </div>
            </ScrollArea>
            <SheetFooter>
              <SheetClose as-child>
                <Button>Cerrar</Button>
              </SheetClose>
            </SheetFooter>
          </SheetContent>
        </Sheet>

        <Sheet v-model:open="openSheetOUs">
          <SheetContent>
            <SheetHeader>
              <SheetTitle>Seleccionar Unidades Administrativas</SheetTitle>
              <SheetDescription>
                Haga clic en la unidad administrativa a la que pertenecerá el usuario. Si es necesario, puede seleccionar varias unidades
                administrativas. Cuando haya terminado, haga clic en Cerrar .
              </SheetDescription>
            </SheetHeader>
            <div class="relative w-full max-w-sm items-center p-4">
              <Input id="search" type="text" placeholder="Buscar..." class="pl-10" v-model="search" />
              <span class="absolute inset-y-0 start-0 flex items-center justify-center px-5">
                <Search class="size-6 text-muted-foreground" />
              </span>
            </div>
            <ScrollArea class="m-3 h-75 rounded-md border">
              <div class="p-4">
                <div v-for="(ou, i) in ous" :key="i">
                  <Label :for="ou.name" class="flex items-center space-x-3">
                    <Checkbox :id="ou.name" :model-value="form.ou_names.includes(ou.name)" @update:model-value="handleOUSelection(ou)" />
                    <span>{{ ou.name }}</span>
                  </Label>
                  <Separator class="my-2" />
                </div>
                <WhenVisible
                  :params="{
                    data: { page: paginationOu?.current_page + 1 },
                    only: ['ous', 'paginationOu'],
                  }"
                  always
                >
                  <div class="text-muted">No hay más registros</div>
                </WhenVisible>
              </div>
            </ScrollArea>
            <SheetFooter>
              <SheetClose as-child>
                <Button>Cerrar</Button>
              </SheetClose>
            </SheetFooter>
          </SheetContent>
        </Sheet>
      </section>
    </ContentLayout>
  </AppLayout>
</template>
