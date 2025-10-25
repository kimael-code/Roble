<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Stepper, StepperDescription, StepperItem, StepperSeparator, StepperTitle, StepperTrigger } from '@/components/ui/stepper';
import { Switch } from '@/components/ui/switch';
import { register } from '@/routes/su-installer';
import { Head, router } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue-inertia';
import { BotMessageSquareIcon, CheckIcon, CircleIcon, DotIcon, LoaderCircleIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
  employee?: {
    email: string;
    names: string;
    org_unit_name: string;
    phone_ext: string;
    position: string;
    surnames: string;
  };
}>();

const firstName = computed(() => {
  if (!props.employee) return '';
  const name = props.employee.names.trim().split(' ')[0];
  return name.charAt(0).toUpperCase() + name.slice(1).toLowerCase();
});

const username = computed(() => {
  if (!props.employee) return '';
  const namePart = props.employee.names.trim().split(' ')[0];
  const surnamePart = props.employee.surnames.trim().split(' ')[0];
  return `${namePart}.${surnamePart}`.toLowerCase();
});

type formUser = {
  id_card: string;
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
};

const form = useForm('post', register.url(), <formUser>{
  id_card: '',
  name: username.value,
  email: props.employee?.email ?? '',
  password: '',
  password_confirmation: '',
});

watch(
  () => props.employee,
  (newEmployee) => {
    if (newEmployee) {
      form.name = username.value;
      form.email = newEmployee.email;
    }
  },
);

const pswdType = ref('password');
const stepIndex = ref(1);
const steps = [
  {
    step: 1,
    title: 'Identificación',
    description: 'Proporcione su número de cédula. Es necesario verificar su identidad.',
  },
  {
    step: 2,
    title: 'Usuario',
    description: 'Proporcione un nombre de usuario y una dirección de correo electrónico.',
  },
  {
    step: 3,
    title: 'Contraseña',
    description: 'Proporcione una clave segura. Haga uso de gestores de contraseñas',
  },
];

function showPasswords() {
  if (pswdType.value === 'password') {
    pswdType.value = 'text';
  } else {
    pswdType.value = 'password';
  }
}

function submit() {
  form.submit({
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: () =>
      router.reload({
        data: { id_card: form.id_card },
        preserveUrl: true,
        only: ['employee'],
      }),
  });
}
</script>

