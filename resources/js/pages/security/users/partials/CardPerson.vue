<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { X } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  isExternal: boolean;
  idCard: string;
  names: string;
  surnames: string;
  phones: string | undefined;
  emails: string | undefined;
  position: string | undefined;
  staffType: string | undefined;
  employeeOus: string[] | undefined;
}>();

defineEmits(['quitPerson']);

const description = computed(() => (props.isExternal ? 'Usuario Externo' : 'Usuario Interno'));
</script>

<template>
  <Card v-if="idCard && names && surnames">
    <CardHeader>
      <CardTitle>Empleado / Persona</CardTitle>
      <CardDescription>{{ description }}</CardDescription>
    </CardHeader>
    <CardContent>
      <div class="space-y-1">
        <p class="text-sm leading-none font-medium">
          Nro. de Cédula: <span class="text-muted-foreground text-sm">{{ idCard }}</span>
        </p>
      </div>
      <div v-if="names" class="space-y-1">
        <p class="text-sm leading-none font-medium">
          Nombres: <span class="text-muted-foreground text-sm">{{ names }}</span>
        </p>
      </div>
      <div v-if="surnames" class="space-y-1">
        <p class="text-sm leading-none font-medium">
          Apellidos: <span class="text-muted-foreground text-sm">{{ surnames }}</span>
        </p>
      </div>
      <div v-if="position" class="space-y-1">
        <p class="text-sm leading-none font-medium">
          Cargo: <span class="text-muted-foreground text-sm">{{ position }}</span>
        </p>
      </div>
      <div v-if="staffType" class="space-y-1">
        <p class="text-sm leading-none font-medium">
          Personal: <span class="text-muted-foreground text-sm">{{ staffType }}</span>
        </p>
      </div>
      <div v-if="employeeOus && employeeOus?.length > 0" class="space-y-1">
        <p class="text-sm leading-none font-medium">
          Unidad Administrativa: <span class="text-muted-foreground text-sm">{{ employeeOus.join(', ')}}</span>
        </p>
      </div>
      <div v-if="phones" class="space-y-1">
        <p class="text-sm leading-none font-medium">
          Teléfonos: <span class="text-muted-foreground text-sm">{{ phones }}</span>
        </p>
      </div>
      <div v-if="emails" class="space-y-1">
        <p class="text-sm leading-none font-medium">
          Correos electrónicos: <span class="text-muted-foreground text-sm">{{ emails }}</span>
        </p>
      </div>
    </CardContent>
    <CardFooter>
      <Button type="button" variant="secondary" class="w-full" @click="$emit('quitPerson')"> <X class="mr-2 h-4 w-4" /> Eliminar </Button>
    </CardFooter>
  </Card>
</template>