<template>
  <div
    class="flex min-h-screen items-center justify-center bg-gray-100 opacity-100 transition-opacity duration-750 dark:bg-gray-900 starting:opacity-0"
  >
    <Head title="Instalador" />

    <Card class="mx-4 w-full lg:max-w-4xl">
      <CardHeader class="text-center">
        <CardTitle class="text-2xl font-bold"> Asistente de Instalación de Superusuario </CardTitle>
        <CardDescription> Este asistente le permite configurar un nuevo usuario con rol de Superusuario en el sistema. </CardDescription>
      </CardHeader>
      <CardContent>
        <div class="space-y-6">
          <Separator />
          <div class="text-center">
            <p class="text-gray-600 dark:text-gray-400">Los campos marcados con un asterisco rojo son obligatorios.</p>
          </div>
          <Stepper v-slot="{ nextStep, prevStep }" v-model="stepIndex" class="block w-full">
            <form @submit.prevent="submit">
              <div class="flex-start flex w-full gap-2">
                <StepperItem
                  v-for="step in steps"
                  :key="step.step"
                  v-slot="{ state }"
                  class="relative flex w-full flex-col items-center justify-center"
                  :step="step.step"
                >
                  <StepperSeparator
                    v-if="step.step !== steps[steps.length - 1].step"
                    class="absolute top-5 right-[calc(-50%+10px)] left-[calc(50%+20px)] block h-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary"
                  />

                  <StepperTrigger as-child>
                    <Button
                      :variant="state === 'completed' || state === 'active' ? 'default' : 'outline'"
                      size="icon"
                      class="z-10 shrink-0 rounded-full"
                      :class="[state === 'active' && 'ring-2 ring-ring ring-offset-2 ring-offset-background']"
                      :disabled="state !== 'completed' && form.hasErrors"
                    >
                      <CheckIcon v-if="state === 'completed'" class="size-5" />
                      <CircleIcon v-if="state === 'active'" />
                      <DotIcon v-if="state === 'inactive'" />
                    </Button>
                  </StepperTrigger>

                  <div class="mt-5 flex flex-col items-center text-center">
                    <StepperTitle :class="[state === 'active' && 'text-primary']" class="text-sm font-semibold transition lg:text-base">
                      {{ step.title }}
                    </StepperTitle>
                    <StepperDescription
                      :class="[state === 'active' && 'text-primary']"
                      class="sr-only text-xs text-muted-foreground transition md:not-sr-only lg:text-sm"
                    >
                      {{ step.description }}
                    </StepperDescription>
                  </div>
                </StepperItem>
              </div>

              <div class="mt-4 flex flex-col gap-4">
                <template v-if="stepIndex === 1">
                  <div class="flex flex-col space-y-1.5">
                    <Label class="is-required" for="id_card">N° de CI</Label>
                    <Input
                      id="id_card"
                      v-model="form.id_card"
                      type="text"
                      maxlength="8"
                      autocomplete="on"
                      placeholder="ej.: 12345678"
                      required
                      autofocus
                      @change="form.validate('id_card')"
                      @keyup.enter="
                        form.validate({
                          only: ['id_card'],
                          onSuccess: () =>
                            router.reload({
                              data: { id_card: form.id_card },
                              preserveUrl: true,
                              only: ['employee'],
                              onSuccess: () => {
                                form.clearErrors();
                                nextStep();
                              },
                            }),
                        })
                      "
                    />
                    <InputError :message="form.errors.id_card" />
                  </div>
                </template>
                <template v-if="stepIndex === 2">
                  <div class="flex flex-col space-y-1.5">
                    <Alert class="mb-5" v-if="employee">
                      <BotMessageSquareIcon class="h-4 w-4" />
                      <AlertTitle>¡Enhorabuena {{ firstName }}!</AlertTitle>
                      <AlertDescription>
                        Usted es {{ employee?.position }} adscrito a la unidad administrativa {{ employee?.org_unit_name }}. Por favor, verifique los
                        siguientes datos. Recuerde: su nombre de usuario puede estar en perfecto español.
                      </AlertDescription>
                    </Alert>
                    <Label class="is-required" for="name">Nombre de Usuario</Label>
                    <Input
                      id="name"
                      v-model="form.name"
                      type="text"
                      maxlength="255"
                      autocomplete="on"
                      placeholder="ej.: pedro.pérez"
                      title="Por ejemplo: pedro.pérez, carlos.gonzález, p.patiño, cgonzález, etc"
                      required
                      autofocus
                      @change="form.validate('name')"
                      @keyup.enter="
                        form.validate({
                          only: ['name', 'email'],
                          onSuccess: () => {
                            form.clearErrors();
                            nextStep();
                          },
                        })
                      "
                    />
                    <InputError :message="form.errors.name" />
                  </div>
                  <div class="flex flex-col space-y-1.5">
                    <Label class="is-required" for="email">Dirección de Correo Electrónico</Label>
                    <Input
                      id="email"
                      v-model="form.email"
                      type="email"
                      maxlength="255"
                      autocomplete="email"
                      placeholder="ej.: pedro.perez@correo.com"
                      title="Por ejemplo: pedro.perez@correo.com, carlos.gonzalez@correo.com, p.patino@correo.com, cgonzalez@correo.com, etc"
                      required
                      @change="form.validate('email')"
                      @keyup.enter="
                        form.validate({
                          only: ['name', 'email'],
                          onSuccess: () => {
                            form.clearErrors();
                            nextStep();
                          },
                        })
                      "
                    />
                    <InputError :message="form.errors.email" />
                  </div>
                </template>

                <template v-if="stepIndex === 3">
                  <div class="flex flex-col space-y-1.5">
                    <Label class="is-required" for="password">Contraseña</Label>
                    <Input
                      id="password"
                      :type="pswdType"
                      v-model="form.password"
                      maxlength="255"
                      autocomplete="new-password"
                      placeholder="Contraseña"
                      autofocus
                      required
                      @change="form.validate('password')"
                      @keyup.enter="
                        form.validate({
                          only: ['password'],
                          onSuccess: () => {
                            form.clearErrors();
                            submit();
                          },
                        })
                      "
                    />
                    <InputError :message="form.errors.password" />
                  </div>
                  <div class="flex flex-col space-y-1.5">
                    <Label class="is-required" for="password_confirmation">Confirmar Contraseña</Label>
                    <Input
                      id="password_confirmation"
                      :type="pswdType"
                      v-model="form.password_confirmation"
                      maxlength="255"
                      autocomplete="new-password"
                      placeholder="Contraseña"
                      required
                      @change="form.validate('password_confirmation')"
                      @keyup.enter="
                        form.validate({
                          only: ['password'],
                          onSuccess: () => {
                            form.clearErrors();
                            submit();
                          },
                        })
                      "
                    />
                    <InputError :message="form.errors.password_confirmation" />
                  </div>
                  <div class="flex items-center space-x-2">
                    <Switch id="show_fields" @update:model-value="showPasswords" />
                    <Label for="show_fields">Mostrar/Ocultar contraseñas</Label>
                  </div>
                </template>
              </div>

              <div class="mt-4 flex items-center justify-between">
                <Button type="button" :disabled="stepIndex === 1" variant="outline" size="sm" @click="(form.clearErrors(), prevStep())">
                  Anterior
                </Button>
                <div class="flex items-center gap-3">
                  <Button
                    v-if="stepIndex !== 3"
                    type="button"
                    :disabled="form.validating"
                    size="sm"
                    @click="
                      form.validate({
                        only: stepIndex === 1 ? ['id_card'] : ['name', 'email'],
                        onSuccess: () => {
                          if (stepIndex === 1) {
                            router.reload({
                              data: { id_card: form.id_card },
                              preserveUrl: true,
                              only: ['employee'],
                              onSuccess: () => {
                                form.clearErrors();
                                nextStep();
                              },
                            });
                          } else {
                            form.clearErrors();
                            nextStep();
                          }
                        },
                      })
                    "
                  >
                    <LoaderCircleIcon v-if="form.validating" class="h-4 w-4 animate-spin" />
                    Siguiente
                  </Button>
                  <Button v-if="stepIndex === 3" size="sm" type="button" :disabled="form.processing" @click="submit">
                    <LoaderCircleIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Finalizar
                  </Button>
                </div>
              </div>
            </form>
          </Stepper>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
